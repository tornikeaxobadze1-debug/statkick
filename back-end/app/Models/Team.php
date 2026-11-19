<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'short_code',
        'division_id',
        'country_id',
        'logo_url',
        'short_name',
        'primary_color',
        'secondary_color',
        'founded_year',
        'city',
        'stadium_name',
        'stadium_capacity',
        'coach_name',
        'president',
        'kit_sponsor',
        'total_market_value',
        'squad_size',
        'average_age',
        'foreigners_count',
        'foreigners_pct',
        'preferred_formation',
        'transfer_income',
        'transfer_expenditure',
        'net_spend',
        'annual_wage_bill',
        'last_5_form',
        'clean_sheets',
        'trophies',
    ];

    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
            'stadium_capacity' => 'integer',
            'squad_size' => 'integer',
            'average_age' => 'float',
            'foreigners_count' => 'integer',
            'foreigners_pct' => 'float',
            'last_5_form' => 'array',
            'clean_sheets' => 'integer',
            'trophies' => 'array',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_player')
            ->using(TeamPlayer::class)
            ->withPivot('id', 'season', 'shirt_number', 'is_current', 'is_starter');
    }

    public function currentPlayers(): BelongsToMany
    {
        return $this->players()->wherePivot('is_current', true);
    }

    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class);
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    /**
     * All matches (home or away). Returns a query builder, not a relation.
     *
     * @return Builder<GameMatch>
     */
    public function matches(): Builder
    {
        return GameMatch::query()
            ->where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_teams')
            ->using(FavoriteTeam::class)
            ->withTimestamps();
    }
}
