<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class DivisionController extends Controller
{
    /**
     * GET /api/divisions
     */
    public function index(): JsonResponse
    {
        $divisions = Division::query()
            ->with('country')
            ->withCount('teams')
            ->orderBy('name')
            ->get();

        return response()->json($divisions);
    }

    /**
     * GET /api/divisions/{id} (also /api/leagues/{id}) — the league with its country,
     * clubs and fixtures. Five queries whatever the fixture count: one fetch of all the
     * league's matches, split by date here, and both sides of every match taken from
     * the league's already-loaded clubs instead of re-querying teams.
     */
    public function show(Division $division): JsonResponse
    {
        $division->load([
            'country',
            'teams' => fn ($q) => $q->orderBy('name'),
        ]);
        $clubs = $division->teams->keyBy('id');

        $matches = $division->matches()->orderBy('match_date')->get();
        $missing = $matches->flatMap(fn ($m) => [$m->home_team_id, $m->away_team_id])->unique()->diff($clubs->keys());
        if ($missing->isNotEmpty()) { // a visiting club from outside the league (none today)
            $clubs = $clubs->union(Team::whereKey($missing->values())->get()->keyBy('id'));
        }
        foreach ($matches as $match) {
            $match->setRelation('homeTeam', $clubs[$match->home_team_id] ?? null);
            $match->setRelation('awayTeam', $clubs[$match->away_team_id] ?? null);
        }

        $now = now();
        $upcoming = $matches->filter(fn ($m) => $m->match_date >= $now)->values();
        $past = $matches->filter(fn ($m) => $m->match_date < $now)->sortByDesc('match_date')->values();

        return response()->json([
            ...$division->toArray(),
            'upcoming_matches' => $upcoming,
            'past_matches' => $past,
        ]);
    }
}
