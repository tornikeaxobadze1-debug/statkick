<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $newsletter_subscribed
 * @property string $preferred_language
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'newsletter_subscribed', 'preferred_language'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Languages a user can pick for `preferred_language`. */
    public const LANGUAGES = ['ka', 'en'];

    /**
     * Defaults mirrored from the migration, so a freshly created model has them too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'newsletter_subscribed' => false,
        'preferred_language' => 'ka',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'newsletter_subscribed' => 'boolean',
        ];
    }

    public function favoritePlayers(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'favorite_players')
            ->using(FavoritePlayer::class)
            ->withTimestamps();
    }

    public function favoriteTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'favorite_teams')
            ->using(FavoriteTeam::class)
            ->withTimestamps();
    }
}
