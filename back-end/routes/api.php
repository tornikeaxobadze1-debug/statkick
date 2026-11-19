<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

// --- Public -----------------------------------------------------------------

Route::get('/countries', [CountryController::class, 'index']);

Route::get('/divisions', [DivisionController::class, 'index']);
Route::get('/divisions/{division}', [DivisionController::class, 'show'])->whereNumber('division');
// Aliases: the frontend calls leagues "leagues".
Route::get('/leagues', [DivisionController::class, 'index']);
Route::get('/leagues/{division}', [DivisionController::class, 'show'])->whereNumber('division');

Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{team}', [TeamController::class, 'show'])->whereNumber('team');

Route::get('/players', [PlayerController::class, 'index']);
Route::get('/players/{player}', [PlayerController::class, 'show'])->whereNumber('player');

Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/{match}', [MatchController::class, 'show'])->whereNumber('match');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// --- Authenticated (Bearer token) ---------------------------------------------

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/preferences', [AuthController::class, 'updatePreferences']);

    Route::get('/user', [AuthController::class, 'user']);

    // Favorites: /api/favorites (and the original /api/user/favorites).
    foreach (['favorites', 'user/favorites'] as $prefix) {
        Route::prefix($prefix)->group(function () {
            Route::get('/', [FavoritesController::class, 'index']);
            Route::post('/players/{player}', [FavoritesController::class, 'togglePlayer'])->whereNumber('player');
            Route::put('/players/{player}', [FavoritesController::class, 'savePlayer'])->whereNumber('player');
            Route::delete('/players/{player}', [FavoritesController::class, 'removePlayer'])->whereNumber('player');
            Route::post('/teams/{team}', [FavoritesController::class, 'toggleTeam'])->whereNumber('team');
            Route::put('/teams/{team}', [FavoritesController::class, 'saveTeam'])->whereNumber('team');
            Route::delete('/teams/{team}', [FavoritesController::class, 'removeTeam'])->whereNumber('team');
        });
    }
});
