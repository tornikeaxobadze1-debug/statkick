<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Player;
use App\Models\TeamPlayer;
use App\Support\Roster\PlayerDataValidator;
use App\Support\Roster\PlayerRosterVerifier;
use Database\Seeders\FootballSeeder;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Console\Command;

/**
 * Audits the seeded database against TheSportsDB's verified records
 * (data/player_cutouts.json): squad sizes per club, club-match confirmation for
 * every player, duplicates, one-current-club, bounds, and the league countries.
 * Exits non-zero when any check fails.
 */
class RosterAudit extends Command
{
    protected $signature = 'roster:audit {--details : List every failing player}';

    protected $description = 'Audit club rosters: verified player counts, duplicates and club-match confirmation';

    /** Fewer verified players than this cannot field a team. */
    /** A realistic first-team squad. */
    private const MIN_SQUAD = 18;

    private const MAX_SQUAD = 24;

    private const EXPECTED_COUNTRIES = ['England', 'France', 'Germany', 'Italy', 'Spain'];

    public function handle(): int
    {
        $tsdb = TheSportsDb::loadCache();
        $overrides = (require database_path('seeders/data/football_profiles.php'))['roster_overrides'] ?? [];
        $failures = 0;

        // ---- Clubs ----------------------------------------------------------------
        $rows = [];
        $mismatches = [];
        $shortSquads = [];
        $wrongClubIds = [];
        $totals = ['players' => 0, 'verified' => 0, 'manual' => 0, 'complete' => 0, 'cutouts' => 0];
        $countries = Country::whereHas('divisions')->with('divisions.teams.currentPlayers')->orderBy('name')->get();

        foreach ($countries as $country) {
            foreach ($country->divisions as $division) {
                foreach ($division->teams->sortBy('name') as $team) {
                    $clubId = $tsdb['teams'][$team->short_code]['id'] ?? null;
                    $tsdbName = $tsdb['teams'][$team->short_code]['name'] ?? '';
                    if (PlayerRosterVerifier::codeForClubName($tsdbName, [$team->short_code => $team->name]) === null) {
                        $wrongClubIds[] = "{$team->name} => ".($tsdbName ?: 'unresolved');
                    }
                    $players = $team->currentPlayers;
                    // Manually confirmed: reviewed inclusions, reviewed reassignments, and the locked squad.
                    $manualNames = [...($overrides['include'][$team->short_code] ?? []),
                        ...array_map(fn ($k) => TheSportsDb::nfc(explode('|', $k)[0]), array_keys(array_filter($overrides['assign'] ?? [], fn ($code) => $code === $team->short_code))),
                        ...array_map(fn ($k) => TheSportsDb::nfc(explode('|', $k)[0]), array_keys($overrides['lock'][$team->short_code] ?? []))];
                    $manual = $players->filter(fn (Player $p) => in_array(trim("{$p->first_name} {$p->last_name}"), $manualNames, true))->count();
                    $verified = $players->filter(function (Player $p) use ($tsdb, $clubId, $team, $manualNames, &$mismatches) {
                        if (in_array(trim("{$p->first_name} {$p->last_name}"), $manualNames, true)) {
                            return true; // manually verified (roster_overrides.include)
                        }
                        $record = $tsdb['players'][trim("{$p->first_name} {$p->last_name}").'|'.$p->date_of_birth?->toDateString()] ?? null;
                        $ok = ($record['found'] ?? false) === true && ! TheSportsDb::isNationalTeamRecord($record)
                            && (($clubId !== null && ($record['team_id'] ?? null) === $clubId)
                                || PlayerRosterVerifier::codeForClubName($record['team'] ?? '', [$team->short_code => $team->name]) !== null);
                        if (! $ok) {
                            $mismatches[] = sprintf('%s (%s): TheSportsDB says %s', trim("{$p->first_name} {$p->last_name}"), $team->name, $record['team'] ?? 'nothing');
                        }

                        return $ok;
                    })->count();
                    // Real photos (remote or local copy) vs the club-colour jersey placeholder.
                    $cutouts = $players->filter(fn ($p) => ! str_starts_with((string) $p->cutout_image_url, 'data:'))->count();
                    $otherKit = $players->where('cutout_source', 'other_kit')->count();
                    // Complete = squad number, market value and contract expiry all known.
                    $complete = $players->filter(fn ($p) => $p->pivot->shirt_number !== null && $p->market_value !== null && $p->contract_expires !== null)->count();

                    $rows[] = [
                        $country->name, $team->name, $players->count(), $verified - $manual, $manual,
                        $players->count() ? sprintf('%d%%', round($verified / $players->count() * 100)) : '—',
                        "{$complete}/{$players->count()}", $cutouts, $otherKit, $players->count() - $cutouts,
                    ];
                    if ($verified < self::MIN_SQUAD || $players->count() > self::MAX_SQUAD) {
                        $shortSquads[] = "{$team->name} ({$verified})";
                    }
                    $totals['players'] += $players->count();
                    $totals['verified'] += $verified;
                    $totals['manual'] += $manual;
                    $totals['complete'] += $complete;
                    $totals['cutouts'] += $cutouts;
                    $totals['other_kit'] = ($totals['other_kit'] ?? 0) + $otherKit;
                }
            }
        }
        $rows[] = ['TOTAL', "{$countries->count()} countries, ".count($rows).' clubs', $totals['players'], $totals['verified'] - $totals['manual'], $totals['manual'],
            $totals['players'] ? sprintf('%d%%', round($totals['verified'] / $totals['players'] * 100)) : '—',
            "{$totals['complete']}/{$totals['players']}", $totals['cutouts'], $totals['other_kit'] ?? 0, $totals['players'] - $totals['cutouts']];

        $this->newLine();
        $this->info('ROSTER AUDIT — clubs');
        $this->table(['Country', 'Club', 'Players', 'TheSportsDB-verified', 'Manually verified', 'Match', 'Complete (no./value/contract)', 'Real photos', '…of which other kit', 'Jersey fallbacks'], $rows);

        // ---- Integrity checks ---------------------------------------------------------
        $players = Player::with('currentTeams')->get();
        $dupNames = $players->groupBy(fn ($p) => TheSportsDb::normalise("{$p->first_name}{$p->last_name}").'|'.$p->date_of_birth?->toDateString())->filter(fn ($g) => $g->count() > 1);
        $dupIds = $players->whereNotNull('external_api_id')->groupBy('external_api_id')->filter(fn ($g) => $g->count() > 1);
        // The same name at two clubs (whatever the date of birth) is a duplicate or a misplacement.
        $nameClubs = [];
        foreach ($players as $p) {
            foreach ($p->currentTeams as $t) {
                $nameClubs[TheSportsDb::normalise("{$p->first_name}{$p->last_name}")][$t->id] = trim("{$p->first_name} {$p->last_name}");
            }
        }
        $namesInTwoClubs = array_values(array_map(fn ($clubs) => reset($clubs), array_filter($nameClubs, fn ($clubs) => count($clubs) > 1)));
        $multiClub = TeamPlayer::where('is_current', true)->selectRaw('player_id, count(*) as n')->groupBy('player_id')->having('n', '>', 1)->count();
        $clubless = $players->filter(fn ($p) => $p->currentTeams->isEmpty());
        $invalid = [];
        foreach ($players as $p) {
            [, $problems] = PlayerDataValidator::clean([
                ...$p->only(['position', 'age', 'height_cm', 'market_value', 'highest_market_value', 'weekly_salary',
                    'annual_salary', 'avg_rating', 'pass_accuracy_pct', 'dribble_success_pct', 'aerial_duels_won_pct',
                    'intl_caps', 'intl_goals']),
                'shirt_number' => $p->currentTeams->first()?->pivot->shirt_number,
            ]);
            foreach ($problems as $problem) {
                $invalid[] = trim("{$p->first_name} {$p->last_name}").": {$problem}";
            }
        }
        $countryNames = $countries->pluck('name')->sort()->values()->all();
        $excludedPresent = $players->filter(fn ($p) => isset($overrides['exclude'][trim("{$p->first_name} {$p->last_name}")]))
            ->map(fn ($p) => trim("{$p->first_name} {$p->last_name}"))->values()->all();

        $shape = [];
        $estimatedFields = 0;
        $playersWithEstimates = 0;
        $lineups = [];
        $numbering = [];
        $incomplete = [];
        $photoProblems = [];
        $rejected = (require database_path('seeders/data/football_profiles.php'))['roster_overrides']['reject_cutouts'] ?? [];
        $urlOwners = [];
        foreach ($countries as $country) {
            foreach ($country->divisions as $division) {
                foreach ($division->teams as $team) {
                    $squad = $team->currentPlayers;
                    $byLine = $squad->countBy('position');
                    foreach (['Goalkeeper' => 2, 'Defender' => 4, 'Midfielder' => 4, 'Forward' => 2] as $line => $min) {
                        if (($byLine[$line] ?? 0) < $min) {
                            $shape[] = "{$team->name}: ".($byLine[$line] ?? 0)." {$line}s (min {$min})";
                        }
                    }

                    $starters = $squad->filter(fn ($p) => $p->pivot->is_starter);
                    $expected = FootballSeeder::formationLines($team->preferred_formation);
                    $startersByLine = $starters->countBy('position');
                    if ($starters->count() !== 11 || ($startersByLine['Goalkeeper'] ?? 0) !== 1) {
                        $lineups[] = "{$team->name}: {$starters->count()} starters, ".($startersByLine['Goalkeeper'] ?? 0).' GK';
                    } elseif (($startersByLine['Defender'] ?? 0) !== $expected['Defender']
                        || ($startersByLine['Midfielder'] ?? 0) + ($startersByLine['Forward'] ?? 0) !== $expected['Midfielder'] + $expected['Forward']) {
                        // Defence must match exactly; midfield and attack are one block (wingers count as forwards).
                        $lineups[] = "{$team->name}: XI ".json_encode($startersByLine->all())." does not match {$team->preferred_formation}";
                    }

                    $numbers = $squad->pluck('pivot.shirt_number');
                    if ($numbers->contains(null) || $numbers->duplicates()->isNotEmpty()) {
                        $numbering[] = "{$team->name}: ".$numbers->filter(fn ($x) => $x === null)->count().' missing, duplicates '.json_encode($numbers->duplicates()->values());
                    }

                    foreach ($squad as $p) {
                        $name = trim("{$p->first_name} {$p->last_name}");
                        // Null, empty or "Undisclosed" all count as missing.
                        $absent = fn ($v) => blank($v) || (is_string($v) && strcasecmp(trim($v), 'Undisclosed') === 0);
                        $missing = array_keys(array_filter([
                            'shirt' => $p->pivot->shirt_number === null, 'display_name' => $absent($p->display_name),
                            'market_value' => $absent($p->market_value), 'highest_value' => $absent($p->highest_market_value),
                            'weekly_salary' => $absent($p->weekly_salary), 'annual_salary' => $absent($p->annual_salary),
                            'release_clause' => $absent($p->release_clause), 'contract' => $p->contract_expires === null,
                            'agent' => $absent($p->agent_name), 'height' => $p->height_cm === null,
                            'foot' => $absent($p->preferred_foot), 'nationality' => $absent($p->nationality), 'age' => $p->age === null,
                            'tactical_role' => $absent($p->playing_style), 'position' => $absent($p->primary_position),
                        ]));
                        // Salary consistency: annual = weekly × 52 (to the cent of the displayed €m).
                        $weekly = PlayerDataValidator::money($p->weekly_salary);
                        $annual = PlayerDataValidator::money($p->annual_salary);
                        if ($weekly !== null && $annual !== null && abs(round($weekly * 52, 2) - $annual) > 0.005) {
                            $missing[] = "annual_salary {$p->annual_salary} ≠ weekly {$p->weekly_salary} × 52";
                        }
                        $value = PlayerDataValidator::money($p->market_value);
                        $peak = PlayerDataValidator::money($p->highest_market_value);
                        if ($value !== null && $peak !== null && $peak < $value) {
                            $missing[] = "highest_value {$p->highest_market_value} below current {$p->market_value}";
                        }
                        $estimatedFields += count($p->profile_estimates ?? []);
                        $playersWithEstimates += empty($p->profile_estimates) ? 0 : 1;
                        if ($missing !== []) {
                            $incomplete[] = "{$name} ({$team->name}): ".implode(', ', $missing);
                        }
                        $url = (string) $p->cutout_image_url;
                        $isPhoto = $url !== '' && ! str_starts_with($url, 'data:');
                        if (isset($rejected[$name]) && $isPhoto) {
                            $photoProblems[] = "{$name}: rejected cut-out still in use";
                        }
                        if ($isPhoto) {
                            if (isset($urlOwners[$url])) {
                                $photoProblems[] = "{$name} and {$urlOwners[$url]} share one image";
                            }
                            $urlOwners[$url] = $name;
                        } elseif (! str_starts_with($url, 'data:image/svg+xml')) {
                            $photoProblems[] = "{$name}: no image and no fallback";
                        }
                    }
                }
            }
        }

        $checks = [
            ['Countries with leagues are exactly the top five', $countryNames === self::EXPECTED_COUNTRIES, implode(', ', $countryNames)],
            ['No duplicate players (name + date of birth)', $dupNames->isEmpty(), $dupNames->count().' duplicates'],
            ['No duplicate TheSportsDB ids', $dupIds->isEmpty(), $dupIds->count().' duplicates ('.$players->whereNotNull('external_api_id')->count().' players have an id)'],
            ['No player name appears at more than one club', $namesInTwoClubs === [], $namesInTwoClubs === [] ? '0 names' : implode(', ', $namesInTwoClubs)],
            ['Every player has at most one current club', $multiClub === 0, "{$multiClub} players in 2+ clubs"],
            ['Every player has a current club', $clubless->isEmpty(), $clubless->count().' without'],
            ['Every club has '.self::MIN_SQUAD.'–'.self::MAX_SQUAD.' verified players', $shortSquads === [], $shortSquads === [] ? 'all clubs' : implode(', ', $shortSquads)],
            ['Every club id resolves to that club', $wrongClubIds === [], $wrongClubIds === [] ? 'all clubs' : implode(', ', $wrongClubIds)],
            ['Every player club-matched (TheSportsDB or manual review)', $mismatches === [], count($mismatches).' unconfirmed'],
            ['No manually excluded players present', $excludedPresent === [], $excludedPresent === [] ? count($overrides['exclude'] ?? []).' excluded, none present' : implode(', ', $excludedPresent)],
            ['All values within realistic bounds', $invalid === [], count($invalid).' violations'],
            ['Every squad covers GK/DEF/MID/FWD (2/4/4/2 minimum)', $shape === [], $shape === [] ? 'all clubs' : implode('; ', $shape)],
            ['Starting XI: exactly 11, 1 GK, matching the formation', $lineups === [], $lineups === [] ? 'all clubs' : implode('; ', $lineups)],
            ['Shirt numbers present and unique within each club', $numbering === [], $numbering === [] ? 'all clubs' : implode('; ', $numbering)],
            ['Every 360° profile complete (no., name, value, peak, salary, clause, contract, agent, height, foot, position, role)', $incomplete === [], count($incomplete).' incomplete'],
            ['Salaries consistent (annual = weekly × 52) and peak ≥ current value', ! collect($incomplete)->contains(fn ($l) => str_contains($l, '≠') || str_contains($l, 'below current')), 'checked for every player'],
            ['Photos: no rejected or shared cut-outs; fallback otherwise', $photoProblems === [], $photoProblems === [] ? 'all players' : implode('; ', array_slice($photoProblems, 0, 5))],
        ];
        $this->info('ROSTER AUDIT — integrity checks');
        $this->table(['Check', 'Result', 'Detail'], array_map(fn ($c) => [$c[0], $c[1] ? 'PASS' : 'FAIL', $c[2]], $checks));
        $failures = collect($checks)->where(1, false)->count();

        if ($this->option('details')) {
            foreach ([...$mismatches, ...$invalid, ...$incomplete, ...$photoProblems] as $line) {
                $this->line("  - {$line}");
            }
        }

        $this->line("Estimates: {$estimatedFields} profile values across {$playersWithEstimates} players are labelled rule-based estimates (players.profile_estimates).");
        $this->line('Provenance: rosters, clubs, cut-outs and TheSportsDB facts are verified; curated profile facts are');
        $this->line('hand-compiled (approximate); appearances, goals, ratings, xG and heatmaps come from SIMULATED fixtures.');

        if ($failures > 0) {
            $this->error("{$failures} check(s) failed.");

            return self::FAILURE;
        }
        $this->info('All checks passed.');

        return self::SUCCESS;
    }
}
