<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A user's favorite players and clubs. Served at /api/favorites (and the original
 * /api/user/favorites): POST toggles, PUT saves, DELETE removes — all idempotent except
 * the toggle, and every write returns the updated lists.
 */
class FavoritesController extends Controller
{
    /**
     * GET /api/favorites
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(self::lists($request->user()));
    }

    /** POST …/players/{id} — toggles the favorite. */
    public function togglePlayer(Request $request, Player $player): JsonResponse
    {
        $result = $request->user()->favoritePlayers()->toggle($player->id);

        return $this->written($request->user(), ['player_id' => $player->id, 'is_favorite' => $result['attached'] !== []]);
    }

    /** PUT …/players/{id} — saves (no-op if already saved). */
    public function savePlayer(Request $request, Player $player): JsonResponse
    {
        $request->user()->favoritePlayers()->syncWithoutDetaching([$player->id]);

        return $this->written($request->user(), ['player_id' => $player->id, 'is_favorite' => true]);
    }

    /** DELETE …/players/{id} — removes (no-op if not saved). */
    public function removePlayer(Request $request, Player $player): JsonResponse
    {
        $request->user()->favoritePlayers()->detach($player->id);

        return $this->written($request->user(), ['player_id' => $player->id, 'is_favorite' => false]);
    }

    /** POST …/teams/{id} — toggles the favorite. */
    public function toggleTeam(Request $request, Team $team): JsonResponse
    {
        $result = $request->user()->favoriteTeams()->toggle($team->id);

        return $this->written($request->user(), ['team_id' => $team->id, 'is_favorite' => $result['attached'] !== []]);
    }

    /** PUT …/teams/{id} — saves (no-op if already saved). */
    public function saveTeam(Request $request, Team $team): JsonResponse
    {
        $request->user()->favoriteTeams()->syncWithoutDetaching([$team->id]);

        return $this->written($request->user(), ['team_id' => $team->id, 'is_favorite' => true]);
    }

    /** DELETE …/teams/{id} — removes (no-op if not saved). */
    public function removeTeam(Request $request, Team $team): JsonResponse
    {
        $request->user()->favoriteTeams()->detach($team->id);

        return $this->written($request->user(), ['team_id' => $team->id, 'is_favorite' => false]);
    }

    /**
     * The user's favorites, newest first.
     *
     * @return array{players: mixed, teams: mixed}
     */
    public static function lists(User $user): array
    {
        return [
            'players' => $user->favoritePlayers()
                ->with(['country', 'currentTeams'])
                ->orderByPivot('created_at', 'desc')
                ->get(),
            'teams' => $user->favoriteTeams()
                ->with(['division', 'country'])
                ->orderByPivot('created_at', 'desc')
                ->get(),
        ];
    }

    /** A write's result plus the updated favorites. */
    private function written(User $user, array $result): JsonResponse
    {
        return response()->json([...$result, 'favorites' => self::lists($user)]);
    }
}
