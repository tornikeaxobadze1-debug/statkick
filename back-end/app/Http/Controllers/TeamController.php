<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * GET /api/teams?division_id= — teams (optionally one league's) with a standings line computed from FINISHED matches
     * (3 points per win) and its last five results, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
        ]);

        $finished = GameMatch::query()
            ->where('status', 'FINISHED')
            ->orderByDesc('match_date')
            ->get(['id', 'home_team_id', 'away_team_id', 'home_score', 'away_score', 'match_date']);

        $teams = Team::query()
            ->when($filters['division_id'] ?? null, fn ($q, $id) => $q->where('division_id', $id))
            ->with(['division', 'country'])
            ->withCount(['currentPlayers as players_count']) // the listed roster, not the static squad_size
            ->orderBy('name')
            ->get()
            ->map(function (Team $team) use ($finished) {
                $record = ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0,
                    'goals_for' => 0, 'goals_against' => 0, 'clean_sheets' => 0, 'points' => 0];
                $form = [];

                foreach ($finished as $match) {
                    $isHome = (int) $match->home_team_id === $team->id;
                    if (! $isHome && (int) $match->away_team_id !== $team->id) {
                        continue;
                    }

                    $for = $isHome ? $match->home_score : $match->away_score;
                    $against = $isHome ? $match->away_score : $match->home_score;
                    $result = $for <=> $against;

                    $record['played']++;
                    $record['goals_for'] += $for;
                    $record['goals_against'] += $against;
                    $record['clean_sheets'] += $against === 0 ? 1 : 0;
                    $record[match ($result) {
                        1 => 'wins', 0 => 'draws', -1 => 'losses'
                    }]++;
                    $record['points'] += match ($result) {
                        1 => 3, 0 => 1, -1 => 0
                    };

                    if (count($form) < 5) {
                        $form[] = match ($result) {
                            1 => 'W', 0 => 'D', -1 => 'L'
                        };
                    }
                }

                return [...$team->toArray(), 'record' => $record, 'form' => $form];
            });

        return response()->json($teams);
    }

    /**
     * GET /api/teams/{id} — the club with its league (and the league's country), squad
     * and matches. A fixed set of queries whatever the squad or fixture count, none
     * repeated: the league's country and the matches' league are the club's own
     * (already loaded), and both sides of every match come from one query.
     */
    public function show(Team $team): JsonResponse
    {
        $team->load([
            'division',
            'country',
            'currentPlayers' => fn ($q) => $q->with('country')->orderBy('team_player.shirt_number'),
        ]);
        $team->division?->setRelation('country', $team->country);

        // Home and away matches combined, newest first.
        $matches = $team->matches()->orderByDesc('match_date')->get();

        $clubs = Team::whereKey($matches->flatMap(fn ($m) => [$m->home_team_id, $m->away_team_id])->unique()->values())
            ->get()
            ->keyBy('id');
        $league = $team->division?->withoutRelations();
        foreach ($matches as $match) {
            $match->setRelation('homeTeam', $clubs[$match->home_team_id] ?? null);
            $match->setRelation('awayTeam', $clubs[$match->away_team_id] ?? null);
            if ($league && (int) $match->division_id === $league->id) {
                $match->setRelation('division', $league);
            }
        }
        $matches->loadMissing('division'); // only for a match outside the club's league (none today)

        return response()->json([
            ...$team->toArray(),
            'players_count' => $team->currentPlayers->count(),
            'matches' => $matches,
        ]);
    }
}
