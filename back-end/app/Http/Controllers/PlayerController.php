<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PlayerController extends Controller
{
    /** Aliases of the player_stats aggregates loaded in index(), reported under `totals`. */
    private const AGGREGATES = [
        'stat_appearances', 'stat_goals', 'stat_assists', 'stat_minutes_played',
        'stat_yellow_cards', 'stat_red_cards', 'stat_avg_rating',
    ];

    /**
     * GET /api/players?team_id= — players with their full profile, current team and
     * career totals aggregated from player_stats, best average rating first.
     * `team_id` limits the list to that team's current squad.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        $players = Player::query()
            ->when($filters['team_id'] ?? null, fn ($q, $teamId) => $q->whereHas(
                'currentTeams',
                fn ($t) => $t->where('teams.id', $teamId),
            ))
            ->with(['country', 'currentTeams'])
            ->withCount('stats as stat_appearances')
            ->withSum('stats as stat_goals', 'goals')
            ->withSum('stats as stat_assists', 'assists')
            ->withSum('stats as stat_minutes_played', 'minutes_played')
            ->withSum('stats as stat_yellow_cards', 'yellow_cards')
            ->withSum('stats as stat_red_cards', 'red_cards')
            ->withAvg('stats as stat_avg_rating', 'rating')
            ->get()
            ->map(fn (Player $player): array => [
                ...Arr::except($player->attributesToArray(), self::AGGREGATES),
                'country' => $player->country,
                'current_team' => $player->currentTeams->first(),
                'totals' => self::totals($player),
            ])
            ->sortByDesc(fn (array $p): array => [$p['totals']['avg_rating'] ?? -1, $p['totals']['goals']])
            ->values();

        return response()->json($players);
    }

    /**
     * Career totals loaded by the withCount/withSum/withAvg aggregates in index().
     * SUM/AVG come back from the database as numeric strings (or null with no rows).
     *
     * @return array{appearances: int, goals: int, assists: int, minutes_played: int, yellow_cards: int, red_cards: int, avg_rating: float|null}
     */
    private static function totals(Player $player): array
    {
        $avg = $player->getAttribute('stat_avg_rating');

        return [
            'appearances' => (int) $player->getAttribute('stat_appearances'),
            'goals' => (int) $player->getAttribute('stat_goals'),
            'assists' => (int) $player->getAttribute('stat_assists'),
            'minutes_played' => (int) $player->getAttribute('stat_minutes_played'),
            'yellow_cards' => (int) $player->getAttribute('stat_yellow_cards'),
            'red_cards' => (int) $player->getAttribute('stat_red_cards'),
            'avg_rating' => is_numeric($avg) ? round((float) $avg, 2) : null,
        ];
    }

    /**
     * GET /api/players/{id}
     */
    public function show(Player $player): JsonResponse
    {
        $player->load([
            'country',
            'currentTeams.division',
            'teamPlayers' => fn ($q) => $q->orderByDesc('season'),
            'stats.match',
        ]);

        // Clubs for the career and for both sides of every match in ONE query, and the
        // matches' league reused from the current club's (no repeated queries).
        $matches = $player->stats->pluck('match')->filter();
        $teams = Team::whereKey(
            $matches->flatMap(fn ($m) => [$m->home_team_id, $m->away_team_id])
                ->merge($player->teamPlayers->pluck('team_id'))->unique()->values()
        )->get()->keyBy('id');
        foreach ($player->teamPlayers as $spell) {
            $spell->setRelation('team', $teams[$spell->team_id] ?? null);
        }
        $divisions = $player->currentTeams->pluck('division')->filter()->keyBy('id');
        $otherDivisions = $matches->pluck('division_id')->unique()->diff($divisions->keys());
        if ($otherDivisions->isNotEmpty()) {
            $divisions = $divisions->union(Division::whereKey($otherDivisions->values())->get()->keyBy('id'));
        }
        foreach ($matches as $match) {
            $match->setRelation('homeTeam', $teams[$match->home_team_id] ?? null);
            $match->setRelation('awayTeam', $teams[$match->away_team_id] ?? null);
            $match->setRelation('division', $divisions[$match->division_id] ?? null);
        }

        $stats = $player->stats->sortByDesc(fn ($stat) => $stat->match->match_date)->values();

        return response()->json([
            ...$player->makeHidden(['currentTeams', 'teamPlayers', 'stats'])->toArray(),
            'current_team' => $player->currentTeams->first(),
            'career' => $player->teamPlayers,
            'match_stats' => $stats,
        ]);
    }
}
