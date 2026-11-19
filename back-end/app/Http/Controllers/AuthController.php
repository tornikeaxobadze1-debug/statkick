<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/register — creates the account and returns the same payload as
     * POST /api/login ({user, token, token_type}), so the client is signed in at once.
     * password_confirmation is optional; when sent it must match.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => self::normaliseEmail($request->input('email'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => array_values(array_filter([
                'required', 'string',
                $request->has('password_confirmation') ? 'confirmed' : null,
                Password::defaults(), // min 8 outside production; stricter in production
            ])),
            'newsletter_subscribed' => ['sometimes', 'boolean'],
            'preferred_language' => ['sometimes', 'string', Rule::in(User::LANGUAGES)],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // hashed by the model's cast
            'newsletter_subscribed' => $data['newsletter_subscribed'] ?? false,
            'preferred_language' => $data['preferred_language'] ?? 'ka',
        ]);

        return response()->json([
            'user' => $user->fresh(),
            'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * POST /api/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->merge(['email' => self::normaliseEmail($request->input('email'))]);
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        // Case-insensitive: "Foo@Example.com" signs in to the account registered as "foo@example.com".
        $user = User::whereRaw('lower(email) = ?', [$data['email']])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * POST /api/logout — revokes only the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->loadCount(['favoritePlayers', 'favoriteTeams'])
        );
    }

    /**
     * GET /api/user — the signed-in user with their favorite players and clubs.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            ...$user->toArray(),
            ...FavoritesController::lists($user),
        ]);
    }

    private static function normaliseEmail(mixed $email): mixed
    {
        return is_string($email) ? strtolower(trim($email)) : $email;
    }

    /**
     * PUT /api/me/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'newsletter_subscribed' => ['sometimes', 'required', 'boolean'],
            'preferred_language' => ['sometimes', 'required', 'string', Rule::in(User::LANGUAGES)],
        ]);

        if ($data === []) {
            throw ValidationException::withMessages([
                'preferences' => ['Provide newsletter_subscribed and/or preferred_language.'],
            ]);
        }

        $user = $request->user();
        $user->update($data);

        return response()->json($user->only(['id', 'newsletter_subscribed', 'preferred_language']));
    }
}
