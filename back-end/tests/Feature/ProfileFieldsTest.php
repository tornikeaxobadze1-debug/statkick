<?php

use App\Models\Country;
use App\Models\Division;
use App\Models\Player;
use App\Models\Team;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests(); // THESPORTSDB_FETCH=false in phpunit.xml: seeding must stay offline
    $this->seed();
});

const CLUB_FIELDS = [
    'short_name', 'primary_color', 'secondary_color', 'founded_year', 'city', 'stadium_name', 'stadium_capacity',
    'coach_name', 'president', 'kit_sponsor', 'total_market_value', 'squad_size', 'average_age', 'foreigners_count',
    'foreigners_pct', 'preferred_formation', 'transfer_income', 'transfer_expenditure', 'net_spend',
    'annual_wage_bill', 'last_5_form', 'clean_sheets', 'trophies',
];

const PLAYER_FIELDS = [
    'shirt_number', 'primary_position', 'secondary_positions', 'age', 'date_of_birth', 'height_cm', 'nationality',
    'preferred_foot', 'market_value', 'highest_market_value', 'highest_market_value_date', 'contract_expires',
    'weekly_salary', 'annual_salary', 'release_clause', 'agent_name', 'boot_sponsor', 'playing_style', 'appearances',
    'goals', 'assists', 'minutes_played', 'yellow_cards', 'red_cards', 'avg_rating', 'xg', 'xa', 'key_passes',
    'pass_accuracy_pct', 'progressive_carries', 'dribble_success_pct', 'tackles_interceptions',
    'aerial_duels_won_pct', 'heatmap_zones', 'injury_status', 'injury_history', 'national_team', 'intl_caps',
    'intl_goals', 'trophies', 'transfer_history', 'transfer_rumors', 'cutout_image_url',
];

test('GET /api/divisions lists the five leagues', function () {
    expect(collect($this->getJson('/api/divisions')->assertOk()->json())->pluck('country.code')->sort()->values()->all())
        ->toBe(['ENG', 'ESP', 'FRA', 'GER', 'ITA']);
});

test('GET /api/countries lists only the five league nations, not player nationalities', function () {
    $countries = collect($this->getJson('/api/countries')->assertOk()->json());

    expect($countries->pluck('name')->all())->toBe(['England', 'France', 'Germany', 'Italy', 'Spain'])
        ->and($countries->every(fn ($c) => count($c['divisions']) === 1))->toBeTrue()
        ->and(Country::where('name', 'Georgia')->exists())->toBeTrue(); // still a nationality (Kvaratskhelia)
});

test('only the 17 elite clubs exist, grouped by country', function () {
    $clubs = Team::with('country')->get()->groupBy('country.name')
        ->map(fn ($teams) => $teams->pluck('name')->sort()->values()->all())->sortKeys()->all();

    expect($clubs)->toBe([
        'England' => ['Arsenal', 'Chelsea', 'Liverpool', 'Manchester City', 'Manchester United'],
        'France' => ['Monaco', 'Paris Saint-Germain'],
        'Germany' => ['Bayer Leverkusen', 'Bayern Munich', 'Borussia Dortmund'],
        'Italy' => ['Inter', 'Juventus', 'Milan', 'Napoli'],
        'Spain' => ['Atlético Madrid', 'Barcelona', 'Real Madrid'],
    ]);
});

test('Barcelona fields a verified squad: curated stars are rich, additions carry no invented values', function () {
    $barca = Team::where('short_code', 'BAR')->firstOrFail();
    $curated = (require database_path('seeders/data/football_profiles.php'))['players'];

    $rows = collect($this->getJson("/api/players?team_id={$barca->id}")->assertOk()->json());
    $names = $rows->map(fn ($p) => trim("{$p['first_name']} {$p['last_name']}"));

    // TheSportsDB places these two elsewhere (Chicago Fire, Ajax), so they are never seeded here.
    expect($rows->count())->toBeGreaterThanOrEqual(11)
        ->and($names)->not->toContain('Robert Lewandowski')
        ->and($names)->not->toContain('Marc-André ter Stegen');

    foreach ($rows as $row) {
        $name = trim("{$row['first_name']} {$row['last_name']}");
        expect(array_sum($row['heatmap_zones']))->toBe(100);
        if (isset($curated[$name])) {
            expect($row['market_value'])->not->toBeNull(); // honours may genuinely be empty (e.g. Adeyemi)
        } else {
            // Not curated: value/contract from reviewed squad_facts; caps from the approximate list, always labelled.
            expect($row['market_value'])->not->toBeNull()->and($row['contract_expires'])->not->toBeNull();
            if ($row['intl_caps'] !== null) {
                expect($row['profile_estimates'])->toContain('intl_caps');
            }
        }
    }
});

test('GET /api/teams?division_id= returns club profiles consistent with the standings', function () {
    foreach (Division::all() as $division) {
        foreach ($this->getJson("/api/teams?division_id={$division->id}")->assertOk()->json() as $row) {
            expect($row)->toHaveKeys(CLUB_FIELDS)
                ->and($row['stadium_name'])->not->toBeNull()
                ->and($row['total_market_value'])->toMatch('/^€\d+(\.\d+)?(bn|m|k)$/')
                ->and($row['last_5_form'])->toBe($row['form'])
                ->and($row['clean_sheets'])->toBe($row['record']['clean_sheets'])
                ->and($row['trophies'][0])->toHaveKeys(['title', 'count']);
        }
    }

    $arsenal = collect($this->getJson('/api/teams')->json())->firstWhere('short_code', 'ARS');
    expect($arsenal)->toMatchArray([
        'stadium_name' => 'Emirates Stadium',
        'transfer_income' => '€30.00m',
        'transfer_expenditure' => '€292.00m',
        'net_spend' => '-€262.00m',
        'foreigners_pct' => 60.0,
    ]);
});

test('GET /api/players?team_id= returns full player profiles with season stats matching totals', function () {
    $team = Team::where('short_code', 'MCI')->firstOrFail();

    $rows = $this->getJson("/api/players?team_id={$team->id}")->assertOk()->json();

    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect($row)->toHaveKeys(PLAYER_FIELDS)
            ->and($row['appearances'])->toBe($row['totals']['appearances'])
            ->and($row['goals'])->toBe($row['totals']['goals'])
            ->and($row['assists'])->toBe($row['totals']['assists'])
            // A verified cut-out, or the club-colour jersey when none can be verified.
            ->and($row['cutout_image_url'])->toMatch('#^(https://r2\.thesportsdb\.com/|data:image/svg\+xml,)#');
    }

    $haaland = collect($rows)->firstWhere('last_name', 'Haaland');
    expect($haaland)->toMatchArray([
        'primary_position' => 'Centre-Forward',
        'market_value' => '€200.00m',
        'weekly_salary' => '€600k',
        'annual_salary' => '€31.20m',
        'preferred_foot' => 'Left',
        'national_team' => 'Norway',
        'contract_expires' => '2034-06-30',
    ]);
});

test('GET /api/players/{id} returns structured JSON profile fields', function () {
    $player = Player::where('last_name', 'Rodri')->firstOrFail();

    $json = $this->getJson("/api/players/{$player->id}")->assertOk()->json();

    expect($json)->toHaveKeys(PLAYER_FIELDS)
        ->and($json['secondary_positions'])->toBe(['Central Midfield', 'Centre-Back'])
        ->and($json['trophies'])->toContain(['title' => 'Premier League', 'count' => 4])
        ->and($json['transfer_history'][1])->toBe([
            'date' => '2019-07', 'from' => 'Atlético Madrid', 'to' => 'Manchester City', 'fee' => '€70.00m',
        ])
        ->and($json['injury_history'][0]['days_out'])->toBe(230)
        ->and(array_sum($json['heatmap_zones']))->toBe(100)
        // No verified source for rumours, so none are invented.
        ->and($json['transfer_rumors'])->toBeNull();
});

test('every player gets a profile and a cut-out URL without network access', function () {
    expect(Player::whereNull('primary_position')->count())->toBe(0)
        ->and(Player::whereNull('cutout_image_url')->count())->toBe(0)
        ->and(Player::whereNull('heatmap_zones')->count())->toBe(0);
});

test('re-seeding keeps generated profile values stable', function () {
    $before = Player::orderBy('id')->get(['xg', 'heatmap_zones', 'transfer_rumors'])->toArray();

    $this->seed();

    expect(Player::orderBy('id')->get(['xg', 'heatmap_zones', 'transfer_rumors'])->toArray())->toBe($before);
});
