<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A football match. Named GameMatch because `match` is a reserved keyword in PHP 8+.
 */
class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    public $timestamps = false;

    protected $fillable = [
        'division_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'status',
        'match_date',
    ];

    protected $attributes = [
        'home_score' => 0,
        'away_score' => 0,
        'status' => 'SCHEDULED',
    ];

    protected function casts(): array
    {
        return [
            'home_score' => 'integer',
            'away_score' => 'integer',
            'match_date' => 'datetime',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStat::class, 'match_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_stats', 'match_id', 'player_id')
            ->withPivot('goals', 'assists', 'yellow_cards', 'red_cards', 'minutes_played', 'rating');
    }
}
