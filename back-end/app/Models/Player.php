<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'first_name',
        'last_name',
        'position',
        'date_of_birth',
        'country_id',
        'photo_url',
        'display_name',
        'cutout_source',
        'profile_estimates',
        // Transfermarkt core
        'shirt_number',
        'primary_position',
        'secondary_positions',
        'age',
        'height_cm',
        'nationality',
        'preferred_foot',
        'market_value',
        'highest_market_value',
        'highest_market_value_date',
        'contract_expires',
        // Financials
        'weekly_salary',
        'annual_salary',
        'release_clause',
        'agent_name',
        'boot_sponsor',
        // Opta / Sofascore metrics
        'playing_style',
        'appearances',
        'goals',
        'assists',
        'minutes_played',
        'yellow_cards',
        'red_cards',
        'avg_rating',
        'xg',
        'xa',
        'key_passes',
        'pass_accuracy_pct',
        'progressive_carries',
        'dribble_success_pct',
        'tackles_interceptions',
        'aerial_duels_won_pct',
        'heatmap_zones',
        // Career, health & international
        'injury_status',
        'injury_history',
        'national_team',
        'intl_caps',
        'intl_goals',
        'trophies',
        'transfer_history',
        'transfer_rumors',
        // Visuals
        'cutout_image_url',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'highest_market_value_date' => 'date:Y-m-d',
            'contract_expires' => 'date:Y-m-d',
            'secondary_positions' => 'array',
            'heatmap_zones' => 'array',
            'profile_estimates' => 'array',
            'injury_history' => 'array',
            'trophies' => 'array',
            'transfer_history' => 'array',
            'transfer_rumors' => 'array',
            'shirt_number' => 'integer',
            'age' => 'integer',
            'height_cm' => 'integer',
            'appearances' => 'integer',
            'goals' => 'integer',
            'assists' => 'integer',
            'minutes_played' => 'integer',
            'yellow_cards' => 'integer',
            'red_cards' => 'integer',
            'avg_rating' => 'float',
            'xg' => 'float',
            'xa' => 'float',
            'key_passes' => 'integer',
            'pass_accuracy_pct' => 'float',
            'progressive_carries' => 'integer',
            'dribble_success_pct' => 'float',
            'tackles_interceptions' => 'integer',
            'aerial_duels_won_pct' => 'float',
            'intl_caps' => 'integer',
            'intl_goals' => 'integer',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_player')
            ->using(TeamPlayer::class)
            ->withPivot('id', 'season', 'shirt_number', 'is_current', 'is_starter');
    }

    public function currentTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('is_current', true);
    }

    public function teamPlayers(): HasMany
    {
        return $this->hasMany(TeamPlayer::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(GameMatch::class, 'player_stats', 'player_id', 'match_id')
            ->withPivot('goals', 'assists', 'yellow_cards', 'red_cards', 'minutes_played', 'rating');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_players')
            ->using(FavoritePlayer::class)
            ->withTimestamps();
    }
}
