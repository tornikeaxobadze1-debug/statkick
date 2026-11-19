<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * GET /api/countries — the countries that have a league here. Other countries
     * exist only as player nationalities and are not listed.
     */
    public function index(): JsonResponse
    {
        $countries = Country::query()
            ->whereHas('divisions')
            ->with([
                'divisions' => fn ($q) => $q->orderBy('name'),
                'divisions.teams' => fn ($q) => $q->withCount(['currentPlayers as players_count'])->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json($countries);
    }
}
