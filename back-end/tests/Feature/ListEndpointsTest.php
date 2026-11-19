<?php

use App\Models\Division;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerStat;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Support\Roster\PlayerDataValidator;
use Database\Seeders\FootballSeeder;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => $this->seed());

/** Standings line for a team, recomputed independently from its finished matches. */
function expectedRecord(Team $team): array
{
    $r = ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0, 'clean_sheets' => 0, 'points' => 0];
    $matches = GameMatch::where('status', 'FINISHED')
        ->where(fn ($q) => $q->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id))
        ->get();

    foreach ($matches as $m) {
        $home = $m->home_team_id === $team->id;
        $for = $home ? $m->home_score : $m->away_score;
        $against = $home ? $m->away_score : $m->home_score;
        $r['played']++;
        $r['goals_for'] += $for;
        $r['goals_against'] += $against;
        $r['clean_sheets'] += $against === 0 ? 1 : 0;
        $r[$for > $against ? 'wins' : ($for === $against ? 'draws' : 'losses')]++;
        $r['points'] += $for > $against ? 3 : ($for === $against ? 1 : 0);
    }

    return $r;
}

test('GET /api/teams returns every team with a record matching its finished matches', function () {
    $response = $this->getJson('/api/teams')->assertOk();

    expect($response->json())->toHaveCount(Team::count());

    foreach ($response->json() as $row) {
        expect($row['record'])->toEqual(expectedRecord(Team::findOrFail($row['id'])))
            ->and(count($row['form']))->toBe(min(5, $row['record']['played']));
    }
});

test('GET /api/teams form is newest first', function () {
    $team = Team::where('short_code', 'ARS')->firstOrFail();
    $latest = GameMatch::where('status', 'FINISHED')
        ->where(fn ($q) => $q->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id))
        ->orderByDesc('match_date')
        ->firstOrFail();
    $for = $latest->home_team_id === $team->id ? $latest->home_score : $latest->away_score;
    $against = $latest->home_team_id === $team->id ? $latest->away_score : $latest->home_score;

    $row = collect($this->getJson('/api/teams')->json())->firstWhere('id', $team->id);

    expect($row['form'][0])->toBe($for > $against ? 'W' : ($for === $against ? 'D' : 'L'));
});

test('GET /api/players returns players with current team and totals matching their stats', function () {
    $response = $this->getJson('/api/players')->assertOk();

    expect($response->json())->toHaveCount(Player::count());

    $saka = collect($response->json())->firstWhere('last_name', 'Saka');
    $stats = PlayerStat::where('player_id', $saka['id'])->get();

    expect($saka['current_team']['short_code'])->toBe('ARS')
        ->and($saka['country']['code'])->toBe('ENG')
        ->and($saka['totals'])->toMatchArray([
            'appearances' => $stats->count(),
            'goals' => $stats->sum('goals'),
            'assists' => $stats->sum('assists'),
            'minutes_played' => $stats->sum('minutes_played'),
        ])
        ->and($saka['totals']['avg_rating'])->toEqualWithDelta($stats->avg('rating'), 0.01);
});

test('GET /api/players lists rated players before unrated ones, best first', function () {
    $ratings = collect($this->getJson('/api/players')->json())->pluck('totals.avg_rating');

    $firstNull = $ratings->search(null, true);

    expect($firstNull === false || $ratings->slice($firstNull)->every(fn ($r) => $r === null))->toBeTrue()
        ->and($ratings->filter()->values()->all())->toBe($ratings->filter()->sortDesc()->values()->all());
});

test('GET /api/players?team_id= returns only that team\'s current squad', function () {
    $arsenal = Team::where('short_code', 'ARS')->firstOrFail();

    $codes = collect($this->getJson("/api/players?team_id={$arsenal->id}")->assertOk()->json())
        ->pluck('current_team.short_code')
        ->unique()
        ->all();

    expect($codes)->toBe(['ARS']);
});

test('GET /api/teams?division_id= returns only that league\'s teams', function () {
    $arsenal = Team::where('short_code', 'ARS')->firstOrFail();

    $divisions = collect($this->getJson("/api/teams?division_id={$arsenal->division_id}")->assertOk()->json())
        ->pluck('division_id')
        ->unique()
        ->all();

    expect($divisions)->toBe([$arsenal->division_id]);
});

test('detail endpoints run a fixed set of queries, none repeated', function (string $url, int $expected) {
    $url = str_replace(
        ['{league}', '{player}'],
        [(string) Division::where('name', 'Premier League')->value('id'), (string) Player::where('last_name', 'Saka')->value('id')],
        $url,
    );
    $queries = [];
    DB::listen(function ($e) use (&$queries) {
        $queries[] = $e->sql;
    });

    $this->getJson($url)->assertOk();

    expect($queries)->toHaveCount($expected)
        ->and(array_unique($queries))->toHaveCount(count($queries));
})->with([
    'league' => ['/api/leagues/{league}', 4],
    'league via /divisions' => ['/api/divisions/{league}', 4],
    'player' => ['/api/players/{player}', 8],
]);

test('every club has a valid squad: 18–24 players, unique numbers, an XI of 11 matching its formation', function () {
    foreach (Team::all() as $team) {
        $squad = $team->currentPlayers()->get();
        $xi = $squad->filter(fn ($p) => $p->pivot->is_starter);
        $lines = FootballSeeder::formationLines($team->preferred_formation);

        expect($squad->count())->toBeBetween(18, 24)
            ->and($squad->pluck('pivot.shirt_number')->filter()->unique())->toHaveCount($squad->count())
            ->and($xi)->toHaveCount(11)
            ->and($xi->where('position', 'Goalkeeper'))->toHaveCount(1)
            ->and($xi->where('position', 'Defender'))->toHaveCount($lines['Defender']);
        foreach ($squad as $p) {
            expect($p->display_name)->not->toBeEmpty()
                ->and($p->market_value)->toMatch('/^€[\d.]+(bn|m|k)$/')
                ->and($p->contract_expires)->not->toBeNull()
                ->and($p->nationality)->not->toBeEmpty();
        }
    }
});

test('display names are short and clean', function () {
    $display = fn (string $last) => Player::where('last_name', $last)->value('display_name');

    expect($display('Júnior'))->toBe('Vinícius Jr.')
        ->and($display('Saka'))->toBe('Saka')
        ->and(FootballSeeder::displayName('', 'Rodri'))->toBe('Rodri')
        ->and(FootballSeeder::displayName('Vinícius', 'Júnior'))->toBe('Vinícius Jr.');
});

test('the top 15 by market value all have real cut-outs, and no two players share an image', function () {
    $top = Player::all()->sortByDesc(fn ($p) => PlayerDataValidator::money($p->market_value) ?? 0)->take(15);
    foreach ($top as $p) {
        expect($p->cutout_image_url)->toStartWith('https://r2.thesportsdb.com/');
    }
    // Only a photo in another club's kit exists: shown anyway, and labelled (policy of 2026-11-19).
    expect(Player::where('last_name', 'Luiz')->where('first_name', 'Douglas')->first()->only(['cutout_source']))->toBe(['cutout_source' => 'other_kit'])
        ->and(Player::where('last_name', 'Luiz')->where('first_name', 'Douglas')->value('cutout_image_url'))->toStartWith('https://r2.thesportsdb.com/')
        // National-team-labelled record, but the photo is from the club shoot (Bayern, 2025-08-28).
        ->and(Player::where('last_name', 'Olise')->value('cutout_image_url'))->toStartWith('https://r2.thesportsdb.com/');

    $urls = Player::where('cutout_image_url', 'like', 'https://%')->pluck('cutout_image_url');
    expect($urls->unique())->toHaveCount($urls->count());
});

test('photo_url carries the verified real photo, never the SVG fallback', function () {
    foreach (Player::all() as $p) {
        if (str_starts_with($p->cutout_image_url, 'https://')) {
            expect($p->photo_url)->toBe($p->cutout_image_url);
        } else {
            expect($p->photo_url)->toBeNull();
        }
    }
    foreach (['Saka', 'Haaland', 'Yamal', 'Mbappé', 'Musiala', 'Rice', 'Wirtz'] as $star) {
        expect(Player::where('last_name', $star)->value('photo_url'))->toStartWith('https://r2.thesportsdb.com/');
    }
});

test('roster:audit passes on the seeded data', function () {
    $this->artisan('roster:audit')->assertSuccessful();
});

test('GET /api/teams/{id} runs a fixed set of queries, none repeated', function () {
    $queriesFor = function (Team $team): array {
        $queries = [];
        DB::listen(function ($e) use (&$queries) {
            $queries[] = $e->sql;
        });
        $json = $this->getJson("/api/teams/{$team->id}")->assertOk()->json();
        DB::flushQueryLog();

        return [$queries, $json];
    };

    [$queries, $json] = $queriesFor(Team::where('short_code', 'ARS')->firstOrFail());

    expect($queries)->toHaveCount(7)
        ->and(array_unique($queries))->toHaveCount(count($queries))
        ->and($json['division']['country']['name'])->toBe('England')
        ->and($json['matches'][0])->toHaveKeys(['home_team', 'away_team', 'division'])
        ->and($json['matches'][0]['division'])->not->toHaveKey('country');
});

test('Arsenal is the reviewed real squad, every player with number, value and contract', function () {
    $squad = Team::where('short_code', 'ARS')->firstOrFail()->currentPlayers()->get();
    $names = $squad->map(fn ($p) => trim("{$p->first_name} {$p->last_name}"));

    expect($names)->not->toContain('Christos Tzolis')
        ->and($names)->not->toContain('Ezri Konsa')
        ->and($names)->not->toContain('Bruno Guimarães')
        ->and($names)->toContain('William Saliba')
        ->and($names)->toContain('Gabriel Magalhães');

    foreach ($squad as $player) {
        expect($player->pivot->shirt_number)->toBeBetween(1, 99)
            ->and($player->market_value)->toMatch('/^€[\d.]+(bn|m|k)$/')
            ->and($player->contract_expires)->not->toBeNull();
    }
    expect($squad->pluck('pivot.shirt_number')->unique())->toHaveCount($squad->count()); // no shared numbers
});

test('list filters reject unknown ids', function (string $url) {
    $this->getJson($url)->assertUnprocessable();
})->with(['/api/players?team_id=99999', '/api/teams?division_id=99999']);

test('CORS allows the Vite dev origins and rejects others', function (string $origin, bool $allowed) {
    $response = $this->call('OPTIONS', '/api/players', server: [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe($allowed ? $origin : null);
})->with([
    ['http://localhost:5173', true],
    ['http://127.0.0.1:5173', true],
    ['http://evil.example', false],
]);

test('players_count on every team endpoint equals the listed current squad', function () {
    $squad = fn (int $id) => count($this->getJson("/api/players?team_id={$id}")->json());

    foreach ($this->getJson('/api/teams')->json() as $row) {
        expect($row['players_count'])->toBe($squad($row['id']));
    }
    foreach ($this->getJson('/api/countries')->json() as $country) {
        foreach ($country['divisions'] as $division) {
            foreach ($division['teams'] as $team) {
                expect($team['players_count'])->toBe($squad($team['id']));
            }
        }
    }
    $arsenal = Team::where('short_code', 'ARS')->firstOrFail();
    expect($this->getJson("/api/teams/{$arsenal->id}")->json('players_count'))->toBe($squad($arsenal->id));
});

test('every player has a standard three-letter country code', function () {
    $players = collect($this->getJson('/api/players')->assertOk()->json());

    expect($players->filter(fn ($p) => ! preg_match('/^[A-Z]{3}$/', $p['country']['code'] ?? ''))->pluck('last_name')->all())->toBe([]);

    $codeOf = fn (string $last) => $players->firstWhere('last_name', $last)['country']['code'];
    expect($codeOf('Mamardashvili'))->toBe('GEO')
        ->and($codeOf('Szoboszlai'))->toBe('HUN')
        ->and($codeOf('Kerkez'))->toBe('HUN')
        ->and($codeOf('Isak'))->toBe('SWE')
        ->and($codeOf('Tsimikas'))->toBe('GRE')
        ->and($codeOf('Bradley'))->toBe('NIR');
});

test('Liverpool is exactly its locked squad, with the confirmed shirt numbers', function () {
    $squad = Team::where('short_code', 'LIV')->firstOrFail()->currentPlayers()->get()
        ->mapWithKeys(fn ($p) => [trim("{$p->first_name} {$p->last_name}") => $p->pivot->shirt_number])
        ->sortKeys()->all();

    expect($squad)->toBe(collect([
        'Alisson' => 1, 'Giorgi Mamardashvili' => 25, 'Virgil van Dijk' => 4, 'Ibrahima Konaté' => 5, 'Joe Gomez' => 2,
        'Giovanni Leoni' => 15, 'Andrew Robertson' => 26, 'Milos Kerkez' => 6, 'Kostas Tsimikas' => 21, 'Conor Bradley' => 12,
        'Jeremie Frimpong' => 30, 'Alexis Mac Allister' => 10, 'Dominik Szoboszlai' => 8, 'Ryan Gravenberch' => 38,
        'Curtis Jones' => 17, 'Wataru Endo' => 3, 'Florian Wirtz' => 7, 'Mohamed Salah' => 11, 'Cody Gakpo' => 18,
        'Federico Chiesa' => 14, 'Alexander Isak' => 9, 'Hugo Ekitiké' => 22,
    ])->sortKeys()->all());
});

test('TheSportsDB-only 2026 moves are reverted to the 2025/26 clubs', function (string $first, string $last, string $code) {
    expect(Player::where(['first_name' => $first, 'last_name' => $last])->firstOrFail()->currentTeams->first()->short_code)->toBe($code);
})->with([
    ['', 'Rodri', 'MCI'], ['Enzo', 'Fernández', 'CHE'], ['Bernardo', 'Silva', 'MCI'], ['Denzel', 'Dumfries', 'INT'],
    ['John', 'Stones', 'MCI'], ['Marcus', 'Rashford', 'BAR'], ['Ferran', 'Torres', 'BAR'], ['Karim', 'Adeyemi', 'BVB'],
]);

test('roster:audit fails when one player name appears at two clubs', function () {
    $saka = Player::where('last_name', 'Saka')->firstOrFail();
    $impostor = Player::create(['first_name' => 'Bukayo', 'last_name' => 'Saka', 'date_of_birth' => '2000-01-01', 'position' => 'Forward']);
    TeamPlayer::create(['team_id' => Team::where('short_code', 'CHE')->value('id'), 'player_id' => $impostor->id, 'season' => '2026/27', 'shirt_number' => 99, 'is_current' => true]);

    $exit = Artisan::call('roster:audit');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toMatch('/No player name appears at more than one club\s*\|\s*FAIL\s*\|\s*Bukayo Saka/');
    expect($saka->exists)->toBeTrue();
});

test('every locked club is exactly its confirmed squad, with the confirmed numbers', function () {
    $locks = (require database_path('seeders/data/football_profiles.php'))['roster_overrides']['lock'];

    expect(array_keys($locks))->toEqualCanonicalizing(['LIV', 'MCI', 'ARS', 'CHE', 'MUN']);
    foreach ($locks as $code => $entries) {
        $expected = collect($entries)->mapWithKeys(fn ($num, $key) => [TheSportsDb::nfc(explode('|', $key)[0]) => $num])->sortKeys()->all();
        $actual = Team::where('short_code', $code)->firstOrFail()->currentPlayers()->get()
            ->mapWithKeys(fn ($p) => [trim("{$p->first_name} {$p->last_name}") => $p->pivot->shirt_number])->sortKeys()->all();
        expect($actual)->toBe($expected);
    }
});

test('false La Liga transfers are gone', function () {
    expect(Player::where('last_name', 'Gordon')->where('first_name', 'Anthony')->exists())->toBeFalse()
        ->and(Player::where('last_name', 'Lookman')->exists())->toBeFalse()
        ->and(Player::where('last_name', 'Semenyo')->exists())->toBeFalse()
        ->and(Player::where('last_name', 'Anderson')->where('first_name', 'Elliot')->exists())->toBeFalse();
});

test('every player has a complete 360° profile with consistent salaries and labelled estimates', function () {
    foreach (Player::all() as $p) {
        foreach (['highest_market_value', 'weekly_salary', 'annual_salary', 'release_clause', 'agent_name', 'preferred_foot', 'primary_position', 'playing_style'] as $field) {
            expect($p->{$field})->not->toBeEmpty()->not->toBe('Undisclosed');
        }
        expect($p->height_cm)->toBeBetween(150, 215)
            ->and($p->contract_expires)->not->toBeNull()
            ->and($p->profile_estimates)->toBeArray();

        $weekly = PlayerDataValidator::money($p->weekly_salary);
        expect(PlayerDataValidator::money($p->annual_salary))->toEqualWithDelta(round($weekly * 52, 2), 0.005)
            ->and(PlayerDataValidator::money($p->highest_market_value))
            ->toBeGreaterThanOrEqual(PlayerDataValidator::money($p->market_value));
    }
});

test('positions use the exact role, not a generic slot (Doku is a left winger)', function () {
    $doku = Player::where('last_name', 'Doku')->firstOrFail();

    expect($doku->primary_position)->toBe('Left Winger')
        ->and($doku->height_cm)->toBe(173)
        ->and($doku->preferred_foot)->toBe('Right');
    expect(Player::where('last_name', 'Saka')->value('primary_position'))->toBe('Right Winger');
});

test('release clauses: La Liga players have one, everyone else shows None', function () {
    foreach (Player::with('currentTeams')->get() as $p) {
        $spanish = in_array($p->currentTeams->first()->short_code, ['RMA', 'BAR', 'ATM'], true);
        $spanish
            ? expect($p->release_clause)->toMatch('/^€[\d.]+(bn|m)$/')
            : expect($p->release_clause)->toBe('None');
    }
});

test('the Back view is populated: caps, career transfers and honours', function () {
    $mamardashvili = Player::where('last_name', 'Mamardashvili')->firstOrFail();
    expect($mamardashvili->intl_caps)->toBe(30)
        ->and($mamardashvili->profile_estimates)->toContain('intl_caps')
        ->and(collect($mamardashvili->transfer_history)->last())->toBe(['date' => '2024-08', 'from' => 'Valencia', 'to' => 'Liverpool', 'fee' => '€30.00m']);

    $doku = Player::where('last_name', 'Doku')->firstOrFail();
    expect(collect($doku->trophies)->pluck('title'))->toContain('Premier League')
        ->and(collect($doku->transfer_history)->firstWhere('to', 'Manchester City'))->toMatchArray(['date' => '2023-08', 'fee' => '€60.00m']);

    foreach (Player::all() as $p) {
        expect($p->intl_caps)->not->toBeNull()          // uncapped players are an explicit 0
            ->and($p->transfer_history)->not->toBeEmpty(); // academy products get an "Academy" entry
    }
    expect(collect(Player::where('last_name', 'Zaïre-Emery')->value('transfer_history'))->first())
        ->toMatchArray(['to' => 'Paris Saint-Germain', 'fee' => 'Academy', 'date' => null]);
});
