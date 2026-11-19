<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public const STATUSES = ['SCHEDULED', 'LIVE', 'FINISHED', 'POSTPONED', 'CANCELLED'];

    /**
     * GET /api/matches?status=FINISHED&division_id=1&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $request->merge(['status' => $request->filled('status') ? strtoupper($request->query('status')) : null]);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $matches = GameMatch::query()
            ->with(['division', 'homeTeam', 'awayTeam'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['division_id'] ?? null, fn ($q, $id) => $q->where('division_id', $id))
            ->orderByDesc('match_date')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($matches);
    }

    /**
     * GET /api/matches/{id}
     */
    public function show(GameMatch $match): JsonResponse
    {
        $match->load([
            'division.country',
            'homeTeam.country',
            'homeTeam.division',
            'awayTeam.country',
            'awayTeam.division',
            'playerStats' => fn ($q) => $q->orderByDesc('rating'),
            'playerStats.player.country',
        ]);

        return response()->json($match);
    }
}
