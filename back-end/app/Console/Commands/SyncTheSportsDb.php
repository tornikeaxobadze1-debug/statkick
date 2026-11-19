<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Support\Roster\PlayerRosterVerifier;
use Database\Seeders\FootballSeeder;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Console\Command;

/**
 * Verifies squads against TheSportsDB and records what it says in
 * database/seeders/data/player_cutouts.json, which the seeders then read offline:
 *
 *   teams       club code => TheSportsDB team id, name and manager (when listed)
 *   players     "Name|dob" => the player's TheSportsDB record (club, number,
 *               position, wage, cutout/render/thumb), or {found: false}
 *   candidates  club code => {searched name => "Name|dob" key, or null}
 *
 * Checked: every squad player in FootballSeeder::LEAGUES, the free squad lookup
 * (≤10 people per club), and the candidate names in football_profiles.php — a
 * candidate only ends up in a squad if TheSportsDB places them at that club.
 *
 * Resumable: progress is saved after every lookup and already-checked entries are
 * skipped unless --refresh is given. Expect ~4–10s per request (rate limits).
 */
class SyncTheSportsDb extends Command
{
    protected $signature = 'football:sync-tsdb
        {--refresh : Re-check entries that are already cached}
        {--missing-photos : Only re-fetch players in the database who have no photo yet}
        {--profiles : Fetch full TheSportsDB profiles (height, foot, wage, agent, position) for every seeded player}
        {--career : Fetch former teams and honours for every seeded player}';

    protected $description = 'Verify current squads and player cut-outs against TheSportsDB';

    private const STAFF = ['Manager', 'Coach', 'Assistant Coach', 'Goalkeeping Coach', 'Goalkeeper Coach', 'Fitness Coach', 'Head Coach'];

    /** @var array{teams: array, players: array, candidates: array} */
    private array $cache;

    public function handle(): int
    {
        $this->cache = TheSportsDb::loadCache();
        $refresh = (bool) $this->option('refresh');
        $api = new TheSportsDb(fn (string $m) => $this->warn("  {$m}"));
        $profiles = require base_path('database/seeders/data/football_profiles.php');
        $today = now()->toDateString();

        if ($this->option('career')) {
            return $this->fetchCareers($api, $today);
        }
        if ($this->option('profiles')) {
            return $this->fetchProfiles($api, $today);
        }
        if ($this->option('missing-photos')) {
            return $this->refreshMissingPhotos($api, $today);
        }

        foreach (FootballSeeder::LEAGUES as [$countryName, , $clubs]) {
            foreach ($clubs as $code => [$teamName, $players]) {
                $this->info("{$teamName}");

                // 1. Team id (+ manager from the squad lookup).
                if ($refresh || ! isset($this->cache['teams'][$code])) {
                    $team = $api->findTeam($profiles['tsdb_search'][$code] ?? $teamName, $countryName, [$teamName, ...(PlayerRosterVerifier::CLUB_ALIASES[$teamName] ?? [])]);
                    if ($team === false) {
                        continue;
                    }
                    if ($team === null) {
                        $this->error("  No TheSportsDB team for {$teamName}");

                        continue;
                    }
                    $this->cache['teams'][$code] = ['id' => $team['idTeam'], 'name' => $team['strTeam'], 'manager' => null, 'squad_checked' => null];
                    $this->save();
                }
                $teamId = $this->cache['teams'][$code]['id'];

                if ($refresh || $this->cache['teams'][$code]['squad_checked'] === null) {
                    $squad = $api->squad($teamId);
                    if ($squad !== false) {
                        foreach ($squad as $person) {
                            if (in_array($person['strPosition'] ?? '', self::STAFF, true) || ($person['strStatus'] ?? '') === 'Coaching') {
                                if (($person['strPosition'] ?? '') === 'Manager') {
                                    $this->cache['teams'][$code]['manager'] = $person['strPlayer'];
                                }

                                continue;
                            }
                            $this->cache['players']["{$person['strPlayer']}|{$person['dateBorn']}"] = self::record($person, $today);
                        }
                        $this->cache['teams'][$code]['squad_checked'] = $today;
                        $this->save();
                    }
                }

                // 2. Existing squad players.
                foreach ($players as [$first, $last, , $dob]) {
                    $name = trim("{$first} {$last}");
                    $key = "{$name}|{$dob}";
                    if (! $refresh && isset($this->cache['players'][$key])) {
                        continue;
                    }
                    $found = $api->searchPlayer($name, $dob);
                    if ($found === false) {
                        continue;
                    }
                    $this->cache['players'][$key] = $found ? self::record($found, $today) : ['found' => false, 'checked' => $today];
                    $this->line(sprintf('  %-28s %s', $name, $found ? $found['strTeam'] : 'not found'));
                    $this->save();
                }

                // 3. Candidates: added only if TheSportsDB puts them at this club.
                foreach ($profiles['candidates'][$code] ?? [] as $name) {
                    if (! $refresh && array_key_exists($name, $this->cache['candidates'][$code] ?? [])) {
                        continue;
                    }
                    $found = $api->searchPlayer($name);
                    if ($found === false) {
                        continue;
                    }
                    $key = $found ? "{$found['strPlayer']}|{$found['dateBorn']}" : null;
                    if ($found) {
                        $this->cache['players'][$key] = self::record($found, $today);
                    }
                    $this->cache['candidates'][$code][$name] = $key;
                    $this->line(sprintf('  + %-26s %s', $name, $found ? $found['strTeam'] : 'not found'));
                    $this->save();
                }
            }
        }

        $this->info('Saved '.TheSportsDb::cachePath());

        return self::SUCCESS;
    }

    /**
     * Re-fetches TheSportsDB records for every seeded player without a photo (photo_url
     * null), matching by name + date of birth, so newer uploads are picked up.
     */
    private function refreshMissingPhotos(TheSportsDb $api, string $today): int
    {
        $players = Player::whereNull('photo_url')->orderBy('last_name')->get();
        $this->info("Re-fetching {$players->count()} players without a photo…");
        $gained = 0;
        foreach ($players as $player) {
            $name = trim("{$player->first_name} {$player->last_name}");
            $key = TheSportsDb::nfc("{$name}|{$player->date_of_birth?->toDateString()}");
            $found = $api->searchPlayer($name, $player->date_of_birth?->toDateString());
            if ($found === false) {
                continue; // API error: keep the old record, retry next time
            }
            $before = $this->cache['players'][$key]['cutout'] ?? null;
            if (! $found && ($this->cache['players'][$key]['found'] ?? false)) {
                // A miss on the (single-result) search must never erase a known-good record.
                $this->line(sprintf('  %-28s %s', $name, 'not found this time — keeping the existing record'));

                continue;
            }
            $previous = $this->cache['players'][$key] ?? [];
            $this->cache['players'][$key] = $found ? self::record($found, $today) : ['found' => false, 'checked' => $today];
            // Search results carry no squad number, height or wage: keep what an earlier squad lookup gave.
            foreach (['number', 'height', 'wage'] as $field) {
                $this->cache['players'][$key][$field] ??= $previous[$field] ?? null;
            }
            $after = $this->cache['players'][$key]['cutout'] ?? $this->cache['players'][$key]['render'] ?? null;
            $gained += ($after && ! $before) ? 1 : 0;
            $this->line(sprintf('  %-28s %s', $name, $found ? (($found['strTeam'] ?? '?').($after ? ($after !== $before ? ' — NEW image' : ' — image') : ' — no image')) : 'not found'));
            $this->save();
        }
        $this->info("Done: {$gained} players gained an image record.");

        return self::SUCCESS;
    }

    /**
     * Full TheSportsDB profile for every seeded player: finds the player id (cached, or by a
     * name + date-of-birth search) and stores height, foot, wage, agent, number and position
     * under 'profile' in the cached record. Resumable; never erases existing data.
     */
    private function fetchProfiles(TheSportsDb $api, string $today): int
    {
        $players = Player::orderBy('last_name')->get();
        $done = 0;
        foreach ($players as $player) {
            $name = trim("{$player->first_name} {$player->last_name}");
            $dob = $player->date_of_birth?->toDateString();
            $key = TheSportsDb::nfc("{$name}|{$dob}");
            $record = $this->cache['players'][$key] ?? [];
            if (isset($record['profile']['checked'])) {
                continue; // already fetched (resume)
            }
            $id = $record['id'] ?? null;
            if (! $id) {
                $found = $api->searchPlayer($name, $dob);
                if ($found === false) {
                    continue;
                }
                $id = $found['idPlayer'] ?? null;
                if ($found && ($record['found'] ?? false) === false) {
                    $record = self::record($found, $today);
                }
                $record['id'] ??= $id;
            }
            if (! $id) {
                $record['profile'] = ['checked' => $today]; // nothing to fetch
                $this->cache['players'][$key] = $record + ['found' => false];
                $this->save();
                $this->line(sprintf('  %-28s no TheSportsDB id', $name));

                continue;
            }
            $full = $api->lookupPlayer($id);
            if ($full === false) {
                continue;
            }
            $record['profile'] = [
                'height' => ($full['strHeight'] ?? '') ?: null,
                'side' => ($full['strSide'] ?? '') ?: null,
                'wage' => ($full['strWage'] ?? '') ?: null,
                'agent' => ($full['strAgent'] ?? '') ?: null,
                'number' => ($full['strNumber'] ?? '') !== '' ? (int) $full['strNumber'] : null,
                'position' => ($full['strPosition'] ?? '') ?: null,
                'checked' => $today,
            ];
            $this->cache['players'][$key] = $record;
            $this->save();
            $done++;
            $p = $record['profile'];
            $this->line(sprintf('  %-28s %s | %s | %s | %s', $name, $p['height'] ?? '-', $p['side'] ?? '-', $p['position'] ?? '-', $p['agent'] ?? 'no agent'));
        }
        $this->info("Fetched {$done} profiles.");

        return self::SUCCESS;
    }

    /**
     * Career path (former teams: club, joined/departed year, move type) and honours (title,
     * team, season) for every seeded player with a TheSportsDB id, stored under 'career'.
     * Resumable; an API failure leaves the player for the next run.
     */
    private function fetchCareers(TheSportsDb $api, string $today): int
    {
        $done = 0;
        foreach (Player::orderBy('last_name')->get() as $player) {
            $key = TheSportsDb::nfc(trim("{$player->first_name} {$player->last_name}").'|'.$player->date_of_birth?->toDateString());
            $record = $this->cache['players'][$key] ?? null;
            if (! ($record['id'] ?? null) || isset($record['career']['checked'])) {
                continue;
            }
            $teams = $api->formerTeams($record['id']);
            $honours = $teams === false ? false : $api->honours($record['id']);
            if ($teams === false || $honours === false) {
                continue;
            }
            $record['career'] = [
                'former_teams' => array_values(array_map(fn ($t) => [
                    'team' => $t['strFormerTeam'] ?? null, 'move' => $t['strMoveType'] ?? null,
                    'joined' => ($t['strJoined'] ?? '') ?: null, 'departed' => ($t['strDeparted'] ?? '') ?: null,
                ], $teams)),
                'honours' => array_values(array_map(fn ($h) => [
                    'title' => $h['strHonour'] ?? null, 'team' => $h['strTeam'] ?? null, 'season' => $h['strSeason'] ?? null,
                ], $honours)),
                'checked' => $today,
            ];
            $this->cache['players'][$key] = $record;
            $this->save();
            $done++;
            $this->line(sprintf('  %-28s %d former teams, %d honours', $key, count($teams), count($honours)));
        }
        $this->info("Fetched {$done} careers.");

        return self::SUCCESS;
    }

    private function save(): void
    {
        TheSportsDb::saveCache($this->cache);
    }

    /** @param  array<string, mixed>  $p */
    private static function record(array $p, string $today): array
    {
        return [
            'found' => true,
            'id' => $p['idPlayer'] ?? null,
            'name' => $p['strPlayer'],
            'dob' => $p['dateBorn'] ?? null,
            'team_id' => $p['idTeam'] ?? null,
            'team' => $p['strTeam'] ?? null,
            'status' => $p['strStatus'] ?? null,
            'number' => ($p['strNumber'] ?? '') !== '' ? (int) $p['strNumber'] : null,
            'position' => $p['strPosition'] ?? null,
            'nationality' => $p['strNationality'] ?? null,
            'height' => $p['strHeight'] ?? null,
            'wage' => ($p['strWage'] ?? '') ?: null,
            'cutout' => ($p['strCutout'] ?? null) ?: null,
            'render' => ($p['strRender'] ?? null) ?: null,
            'thumb' => ($p['strThumb'] ?? null) ?: null,
            'checked' => $today,
        ];
    }
}
