<?php

namespace App\Support\Roster;

use Database\Seeders\Support\TheSportsDb;

/**
 * Decides which club, if any, each player is seeded into, from the local squad
 * lists and TheSportsDB's records (data/player_cutouts.json, written by
 * `php artisan football:sync-tsdb`). STRICT: a player is seeded only when
 * TheSportsDB confirms their current club is one of ours.
 *
 *   listed at A, TheSportsDB says A ............ VERIFIED, seeded at A
 *   listed at A, TheSportsDB says B (ours) ..... MOVED, seeded at B, never at A
 *   listed at A, TheSportsDB says another club,
 *     a national team only, nothing, or unchecked  BLOCKED, not seeded
 *   not listed, TheSportsDB places at A ........ ADDED (squad lookups / candidates)
 *
 * A club is matched by TheSportsDB team id, falling back to the alias dictionary
 * below when an id is unavailable. Each player is assigned at most once, by
 * TheSportsDB id when known and by (date of birth, normalised name) otherwise.
 */
class PlayerRosterVerifier
{
    /** A real first-team squad size. */
    public const MAX_SQUAD = 24;

    /** Overall for verified additions at a club with no listed players to average. */
    private const DEFAULT_OVERALL = 72;

    /** Our club name => other names the same club goes by (TheSportsDB, common usage). */
    public const CLUB_ALIASES = [
        'Arsenal' => ['Arsenal FC'],
        'Manchester City' => ['Man City', 'Manchester City FC'],
        'Manchester United' => ['Man United', 'Man Utd', 'Manchester United FC'],
        'Liverpool' => ['Liverpool FC'],
        'Chelsea' => ['Chelsea FC'],
        'Real Madrid' => ['Real Madrid CF'],
        'Barcelona' => ['FC Barcelona', 'Barça'],
        'Atlético Madrid' => ['Atletico Madrid', 'Atlético de Madrid', 'Club Atlético de Madrid'],
        'Inter' => ['Inter Milan', 'Internazionale', 'FC Internazionale Milano'],
        'Milan' => ['AC Milan'],
        'Juventus' => ['Juventus FC'],
        'Napoli' => ['SSC Napoli'],
        'Bayern Munich' => ['FC Bayern München', 'Bayern München', 'Bayern'],
        'Bayer Leverkusen' => ['Leverkusen', 'Bayer 04 Leverkusen'],
        'Borussia Dortmund' => ['Dortmund', 'BVB'],
        'Paris Saint-Germain' => ['PSG', 'Paris SG', 'Paris Saint Germain'],
        'Monaco' => ['AS Monaco'],
    ];

    /** @var list<array{level: string, message: string}> */
    private array $log = [];

    /**
     * @param  array<string, array{0: string, 1: string, 2: array<string, array{0: string, 1: list<array>}>}>  $leagues  FootballSeeder::LEAGUES
     * @param  array{teams: array, players: array}  $tsdb
     * @param  array{exclude?: array<string, string>, include?: array<string, list<string>>, shirt_numbers?: array<string, array<string, int>>}  $overrides
     *                                                                                                                                                       Human-reviewed corrections that win over TheSportsDB (football_profiles.php `roster_overrides`):
     *                                                                                                                                                       exclude  name => reason: never seeded anywhere (bad TheSportsDB data);
     *                                                                                                                                                       include  club code => names: listed players seeded at that club even when
     *                                                                                                                                                       TheSportsDB cannot confirm it (e.g. only a national-team record);
     *                                                                                                                                                       shirt_numbers  club code => [name => number].
     */
    public function __construct(private array $leagues, private array $tsdb, private array $overrides = []) {}

    /**
     * @return array<string, list<array<string, mixed>>> club code => verified members
     */
    public function squads(): array
    {
        $clubNames = [];
        $codeByTsdbId = [];
        foreach ($this->leagues as [, , $clubs]) {
            foreach ($clubs as $code => [$name]) {
                $clubNames[$code] = $name;
                if (isset($this->tsdb['teams'][$code]['id'])) {
                    $codeByTsdbId[$this->tsdb['teams'][$code]['id']] = $code;
                }
            }
        }
        $codeForRecord = function (array $record) use ($codeByTsdbId, $clubNames): ?string {
            if (isset($record['team_id'], $codeByTsdbId[$record['team_id']])) {
                return $codeByTsdbId[$record['team_id']];
            }

            return self::codeForClubName($record['team'] ?? '', $clubNames);
        };

        $squads = array_fill_keys(array_keys($clubNames), []);
        $assigned = [];   // identity => club code, so nobody is seeded twice
        $dobsByClub = []; // a club with two players born the same day is almost always one player under two spellings
        $listedKeys = [];
        $listedRows = [];
        $overalls = [];

        foreach ($this->leagues as [, , $clubs]) {
            foreach ($clubs as $listedCode => [$listedClub, $players]) {
                foreach ($players as [$first, $last, $position, $dob, $nation, $shirt, $overall]) {
                    $name = trim("{$first} {$last}");
                    $key = "{$name}|{$dob}";
                    $listedKeys[$key] = true;
                    $listedRows[$key] = compact('first', 'last', 'position', 'dob', 'nation', 'shirt', 'overall');
                    $record = $this->tsdb['players'][$key] ?? null;

                    if (isset($this->overrides['exclude'][$name])) {
                        $this->log('EXCLUDED', "{$name}: {$this->overrides['exclude'][$name]}");

                        continue;
                    }
                    if (in_array($name, $this->overrides['include'][$listedCode] ?? [], true)) {
                        $identity = isset($record['id']) ? "tsdb:{$record['id']}" : 'dob:'.$dob.':'.TheSportsDb::normalise($name);
                        $assigned[$identity] = $listedCode;
                        $dobsByClub[$listedCode][$dob] = true;
                        $squads[$listedCode][] = [
                            'first' => $first, 'last' => $last, 'position' => $position, 'dob' => $dob,
                            'nation_code' => $nation, 'nation_name' => null, 'shirt' => $shirt, 'overall' => $overall,
                            'tsdb' => $record, 'moved' => false, 'external_id' => $record['id'] ?? null, 'manual' => true,
                        ];
                        $overalls[$listedCode][] = $overall;
                        $this->log('MANUAL', "{$name} ({$listedClub}): manually verified, seeded without TheSportsDB confirmation");

                        continue;
                    }

                    $reason = match (true) {
                        $record === null => 'not yet checked against TheSportsDB (run football:sync-tsdb)',
                        ($record['found'] ?? false) !== true => 'not found on TheSportsDB',
                        TheSportsDb::isNationalTeamRecord($record) => "TheSportsDB only has a national-team record ({$record['team']}); club unverified",
                        default => null,
                    };
                    if ($reason !== null) {
                        $this->block("{$name}: listed at {$listedClub}, {$reason}");

                        continue;
                    }

                    $code = $codeForRecord($record);
                    if ($code === null) {
                        $this->block("{$name}: listed at {$listedClub}, but TheSportsDB says ".($record['team'] ?? 'no club').' (outside the five leagues / retired)');

                        continue;
                    }
                    if ($code !== $listedCode) {
                        $this->log('MOVED', "{$name}: listed at {$listedClub}, TheSportsDB says {$clubNames[$code]}; seeded at {$clubNames[$code]} only");
                    }

                    $identity = self::identity($record, $name, $dob);
                    if (isset($assigned[$identity])) {
                        $this->block("{$name}: duplicate of a player already assigned to {$clubNames[$assigned[$identity]]}");

                        continue;
                    }
                    $assigned[$identity] = $code;
                    $dobsByClub[$code][$dob] = true;
                    $squads[$code][] = [
                        'first' => $first, 'last' => $last, 'position' => $position, 'dob' => $dob,
                        'nation_code' => $nation, 'nation_name' => null,
                        'shirt' => $record['number'] ?? ($code === $listedCode ? $shirt : null),
                        'overall' => $overall, 'tsdb' => $record, 'moved' => $code !== $listedCode,
                        'external_id' => $record['id'] ?? null, 'manual' => false,
                    ];
                    $overalls[$code][] = $overall;
                }
            }
        }

        foreach ($this->tsdb['players'] as $key => $record) {
            if (isset($listedKeys[$key]) || ($record['found'] ?? false) !== true || TheSportsDb::isNationalTeamRecord($record)) {
                continue;
            }
            if (isset($this->overrides['exclude'][$record['name']])) {
                $this->log('EXCLUDED', "{$record['name']}: {$this->overrides['exclude'][$record['name']]}");

                continue;
            }
            $code = $codeForRecord($record);
            $category = self::category($record['position'] ?? null);
            if ($code === null || $category === null || empty($record['dob']) || ($record['status'] ?? 'Active') !== 'Active') {
                continue;
            }
            $identity = self::identity($record, $record['name'], $record['dob']);
            if (isset($assigned[$identity]) || isset($dobsByClub[$code][$record['dob']])) {
                continue; // same person as a listed player, spelt differently
            }
            if (count($squads[$code]) >= self::MAX_SQUAD) {
                $this->log('CAPPED', "{$record['name']} ({$clubNames[$code]}): verified, but the squad is full at ".self::MAX_SQUAD);

                continue;
            }
            $assigned[$identity] = $code;
            $dobsByClub[$code][$record['dob']] = true;
            [$first, $last] = self::splitName($record['name']);
            $squads[$code][] = [
                'first' => $first, 'last' => $last, 'position' => $category, 'dob' => $record['dob'],
                'nation_code' => null, 'nation_name' => $record['nationality'] ?? null, 'shirt' => $record['number'] ?? null,
                'overall' => isset($overalls[$code]) ? (int) round(array_sum($overalls[$code]) / count($overalls[$code])) - 3 : self::DEFAULT_OVERALL,
                'tsdb' => $record, 'moved' => false, 'external_id' => $record['id'] ?? null, 'manual' => false,
            ];
            $this->log('ADDED', "{$record['name']} ({$clubNames[$code]}): verified by TheSportsDB");
        }

        foreach ($squads as $code => $members) {
            if (count($members) > self::MAX_SQUAD) {
                $squads[$code] = array_slice($members, 0, self::MAX_SQUAD);
                $this->log('CAPPED', "{$clubNames[$code]}: ".(count($members) - self::MAX_SQUAD).' verified listed players over the cap of '.self::MAX_SQUAD);
            }
        }

        // Reviewed reassignments (roster_overrides.assign): "Name|dob" => club code. Used to
        // revert TheSportsDB-only transfers that contradict the confirmed baseline.
        foreach ($this->overrides['assign'] ?? [] as $key => $target) {
            [$name, $dob] = explode('|', TheSportsDb::nfc($key));
            if (! isset($squads[$target])) {
                continue;
            }
            foreach ($squads as $c => $members) {
                foreach ($members as $i => $m) {
                    if ($m['dob'] === $dob && TheSportsDb::normalise("{$m['first']} {$m['last']}") === TheSportsDb::normalise($name)) {
                        unset($squads[$c][$i]);
                        if ($c !== $target) {
                            $this->log('REVERTED', "{$name}: {$clubNames[$c]} → {$clubNames[$target]} (TheSportsDB-only move not confirmed)");
                        }
                        // Back at his confirmed club: no invented transfer, curated contract kept.
                        $squads[$target][] = ['moved' => false, 'manual' => true, 'shirt' => null] + $m;
                    }
                }
            }
        }

        // Squad locks (roster_overrides.lock): a confirmed real-world squad replaces whatever
        // was assigned, overriding TheSportsDB. Locked players are taken from any other club.
        foreach ($this->overrides['lock'] ?? [] as $code => $entries) {
            if (! isset($squads[$code])) {
                continue;
            }
            $locked = [];
            foreach ($entries as $key => $shirt) {
                [$name, $dob] = explode('|', TheSportsDb::nfc($key));
                $member = null;
                foreach ($squads as $c => $members) {
                    foreach ($members as $i => $m) {
                        if ($m['dob'] === $dob && TheSportsDb::normalise("{$m['first']} {$m['last']}") === TheSportsDb::normalise($name)) {
                            $member = $m;
                            unset($squads[$c][$i]);
                            if ($c !== $code) {
                                $this->log('LOCKED', "{$name}: moved from {$clubNames[$c]} to {$clubNames[$code]} (locked squad)");
                            }
                        }
                    }
                }
                if ($member === null) {
                    $member = $this->lockedMember($name, $dob, $listedRows["{$name}|{$dob}"] ?? null);
                    if ($member === null) {
                        // Never invent a player: without a listing or record we cannot seed them (the audit flags the short squad).
                        $this->log('BLOCKED', "{$name}: in {$clubNames[$code]}'s locked squad but has no squad listing or TheSportsDB record");

                        continue;
                    }
                    $this->log('LOCKED', "{$name}: added to {$clubNames[$code]} (locked squad)");
                }
                $locked[] = ['shirt' => $shirt, 'manual' => true, 'locked' => true] + $member;
            }
            foreach ($squads[$code] as $m) {
                $this->log('LOCKED', trim("{$m['first']} {$m['last']}").": removed from {$clubNames[$code]} (not in its locked squad)");
            }
            $squads[$code] = $locked;
        }
        $squads = array_map('array_values', $squads);

        // Reviewed shirt numbers win over TheSportsDB's (it sometimes carries a previous club's number).
        foreach ($squads as $code => &$members) {
            foreach ($members as &$member) {
                $number = $this->overrides['shirt_numbers'][$code][trim("{$member['first']} {$member['last']}")] ?? null;
                if ($number !== null && empty($member['locked'])) {
                    $member['shirt'] = $number;
                }
            }
            unset($member);
        }
        unset($members);

        return $squads;
    }

    /** A locked player not otherwise assigned: built from their squad listing, else their TheSportsDB record. */
    private function lockedMember(string $name, string $dob, ?array $listed): ?array
    {
        if ($listed !== null) {
            return [
                'first' => $listed['first'], 'last' => $listed['last'], 'position' => $listed['position'], 'dob' => $dob,
                'nation_code' => $listed['nation'], 'nation_name' => null, 'overall' => $listed['overall'],
                'tsdb' => $this->tsdb['players']["{$name}|{$dob}"] ?? null, 'moved' => false, 'external_id' => null,
            ];
        }
        $record = $this->tsdb['players']["{$name}|{$dob}"] ?? null;
        $category = self::category($record['position'] ?? null);
        if ($category === null) {
            return null;
        }
        [$first, $last] = self::splitName($name);

        return [
            'first' => $first, 'last' => $last, 'position' => $category, 'dob' => $dob,
            'nation_code' => null, 'nation_name' => $record['nationality'] ?? null, 'overall' => self::DEFAULT_OVERALL,
            'tsdb' => $record, 'moved' => false, 'external_id' => $record['id'] ?? null,
        ];
    }

    /** @return list<array{level: string, message: string}> */
    public function log(?string $level = null, ?string $message = null): array
    {
        if ($level !== null) {
            $this->log[] = ['level' => $level, 'message' => $message];
        }

        return $this->log;
    }

    private function block(string $message): void
    {
        $this->log('BLOCKED', $message);
    }

    /** Our club code for a club name, via the alias dictionary (accent/case-insensitive). */
    public static function codeForClubName(string $name, array $clubNames): ?string
    {
        $wanted = TheSportsDb::normalise($name);
        if ($wanted === '') {
            return null;
        }
        foreach ($clubNames as $code => $club) {
            foreach ([$club, ...(self::CLUB_ALIASES[$club] ?? [])] as $alias) {
                if (TheSportsDb::normalise($alias) === $wanted) {
                    return $code;
                }
            }
        }

        return null;
    }

    private static function identity(array $record, string $name, string $dob): string
    {
        return isset($record['id']) ? "tsdb:{$record['id']}" : 'dob:'.$dob.':'.TheSportsDb::normalise($name);
    }

    /** TheSportsDB position => our position category, or null for staff/unknown. */
    public static function category(?string $position): ?string
    {
        return match (true) {
            $position === null || str_contains($position, 'Coach') || $position === 'Manager' => null,
            str_contains($position, 'Goalkeeper') => 'Goalkeeper',
            str_contains($position, 'Back') || str_contains($position, 'Defender') || $position === 'Sweeper' => 'Defender',
            str_contains($position, 'Midfield') => 'Midfielder',
            str_contains($position, 'Wing') || str_contains($position, 'Forward') || str_contains($position, 'Striker') => 'Forward',
            default => null,
        };
    }

    /** "Ben White" => ["Ben", "White"]; "Estêvão" => ["", "Estêvão"]. */
    public static function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return count($parts) === 2 ? $parts : ['', $parts[0]];
    }
}
