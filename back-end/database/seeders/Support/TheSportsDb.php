<?php

namespace Database\Seeders\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal TheSportsDB v1 client for seeding: spaced-out requests with back-off on
 * HTTP 429 (the free key "123" is rate limited hard, and returns only the single
 * best search hit and at most 10 people per squad lookup).
 *
 * Every lookup method returns false on a network/API failure so callers can tell
 * "not found" (null / []) from "couldn't ask" and avoid caching the latter.
 */
class TheSportsDb
{
    /** Verified lookups written by `php artisan football:sync-tsdb` and read by the seeders. */
    public const CACHE_FILE = __DIR__.'/../data/player_cutouts.json';

    private const BASE = 'https://www.thesportsdb.com/api/v1/json/%s/%s';

    /**
     * Extra queries tried (in order) when the plain name doesn't return the right
     * player — the free key returns one hit, so ambiguous names need a more specific
     * query. "Pedri González" and "Rodri Cascante" are verified to work.
     */
    private const SEARCH_ALIASES = [
        'Pedri' => ['Pedri González'],
        'Rodri' => ['Rodri Cascante'],
        'Gavi' => ['Pablo Gavi', 'Gavi Páez'],
        'Reece James' => ['Reece James Chelsea'],
        'Alisson' => ['Alisson Becker'],
        'Koke' => ['Koke Resurrección'],
        'Marquinhos' => ['Marcos Aoás Corrêa'],
        'Vitinha' => ['Vitor Ferreira'],
        'Raphinha' => ['Raphael Dias Belloli'],
    ];

    private int $gapMs = 4000;

    private ?float $lastRequestAt = null;

    /** @param  (callable(string): void)|null  $log */
    public function __construct(private $log = null) {}

    /**
     * The footballer called $name, tried with known aliases. With $dob, only a hit
     * born that day is accepted (searches are fuzzy: "Rodri" finds "Jay Rodriguez");
     * without it, only an exact (accent-insensitive) name match.
     *
     * @return array<string, mixed>|null|false
     */
    public function searchPlayer(string $name, ?string $dob = null): array|null|false
    {
        foreach ([$name, ...(self::SEARCH_ALIASES[$name] ?? [])] as $query) {
            $players = $this->get('searchplayers.php', ['p' => $query], 'player');
            if ($players === false) {
                return false;
            }
            foreach ($players as $p) {
                if (($p['strSport'] ?? null) !== 'Soccer') {
                    continue;
                }
                $matches = $dob !== null
                    ? ($p['dateBorn'] ?? null) === $dob
                    : self::normalise($p['strPlayer'] ?? '') === self::normalise($name);
                if ($matches) {
                    return $p;
                }
            }
        }

        return null;
    }

    /**
     * The soccer club called $name — accepted only if its name or one of its alternate
     * names is in $acceptedNames (searching "Paris SG" once returned Torcy).
     *
     * @param  list<string>  $acceptedNames
     * @return array<string, mixed>|null|false
     */
    public function findTeam(string $name, string $country, array $acceptedNames = []): array|null|false
    {
        $teams = $this->get('searchteams.php', ['t' => $name], 'teams');
        if ($teams === false) {
            return false;
        }

        $accepted = array_map(self::normalise(...), [$name, ...$acceptedNames]);
        $soccer = collect($teams)->filter(fn ($t) => ($t['strSport'] ?? null) === 'Soccer'
            && array_intersect($accepted, array_map(self::normalise(...), [$t['strTeam'] ?? '', ...explode(',', $t['strTeamAlternate'] ?? '')])) !== []);

        // Prefer the club in the league's country (Monaco is listed under its own country).
        return $soccer->first(fn ($t) => ($t['strCountry'] ?? null) === $country) ?? $soccer->first();
    }

    /** @return list<array<string, mixed>>|false up to 10 people (players and staff) on the free key */
    /** @return array<string, mixed>|null|false the full player record (height, side, wage, agent…) */
    public function lookupPlayer(string $playerId): array|null|false
    {
        $players = $this->get('lookupplayer.php', ['id' => $playerId], 'players');

        return $players === false ? false : ($players[0] ?? null);
    }

    /** @return list<array<string, mixed>>|false the player's former teams (career path) */
    public function formerTeams(string $playerId): array|false
    {
        return $this->get('lookupformerteams.php', ['id' => $playerId], 'formerteams');
    }

    /** @return list<array<string, mixed>>|false the player's honours (title, team, season) */
    public function honours(string $playerId): array|false
    {
        return $this->get('lookuphonours.php', ['id' => $playerId], 'honours');
    }

    public function squad(string $teamId): array|false
    {
        return $this->get('lookup_all_players.php', ['id' => $teamId], 'player');
    }

    /**
     * @return array{teams: array<string, array{id: string, name: string, manager: ?string, squad_checked: ?string}>, players: array<string, array<string, mixed>>, candidates: array<string, array<string, ?string>>}
     */
    public static function loadCache(): array
    {
        $path = self::cachePath();
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : null;

        // Older caches were a flat "Name|dob" => url map with no club information, so they
        // cannot prove a kit is current; they are discarded.
        if (! isset($data['players'])) {
            return ['teams' => [], 'players' => [], 'candidates' => []];
        }

        // TheSportsDB mixes Unicode forms ("Fernández" as a + combining accent), which would
        // never match the same name typed normally: normalise every name and key to NFC.
        $players = [];
        foreach ($data['players'] as $key => $record) {
            if (isset($record['name'])) {
                $record['name'] = self::nfc($record['name']);
            }
            $players[self::nfc($key)] = $record;
        }
        $data['players'] = $players;

        return $data + ['teams' => [], 'candidates' => []];
    }

    public static function nfc(string $text): string
    {
        return class_exists(\Normalizer::class) ? (\Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text) : $text;
    }

    /** The cache file; tests point services.thesportsdb.cache at a fixture. */
    public static function cachePath(): string
    {
        return config('services.thesportsdb.cache') ?: self::CACHE_FILE;
    }

    public static function saveCache(array $cache): void
    {
        ksort($cache['players']);
        // Atomic: write a temp file and rename it, so a seeder reading concurrently never sees half a file.
        $path = self::cachePath();
        $tmp = $path.'.'.getmypid().'.tmp';
        file_put_contents($tmp, json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        rename($tmp, $path);
    }

    /** Unix time a TheSportsDB image was uploaded, from its file name (".../vtrddu1785612817.png"). */
    public static function uploadedAt(?string $url): ?int
    {
        return $url !== null && preg_match('/(\d{10})\.(png|jpe?g)$/i', $url, $m) ? (int) $m[1] : null;
    }

    /**
     * Some players' best search hit is their national-team profile (Saliba => "France"),
     * which says nothing about their club.
     */
    public static function isNationalTeamRecord(array $record): bool
    {
        $team = self::normalise($record['team'] ?? '');
        $nation = self::normalise(preg_replace('/^The /', '', $record['nationality'] ?? ''));

        return $team !== '' && $team === $nation;
    }

    public static function normalise(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;

        return strtolower(preg_replace('/[^a-z]/i', '', $ascii));
    }

    /** @return list<array<string, mixed>>|false */
    private function get(string $endpoint, array $query, string $key): array|false
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->throttle();

            try {
                $response = Http::timeout(15)->acceptJson()
                    ->get(sprintf(self::BASE, config('services.thesportsdb.key'), $endpoint), $query);
            } catch (ConnectionException $e) {
                $this->log("TheSportsDB unreachable: {$e->getMessage()}");

                return false;
            }

            if ($response->status() === 429) {
                // Slow down for the rest of the run as well as waiting this one out.
                $this->gapMs = min(10000, $this->gapMs + 1000);
                $this->log("Rate limited; waiting 60s (gap now {$this->gapMs}ms)…");
                sleep(60);

                continue;
            }

            if (! $response->successful()) {
                $this->log("HTTP {$response->status()} for {$endpoint} ".json_encode($query, JSON_UNESCAPED_UNICODE));

                return false;
            }

            return $response->json($key) ?? [];
        }

        return false;
    }

    private function throttle(): void
    {
        if ($this->lastRequestAt !== null) {
            $waitMs = $this->gapMs - (microtime(true) - $this->lastRequestAt) * 1000;
            if ($waitMs > 0) {
                usleep((int) ($waitMs * 1000));
            }
        }
        $this->lastRequestAt = microtime(true);
    }

    private function log(string $message): void
    {
        if ($this->log) {
            ($this->log)($message);
        }
    }
}
