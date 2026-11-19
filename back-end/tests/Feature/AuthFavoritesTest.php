<?php

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

/*
 * Registration, sign-in and favorites (players and clubs). Uses small hand-made
 * fixtures rather than the full football seed, except where the seed itself is tested.
 */

function registerUser(array $overrides = []): TestResponse
{
    return test()->postJson('/api/register', [
        'name' => 'Nino Beridze', 'email' => 'nino@example.com', 'password' => 'secret123', ...$overrides,
    ]);
}

function bearer(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

// ---- Registration & sign-in ----------------------------------------------------------

test('register creates the account and signs in with the same payload as login', function () {
    $register = registerUser()->assertCreated()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token', 'token_type']);

    expect($register->json('token_type'))->toBe('Bearer')
        ->and($register->json('user.email'))->toBe('nino@example.com')
        ->and($register->json('user'))->not->toHaveKey('password');

    // The returned token works straight away.
    $this->getJson('/api/user', bearer($register->json('token')))->assertOk()->assertJsonPath('email', 'nino@example.com');

    $login = $this->postJson('/api/login', ['email' => 'nino@example.com', 'password' => 'secret123'])->assertOk();
    expect(array_keys($login->json()))->toBe(array_keys($register->json()))
        ->and(array_keys($login->json('user')))->toEqualCanonicalizing(array_keys($register->json('user')));
});

test('password_confirmation is optional, but must match when sent', function () {
    registerUser(['email' => 'a@example.com'])->assertCreated();
    registerUser(['email' => 'b@example.com', 'password_confirmation' => 'secret123'])->assertCreated();
    registerUser(['email' => 'c@example.com', 'password_confirmation' => 'different1'])
        ->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('registration rejects a duplicate email, case-insensitively', function () {
    registerUser()->assertCreated();

    registerUser()->assertUnprocessable()->assertJsonValidationErrors('email');
    registerUser(['email' => '  NINO@Example.com '])->assertUnprocessable()->assertJsonValidationErrors('email');
    expect(User::where('email', 'nino@example.com')->count())->toBe(1);
});

test('registration validates name, email and an 8-character minimum password', function () {
    registerUser(['password' => 'short1'])->assertUnprocessable()->assertJsonValidationErrors('password');
    registerUser(['email' => 'not-an-email'])->assertUnprocessable()->assertJsonValidationErrors('email');
    registerUser(['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
});

test('login is case-insensitive on email and rejects a wrong password', function () {
    registerUser()->assertCreated();

    $this->postJson('/api/login', ['email' => 'Nino@Example.COM', 'password' => 'secret123'])->assertOk();
    $this->postJson('/api/login', ['email' => 'nino@example.com', 'password' => 'wrong-pass'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

// ---- Favorites -----------------------------------------------------------------------

beforeEach(function () {
    $this->team = Team::create(['name' => 'Test FC', 'short_code' => 'TFC']);
    $this->otherTeam = Team::create(['name' => 'Other FC', 'short_code' => 'OFC']);
    $this->player = Player::create(['first_name' => 'Test', 'last_name' => 'Striker', 'date_of_birth' => '2000-01-01', 'position' => 'Forward']);
});

test('favorites require a signed-in user', function () {
    $this->getJson('/api/favorites')->assertUnauthorized();
    $this->postJson("/api/favorites/players/{$this->player->id}")->assertUnauthorized();
});

test('a user saves and removes favorite players, and the lists reflect it everywhere', function () {
    $auth = bearer(registerUser()->json('token'));

    // Toggle on — the response carries the updated lists.
    $this->postJson("/api/favorites/players/{$this->player->id}", [], $auth)
        ->assertOk()->assertJsonPath('is_favorite', true)->assertJsonPath('favorites.players.0.id', $this->player->id);
    $this->getJson('/api/favorites', $auth)->assertJsonPath('players.0.id', $this->player->id);
    $this->getJson('/api/user', $auth)->assertJsonPath('players.0.id', $this->player->id);

    // Toggle off.
    $this->postJson("/api/favorites/players/{$this->player->id}", [], $auth)->assertJsonPath('is_favorite', false);
    $this->getJson('/api/favorites', $auth)->assertJsonCount(0, 'players');

    // Explicit save / remove are idempotent.
    $this->putJson("/api/favorites/players/{$this->player->id}", [], $auth)->assertJsonPath('is_favorite', true);
    $this->putJson("/api/favorites/players/{$this->player->id}", [], $auth)->assertJsonCount(1, 'favorites.players');
    $this->deleteJson("/api/favorites/players/{$this->player->id}", [], $auth)->assertJsonPath('is_favorite', false);
    $this->deleteJson("/api/favorites/players/{$this->player->id}", [], $auth)->assertJsonCount(0, 'favorites.players');
});

test('a user saves and removes favorite clubs, newest first', function () {
    $auth = bearer(registerUser()->json('token'));

    $this->putJson("/api/favorites/teams/{$this->team->id}", [], $auth)->assertOk()->assertJsonPath('is_favorite', true);
    $this->travel(1)->seconds();
    $this->postJson("/api/favorites/teams/{$this->otherTeam->id}", [], $auth)->assertJsonPath('is_favorite', true);

    $this->getJson('/api/favorites', $auth)->assertJsonPath('teams.0.short_code', 'OFC')->assertJsonPath('teams.1.short_code', 'TFC');
    $this->getJson('/api/user', $auth)->assertJsonCount(2, 'teams');

    $this->deleteJson("/api/favorites/teams/{$this->team->id}", [], $auth)->assertJsonCount(1, 'favorites.teams');
});

test('favorites are private to each user, and the original /api/user/favorites routes still work', function () {
    $alice = bearer(registerUser(['email' => 'alice@example.com'])->json('token'));
    $bob = bearer(registerUser(['email' => 'bob@example.com'])->json('token'));

    $this->postJson("/api/user/favorites/players/{$this->player->id}", [], $alice)->assertJsonPath('is_favorite', true);

    $this->getJson('/api/user/favorites', $alice)->assertJsonCount(1, 'players');
    // Each real HTTP request authenticates afresh; the test client keeps one app instance, so reset
    // the cached guard before switching users.
    app('auth')->forgetGuards();
    $this->getJson('/api/user', $bob)->assertJsonPath('email', 'bob@example.com');
    app('auth')->forgetGuards();
    $this->getJson('/api/favorites', $bob)->assertJsonCount(0, 'players');
    app('auth')->forgetGuards();
    $this->getJson('/api/favorites', $alice)->assertJsonCount(1, 'players');
});

test('unknown players or clubs are 404', function () {
    $auth = bearer(registerUser()->json('token'));

    $this->postJson('/api/favorites/players/999999', [], $auth)->assertNotFound();
    $this->putJson('/api/favorites/teams/999999', [], $auth)->assertNotFound();
});

// ---- Reseeding keeps users and their favorites ---------------------------------------

test('football:reseed rebuilds football data but keeps users and re-attaches their favorites', function () {
    Http::preventStrayRequests();
    Player::query()->delete();
    Team::query()->delete();
    $this->seed();

    $auth = bearer(registerUser()->json('token'));
    $saka = Player::where('last_name', 'Saka')->firstOrFail();
    $arsenal = Team::where('short_code', 'ARS')->firstOrFail();
    $this->putJson("/api/favorites/players/{$saka->id}", [], $auth)->assertOk();
    $this->putJson("/api/favorites/teams/{$arsenal->id}", [], $auth)->assertOk();

    $this->artisan('football:reseed')->assertSuccessful();

    // Ids changed, but the user, the token and the favorites (by identity) survived.
    expect(Player::where('last_name', 'Saka')->value('id'))->not->toBe($saka->id)
        ->and(User::where('email', 'nino@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'test@example.com')->exists())->toBeTrue();
    $this->getJson('/api/favorites', $auth)
        ->assertJsonPath('players.0.last_name', 'Saka')
        ->assertJsonPath('teams.0.short_code', 'ARS');
});

test('db:seed never overwrites or removes existing users', function () {
    Http::preventStrayRequests();
    Player::query()->delete();
    Team::query()->delete();
    registerUser()->assertCreated();
    User::factory()->create(['email' => 'test@example.com', 'name' => 'Renamed Demo']);

    $this->seed();

    expect(User::count())->toBe(2)
        ->and(User::where('email', 'test@example.com')->value('name'))->toBe('Renamed Demo');
});
