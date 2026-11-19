<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Fills players.cutout_image_url with a transparent TheSportsDB cut-out ONLY when it
 * can be trusted to show the player's current club kit:
 *
 *  1. TheSportsDB's record (matched by name + date of birth) must place the player
 *     at the same club as this database — by TheSportsDB team id, not by name;
 *  2. the image must have been uploaded (timestamp in its file name) after the
 *     player joined that club, when the join date is known — an older upload would
 *     show the previous club's shirt;
 *  3. strCutout is preferred, then strRender (also a transparent PNG). strThumb is a
 *     photo with a background, so it is never used.
 *
 * Anyone failing the checks gets a jersey silhouette in their club's colours with
 * their squad number (an inline SVG, so it always resolves).
 *
 * Lookups come from data/player_cutouts.json, written by `php artisan
 * football:sync-tsdb`. Players missing from it are looked up here when
 * THESPORTSDB_FETCH is on (it is off in tests). Runs outside the main seeding
 * transaction so a slow API never holds the DB lock.
 */
class PlayerCutoutSeeder extends Seeder
{
    public function run(): void
    {
        $cache = TheSportsDb::loadCache();
        $api = config('services.thesportsdb.fetch') ? new TheSportsDb(fn ($m) => $this->command?->warn("  {$m}")) : null;
        $teamIdByCode = array_map(fn ($t) => $t['id'], $cache['teams']);
        $dirty = false;
        $outcomes = [];
        $usedUrls = [];
        // Reviewed rejections: wrong kit in the image, or reported as showing someone else.
        $overrides = (require __DIR__.'/data/football_profiles.php')['roster_overrides'] ?? [];
        $rejected = $overrides['reject_cutouts'] ?? [];
        // Reviewed acceptances: the image's kit was checked by eye and matches the player's club.
        $acceptedKits = $overrides['accept_cutouts'] ?? [];
        $otherKitImages = $overrides['other_kit_images'] ?? [];
        // Players in a locked squad: their club is confirmed, whatever TheSportsDB's record says.
        $lockedKeys = [];
        foreach ($overrides['lock'] ?? [] as $entries) {
            foreach (array_keys($entries) as $key) {
                $lockedKeys[TheSportsDb::nfc($key)] = true;
            }
        }

        $players = Player::with('currentTeams')->orderBy('id')->get();
        $batchDates = self::clubPhotoBatches($players, $cache, $teamIdByCode);

        foreach ($players as $player) {
            $team = $player->currentTeams->first();
            $key = trim("{$player->first_name} {$player->last_name}").'|'.$player->date_of_birth?->toDateString();

            if (! isset($cache['players'][$key]) && $api) {
                $found = $api->searchPlayer(trim("{$player->first_name} {$player->last_name}"), $player->date_of_birth?->toDateString());
                if ($found !== false) {
                    $cache['players'][$key] = $found ? self::record($found) : ['found' => false, 'checked' => now()->toDateString()];
                    $dirty = true;
                }
            }

            $name = trim("{$player->first_name} {$player->last_name}");
            [$url, $outcome] = match (true) {
                isset($rejected[$name]) => [null, 'rejected: manual review'],
                // Kit visually checked against the player's (reviewed) club.
                isset($acceptedKits[$name]) && ! empty($cache['players'][$key]['cutout']) => [$cache['players'][$key]['cutout'], 'verified cutout (reviewed kit)'],
                // Locked squad: a verified current-kit photo first, else one from the club's photo shoot.
                isset($lockedKeys[$key]) => (function () use ($player, $team, $cache, $key, $teamIdByCode, $batchDates) {
                    $verified = $this->verifiedCutout($player, $team, $cache['players'][$key] ?? null, $teamIdByCode[$team?->short_code] ?? null, $batchDates[$team?->short_code] ?? null);

                    return $verified[0] !== null ? $verified : self::lockedCutout($cache['players'][$key] ?? null, $batchDates[$team?->short_code] ?? null);
                })(),
                default => $this->verifiedCutout($player, $team, $cache['players'][$key] ?? null, $teamIdByCode[$team?->short_code] ?? null, $batchDates[$team?->short_code] ?? null),
            };
            // No current-kit photo: show the player's real photo in whatever kit TheSportsDB has
            // (product decision 2026-11-19) — still only from the record matched by name + date
            // of birth, so it is the right person; never for an identity rejection.
            $record = $cache['players'][$key] ?? null;
            if ($url === null && ! isset($rejected[$name]) && ($record['found'] ?? false)) {
                $other = ($record['cutout'] ?? null) ?: ($record['render'] ?? null);
                if ($other !== null) {
                    [$url, $outcome] = [$other, 'other kit'];
                }
            }
            // Never show one image for two players (it would be someone else's face).
            if ($url !== null && isset($usedUrls[$url])) {
                [$url, $outcome] = [null, 'rejected: image already used by '.$usedUrls[$url]];
            }
            if ($url !== null) {
                $usedUrls[$url] = $name;
            }
            $outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;
            $served = $url !== null ? $this->localCopy($url) : null;
            $player->update([
                'cutout_image_url' => $served ?? self::jerseyUrl($player, $team),
                // The real photo for any client reading photo_url; null (never the SVG) when there is none.
                'photo_url' => $served,
                'cutout_source' => isset($otherKitImages[basename((string) parse_url((string) $url, PHP_URL_PATH))])
                    ? 'other_kit' // reviewed image known to show another kit
                    : self::source($outcome, $url),
            ]);
        }

        if ($dirty) {
            TheSportsDb::saveCache($cache);
        }

        ksort($outcomes);
        $this->command?->info('Player cut-outs: '.collect($outcomes)->map(fn ($n, $k) => "{$k}: {$n}")->implode(', '));
    }

    /**
     * @param  array<string, mixed>|null  $record
     * @return array{0: ?string, 1: string} accepted URL (or null) and why
     */
    private function verifiedCutout(Player $player, ?Team $team, ?array $record, ?string $clubTsdbId, ?string $clubBatchDate = null): array
    {
        if ($record === null) {
            return [null, 'unchecked'];
        }
        if (! ($record['found'] ?? false)) {
            return [null, 'not on TheSportsDB'];
        }
        if (TheSportsDb::isNationalTeamRecord($record)) {
            // TheSportsDB labels the record with the national team, but the image is often from
            // the club's official shoot (Olise: "France" record, Bayern photo of 2025-08-28).
            // Accept it only when it was uploaded with the club's photo batch.
            $uploaded = TheSportsDb::uploadedAt($record['cutout'] ?? null);
            if ($uploaded !== null && $clubBatchDate !== null
                && abs($uploaded - strtotime($clubBatchDate)) <= 2 * 86400) {
                return [$record['cutout'], 'verified cutout (club photo batch)'];
            }

            return [null, 'rejected: national-team kit'];
        }
        if ($clubTsdbId === null || ($record['team_id'] ?? null) !== $clubTsdbId) {
            return [null, 'rejected: other club'];
        }

        $joined = self::joinedAt($player, $team);
        foreach (['cutout', 'render'] as $field) {
            $url = $record[$field] ?? null;
            if ($url === null) {
                continue;
            }
            $uploaded = TheSportsDb::uploadedAt($url);
            if ($joined !== null && $uploaded !== null && $uploaded < $joined) {
                continue; // uploaded before the move: previous club's kit
            }

            return [$url, "verified {$field}"];
        }

        return [null, ($record['cutout'] ?? $record['render'] ?? null) ? 'rejected: predates transfer' : 'no cut-out'];
    }

    /**
     * Locked-squad player: the club is confirmed, so the photo only has to come from the club's
     * own shoot (uploaded within two days of its photo batch); otherwise the jersey fallback.
     *
     * @return array{0: ?string, 1: string}
     */
    private static function lockedCutout(?array $record, ?string $clubBatchDate): array
    {
        foreach (['cutout', 'render'] as $field) {
            $uploaded = TheSportsDb::uploadedAt($record[$field] ?? null);
            if ($uploaded !== null && $clubBatchDate !== null && abs($uploaded - strtotime($clubBatchDate)) <= 2 * 86400) {
                return [$record[$field], "verified {$field} (locked squad, club photo batch)"];
            }
        }

        return [null, 'locked squad: no photo from the club shoot'];
    }

    /**
     * Each club's official photo-shoot date: the most common upload day among cut-outs
     * whose TheSportsDB record is confirmed at that club.
     *
     * @return array<string, string> club code => Y-m-d
     */
    private static function clubPhotoBatches($players, array $cache, array $teamIdByCode): array
    {
        $days = [];
        foreach ($players as $player) {
            $code = $player->currentTeams->first()?->short_code;
            $record = $cache['players'][trim("{$player->first_name} {$player->last_name}").'|'.$player->date_of_birth?->toDateString()] ?? null;
            $uploaded = TheSportsDb::uploadedAt($record['cutout'] ?? null);
            if ($code && $uploaded && ($record['team_id'] ?? null) === ($teamIdByCode[$code] ?? false)) {
                $days[$code][] = date('Y-m-d', $uploaded);
            }
        }

        // A shoot is a day with at least two of the club's photos; a lone upload is not a batch.
        return array_filter(array_map(function (array $list) {
            $counts = array_count_values($list);
            arsort($counts);

            return reset($counts) >= 2 ? array_key_first($counts) : null;
        }, $days));
    }

    /** When the player joined their current club (start of that month), from their transfer history. */
    private static function joinedAt(Player $player, ?Team $team): ?int
    {
        foreach (array_reverse($player->transfer_history ?? []) as $move) {
            if ($team && $move['to'] === $team->name && ! empty($move['date'])) {
                return Carbon::parse("{$move['date']}-01")->getTimestamp();
            }
        }

        return null;
    }

    /**
     * Serves the accepted image from our own storage: copies it to
     * storage/app/public/cutouts/<TheSportsDB file name> (downloaded once, from the image
     * CDN, only when fetching is allowed) and returns its public URL. Falls back to the
     * remote URL when local copies are disabled (tests) or the download fails.
     */
    private function localCopy(string $remote): string
    {
        if (! config('services.thesportsdb.local_cutouts')) {
            return $remote;
        }
        $file = basename(parse_url($remote, PHP_URL_PATH) ?: '');
        if (! preg_match('/^[A-Za-z0-9_-]+\.(png|jpe?g|webp)$/', $file)) {
            return $remote;
        }
        $path = storage_path("app/public/cutouts/{$file}");
        // Image files come from the CDN (not the rate-limited API), so this is not gated on THESPORTSDB_FETCH.
        if (! is_file($path)) {
            try {
                $response = Http::timeout(30)->get($remote);
                if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                    @mkdir(dirname($path), 0775, true);
                    file_put_contents($path, $response->body());
                }
            } catch (\Throwable $e) {
                $this->command?->warn("  Could not download {$remote}: {$e->getMessage()}");
            }
        }

        return is_file($path) ? rtrim((string) config('services.thesportsdb.cutouts_url'), '/')."/{$file}" : $remote;
    }

    /** Short label for why this photo was chosen (players.cutout_source). */
    private static function source(string $outcome, ?string $url): string
    {
        return match (true) {
            $url === null => 'fallback',
            $outcome === 'other kit' => 'other_kit',
            str_contains($outcome, 'reviewed kit') => 'reviewed',
            str_contains($outcome, 'club photo batch') => 'club_shoot',
            default => 'current_kit',
        };
    }

    /** Shirt silhouette in the club's colours with the squad number, as an SVG data URI. */
    private static function jerseyUrl(Player $player, ?Team $team): string
    {
        $body = $team?->primary_color ?? '#1F2937';
        $trim = $team?->secondary_color ?? '#FFFFFF';
        if (strcasecmp($body, $trim) === 0) {
            $trim = strcasecmp($body, '#FFFFFF') === 0 ? '#000000' : '#FFFFFF';
        }
        $number = $player->shirt_number ?? '';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">'
            .'<path d="M70 20 L40 32 L8 64 L30 92 L48 78 L48 186 L152 186 L152 78 L170 92 L192 64 L160 32 L130 20 Q100 44 70 20 Z" '
            ."fill=\"{$body}\" stroke=\"{$trim}\" stroke-width=\"6\" stroke-linejoin=\"round\"/>"
            ."<path d=\"M70 20 Q100 44 130 20\" fill=\"none\" stroke=\"{$trim}\" stroke-width=\"8\"/>"
            ."<text x=\"100\" y=\"140\" font-family=\"Arial,Helvetica,sans-serif\" font-size=\"64\" font-weight=\"700\" text-anchor=\"middle\" fill=\"{$trim}\">{$number}</text>"
            .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    /** @param  array<string, mixed>  $p */
    private static function record(array $p): array
    {
        return [
            'found' => true, 'id' => $p['idPlayer'] ?? null, 'name' => $p['strPlayer'], 'dob' => $p['dateBorn'] ?? null,
            'team_id' => $p['idTeam'] ?? null, 'team' => $p['strTeam'] ?? null, 'status' => $p['strStatus'] ?? null,
            'number' => null, 'position' => $p['strPosition'] ?? null, 'nationality' => $p['strNationality'] ?? null,
            'height' => null, 'wage' => null,
            'cutout' => ($p['strCutout'] ?? null) ?: null, 'render' => ($p['strRender'] ?? null) ?: null,
            'thumb' => ($p['strThumb'] ?? null) ?: null, 'checked' => now()->toDateString(),
        ];
    }
}
