<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\TeamPlayer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/*
 * How the seeders reconcile squads and cut-outs with TheSportsDB, driven by a small
 * fixture cache instead of the real data/player_cutouts.json.
 */

function tsdbRecord(string $name, string $dob, string $teamId, string $team, array $extra = []): array
{
    return [
        'found' => true, 'name' => $name, 'dob' => $dob, 'team_id' => $teamId, 'team' => $team,
        'status' => 'Active', 'number' => null, 'position' => null, 'nationality' => null, 'height' => null,
        'wage' => null, 'cutout' => null, 'render' => null, 'thumb' => null, 'checked' => '2026-11-16', ...$extra,
    ];
}

/** TheSportsDB-style image URL uploaded at $date. */
function cutoutAt(string $date): string
{
    return 'https://r2.thesportsdb.com/images/media/player/cutout/abcdef'.strtotime($date).'.png';
}

/**
 * Liverpool's locked squad (roster_overrides.lock) includes players who aren't in the
 * seeder's own squad listings, so the seed builds them from their TheSportsDB records:
 * position and nationality as in data/player_cutouts.json. Liverpool isn't one of the
 * fixture's clubs, so these records don't change the ARS / MCI / BAR scenarios below.
 */
function lockedLiverpoolRecords(): array
{
    $records = [];
    foreach ([
        ['Giorgi Mamardashvili', '2000-09-29', 'Goalkeeper', 'Georgia'],
        ['Ibrahima Konaté', '1999-05-25', 'Centre-Back', 'France'],
        ['Joe Gomez', '1997-05-23', 'Centre-Back', 'England'],
        ['Giovanni Leoni', '2006-12-21', 'Defender', 'Italy'],
        ['Andrew Robertson', '1994-03-11', 'Left-Back', 'Scotland'],
        ['Milos Kerkez', '2003-11-07', 'Left-Back', 'Hungary'],
        ['Kostas Tsimikas', '1996-05-12', 'Left-Back', 'Greece'],
        ['Conor Bradley', '2003-07-09', 'Right-Back', 'Northern Ireland'],
        ['Jeremie Frimpong', '2000-12-10', 'Defender', 'The Netherlands'],
        ['Dominik Szoboszlai', '2000-10-25', 'Central Midfield', 'Hungary'],
        ['Ryan Gravenberch', '2002-05-16', 'Central Midfield', 'The Netherlands'],
        ['Curtis Jones', '2001-01-30', 'Central Midfield', 'England'],
        ['Wataru Endo', '1993-02-09', 'Defensive Midfield', 'Japan'],
        ['Cody Gakpo', '1999-05-07', 'Left Wing', 'The Netherlands'],
        ['Federico Chiesa', '1997-10-25', 'Left Wing', 'Italy'],
        ['Alexander Isak', '1999-09-21', 'Centre-Forward', 'Sweden'],
        ['Hugo Ekitiké', '2002-06-20', 'Centre-Forward', 'France'],
    ] as [$name, $dob, $position, $nationality]) {
        $records["{$name}|{$dob}"] = tsdbRecord($name, $dob, '133602', 'Liverpool', ['position' => $position, 'nationality' => $nationality]);
    }

    return $records;
}

beforeEach(function () {
    Http::preventStrayRequests();

    $fixture = [
        'teams' => [
            'ARS' => ['id' => '1', 'name' => 'Arsenal', 'manager' => null, 'squad_checked' => '2026-11-16'],
            'MCI' => ['id' => '2', 'name' => 'Manchester City', 'manager' => 'Test Manager', 'squad_checked' => '2026-11-16'],
            'BAR' => ['id' => '3', 'name' => 'Barcelona', 'manager' => null, 'squad_checked' => '2026-11-16'],
        ],
        'players' => [
            // Left for a club outside the database: dropped.
            'Robert Lewandowski|1988-08-21' => tsdbRecord('Robert Lewandowski', '1988-08-21', '999', 'Chicago Fire', ['cutout' => cutoutAt('2026-08-01')]),
            // Moved between clubs in the database, no reviewed override: follows TheSportsDB.
            'Thibaut Courtois|1992-05-11' => tsdbRecord('Thibaut Courtois', '1992-05-11', '3', 'Barcelona', ['wage' => '€10,400,000']),
            // TheSportsDB-only move contradicted by roster_overrides.assign: stays at City. His
            // cut-out (City kit) passed the reviewed kit check (roster_overrides.accept_cutouts).
            'Rodri|1996-06-22' => tsdbRecord('Rodri', '1996-06-22', '3', 'Barcelona', ['cutout' => cutoutAt('2026-09-01'), 'wage' => '€10,400,000']),
            // Best hit is the national-team profile: proves nothing about the club.
            'William Saliba|2001-03-24' => tsdbRecord('William Saliba', '2001-03-24', '77', 'France', ['nationality' => 'France', 'cutout' => cutoutAt('2026-06-01')]),
            // Verified, recent upload.
            'Bukayo Saka|2001-09-05' => tsdbRecord('Bukayo Saka', '2001-09-05', '1', 'Arsenal', ['cutout' => cutoutAt('2026-09-05')]),
            // Verified club, but the cut-out predates his July 2023 move (West Ham shirt).
            'Declan Rice|1999-01-14' => tsdbRecord('Declan Rice', '1999-01-14', '1', 'Arsenal', ['cutout' => cutoutAt('2022-05-01')]),
            // Cut-out predates the move, but a newer render exists.
            'Martin Ødegaard|1998-12-17' => tsdbRecord('Martin Ødegaard', '1998-12-17', '1', 'Arsenal', [
                'cutout' => cutoutAt('2020-01-01'), 'render' => cutoutAt('2025-01-01'),
            ]),
            // Verified addition from a squad lookup.
            'Test Fullback|1997-11-08' => tsdbRecord('Test Fullback', '1997-11-08', '3', 'Barcelona', [
                'position' => 'Right-Back', 'nationality' => 'England', 'number' => 4, 'height' => '186 cm', 'wage' => '£6,240,000',
            ]),
            // TheSportsDB says Arsenal, but roster_overrides.exclude rules him out.
            'Ezri Konsa|1997-06-06' => tsdbRecord('Ezri Konsa', '1997-06-06', '1', 'Arsenal', ['position' => 'Centre-Back', 'number' => 15]),
            // Staff are never added.
            'Test Assistant|1970-08-05' => tsdbRecord('Test Assistant', '1970-08-05', '3', 'Barcelona', ['position' => 'Assistant Coach']),
            ...lockedLiverpoolRecords(),
        ],
        'candidates' => [],
    ];

    $path = tempnam(sys_get_temp_dir(), 'tsdb').'.json';
    file_put_contents($path, json_encode($fixture));
    config(['services.thesportsdb.cache' => $path]);

    $this->seed();
});

function squadOf(string $code): array
{
    return Team::where('short_code', $code)->firstOrFail()->currentPlayers()->get()
        ->map(fn ($p) => trim("{$p->first_name} {$p->last_name}"))->all();
}

test('players TheSportsDB places outside the database are dropped', function () {
    expect(squadOf('BAR'))->not->toContain('Robert Lewandowski')
        ->and(Player::where('last_name', 'Lewandowski')->exists())->toBeFalse();
});

test('players who moved between clubs follow TheSportsDB, with old contract terms cleared', function () {
    // Between unlocked clubs (locked squads are covered separately).
    expect(squadOf('BAR'))->toContain('Thibaut Courtois')
        ->and(squadOf('RMA'))->not->toContain('Thibaut Courtois');

    $courtois = Player::where('last_name', 'Courtois')->firstOrFail();
    expect(collect($courtois->transfer_history)->last())->toBe(['date' => null, 'from' => 'Real Madrid', 'to' => 'Barcelona', 'fee' => null])
        ->and($courtois->contract_expires)->toBeNull()     // his Real Madrid contract is dropped; no reviewed Barcelona terms
        ->and($courtois->release_clause)->toBe('€200.00m')   // not his €1bn Real Madrid clause: a labelled La Liga estimate
        ->and($courtois->profile_estimates)->toContain('release_clause')
        ->and($courtois->weekly_salary)->toBe('€200k');    // TheSportsDB wage: €10.4m a year
});

test('a reviewed reassignment wins over a TheSportsDB-only move', function () {
    expect(squadOf('MCI'))->toContain('Rodri')
        ->and(squadOf('BAR'))->not->toContain('Rodri');

    $rodri = Player::where('last_name', 'Rodri')->firstOrFail();
    expect(collect($rodri->transfer_history)->pluck('to')->all())->not->toContain('Barcelona') // no invented transfer
        ->and($rodri->contract_expires?->toDateString())->toBe('2027-06-30');                    // curated City contract kept
});

test('strict mode blocks players TheSportsDB cannot place at one of our clubs', function () {
    // Never checked against TheSportsDB, not manually reviewed, and at an unlocked club.
    expect(squadOf('RMA'))->not->toContain('Kylian Mbappé')
        ->and(Player::where('last_name', 'Mbappé')->exists())->toBeFalse();
});

test('manual review wins over TheSportsDB: inclusions are seeded, exclusions never are', function () {
    // Saliba: only a national-team record, but in roster_overrides.include for Arsenal —
    // seeded, and his real (France-kit) photo shown, labelled as another kit.
    $saliba = Player::where('last_name', 'Saliba')->firstOrFail();
    expect(squadOf('ARS'))->toContain('William Saliba')
        ->and($saliba->cutout_image_url)->toBe(cutoutAt('2026-06-01'))
        ->and($saliba->cutout_source)->toBe('other_kit')
        // Konsa: TheSportsDB says Arsenal, roster_overrides.exclude says no.
        ->and(squadOf('ARS'))->not->toContain('Ezri Konsa')
        ->and(Player::where('last_name', 'Konsa')->exists())->toBeFalse();
});

test('reviewed shirt numbers win over TheSportsDB numbers', function () {
    // The fixture gives Test Fullback #4 from TheSportsDB; Saka has none there.
    $number = fn (string $last) => Player::where('last_name', $last)->firstOrFail()->currentTeams->first()->pivot->shirt_number;

    expect($number('Saka'))->toBe(7)->and($number('Saliba'))->toBe(2);
});

test('the database rejects a second row for the same player and a second current club', function () {
    $saka = Player::where('last_name', 'Saka')->firstOrFail();

    expect(fn () => Player::create(['first_name' => 'Bukayo', 'last_name' => 'Saka', 'date_of_birth' => '2001-09-05']))
        ->toThrow(UniqueConstraintViolationException::class);

    $chelsea = Team::where('short_code', 'CHE')->firstOrFail();
    expect(fn () => TeamPlayer::create(['team_id' => $chelsea->id, 'player_id' => $saka->id, 'season' => '2026/27', 'is_current' => true]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('roster:audit confirms club matches and flags squads too small to field a team', function () {
    $exit = Artisan::call('roster:audit');
    $output = Artisan::output();

    expect($exit)->toBe(1) // the fixture squads are tiny
        ->and($output)->toMatch('/Every player club-matched \(TheSportsDB or manual review\)\s*\|\s*PASS/')
        ->and($output)->toMatch('/No manually excluded players present\s*\|\s*PASS/')
        ->and($output)->toMatch('/No duplicate players.*\|\s*PASS/')
        ->and($output)->toMatch('/at most one current club\s*\|\s*PASS/')
        ->and($output)->toMatch('/has 18–24 verified players\s*\|\s*FAIL/');
});

test('current-kit photos are preferred; otherwise the real photo in another kit is shown and labelled', function () {
    $url = fn (string $last) => Player::where('last_name', $last)->firstOrFail()->cutout_image_url;

    expect($url('Saka'))->toBe(cutoutAt('2026-09-05'))
        ->and($url('Rodri'))->toBe(cutoutAt('2026-09-01'))              // TheSportsDB's club disagrees, but the reviewed kit check accepts it
        ->and($url('Rice'))->toBe(cutoutAt('2022-05-01'))              // uploaded before he joined Arsenal: other kit, still his real photo
        ->and(Player::where('last_name', 'Rice')->value('cutout_source'))->toBe('other_kit')
        ->and(Player::where('last_name', 'Saka')->value('cutout_source'))->toBe('current_kit')
        ->and($url('Ødegaard'))->toBe(cutoutAt('2025-01-01'));        // falls through to the newer render
});

test('the fallback, for a player with no photo at all, is a jersey in club colours with the squad number', function () {
    $fullback = Player::where('last_name', 'Fullback')->firstOrFail(); // TheSportsDB record has no image
    $svg = rawurldecode($fullback->cutout_image_url);

    expect($svg)->toContain('fill="#A50044"')->toContain('>4</text>') // Barcelona colours
        ->and($fullback->photo_url)->toBeNull()
        ->and($fullback->cutout_source)->toBe('fallback');
});

test('verified additions join the squad with TheSportsDB facts and nothing invented', function () {
    expect(squadOf('BAR'))->toContain('Test Fullback')->not->toContain('Test Assistant');

    $added = Player::where('last_name', 'Fullback')->firstOrFail();
    expect($added->only(['primary_position', 'height_cm', 'shirt_number', 'weekly_salary', 'nationality']))->toBe([
        'primary_position' => 'Right-Back', 'height_cm' => 186, 'shirt_number' => 4, 'weekly_salary' => '€140k', 'nationality' => 'England',
    ])
        ->and($added->market_value)->toBeNull()
        ->and($added->contract_expires)->toBeNull()
        ->and($added->intl_caps)->toBeNull();
});

test('a manager listed by TheSportsDB replaces the curated coach', function () {
    expect(Team::where('short_code', 'MCI')->value('coach_name'))->toBe('Test Manager')
        ->and(Team::where('short_code', 'ARS')->value('coach_name'))->toBe('Mikel Arteta');
});

test('a locked squad replaces TheSportsDB entirely, including its additions', function () {
    // TheSportsDB-style addition at Arsenal would join an unlocked club, but Arsenal is locked.
    expect(squadOf('ARS'))->not->toContain('Test Fullback')
        ->and(squadOf('ARS'))->toContain('Bukayo Saka');
});
