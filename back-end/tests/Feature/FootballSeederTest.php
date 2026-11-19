<?php

use App\Models\Country;
use App\Models\GameMatch;
use App\Models\PlayerStat;
use App\Models\TeamPlayer;
use Database\Seeders\FootballSeeder;

beforeEach(fn () => $this->seed());

test('every featured country has a league with clubs, squads and fixtures', function (string $code, string $league) {
    $country = Country::where('code', $code)->with('divisions.teams')->firstOrFail();
    $division = $country->divisions->firstWhere('name', $league);

    expect($division)->not->toBeNull()
        ->and($division->teams->count())->toBeGreaterThanOrEqual(2);

    foreach ($division->teams as $team) {
        expect($team->currentPlayers()->count())->toBeGreaterThanOrEqual(4);
    }

    expect(GameMatch::where('division_id', $division->id)->where('status', 'FINISHED')->count())->toBeGreaterThan(0)
        ->and(GameMatch::where('division_id', $division->id)->where('status', 'SCHEDULED')->count())->toBeGreaterThan(0);
})->with([
    ['ENG', 'Premier League'],
    ['ESP', 'La Liga'],
    ['ITA', 'Serie A'],
    ['GER', 'Bundesliga'],
    ['FRA', 'Ligue 1'],
]);

test('each team meets every league rival home and away, with enough games for a table', function () {
    GameMatch::query()->get()->groupBy('division_id')->each(function ($matches) {
        $teams = $matches->pluck('home_team_id')->merge($matches->pluck('away_team_id'))->unique();
        $pairs = $matches->countBy(fn ($m) => "{$m->home_team_id}-{$m->away_team_id}");

        // Every ordered pairing (home and away) is played.
        expect($pairs)->toHaveCount($teams->count() * ($teams->count() - 1));

        $finished = $matches->where('status', 'FINISHED');
        foreach ($teams as $teamId) {
            $played = $finished->filter(fn ($m) => $m->home_team_id === $teamId || $m->away_team_id === $teamId)->count();
            expect($played)->toBeGreaterThanOrEqual(10);
        }
    });
});

test('player goals never exceed each side\'s score, and only finished matches have stats', function () {
    foreach (GameMatch::with('playerStats.player.teams')->get() as $match) {
        if ($match->status !== 'FINISHED') {
            expect($match->playerStats)->toBeEmpty();

            continue;
        }

        $goalsFor = fn (int $teamId) => $match->playerStats
            ->filter(fn ($s) => $s->player->teams->contains(fn ($t) => $t->id === $teamId && $t->pivot->is_current))
            ->sum('goals');

        expect($goalsFor($match->home_team_id))->toBeLessThanOrEqual($match->home_score)
            ->and($goalsFor($match->away_team_id))->toBeLessThanOrEqual($match->away_score);
    }
});

test('match ratings stay within 5–10 and card ratings land in a believable range', function () {
    expect(PlayerStat::where('rating', '<', 5)->orWhere('rating', '>', 10)->count())->toBe(0);

    $averages = PlayerStat::query()->selectRaw('player_id, avg(rating) as avg')->groupBy('player_id')->pluck('avg');
    expect($averages->min())->toBeGreaterThanOrEqual(6.0)
        ->and($averages->max())->toBeLessThanOrEqual(9.6);
});

test('re-running the seeder does not duplicate data', function () {
    $before = [GameMatch::count(), PlayerStat::count(), TeamPlayer::count()];

    $this->seed(FootballSeeder::class);

    expect([GameMatch::count(), PlayerStat::count(), TeamPlayer::count()])->toBe($before);
});
