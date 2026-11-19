<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'player_id',
        'match_id',
        'goals',
        'assists',
        'yellow_cards',
        'red_cards',
        'minutes_played',
        'rating',
    ];

    protected $attributes = [
        'goals' => 0,
        'assists' => 0,
        'yellow_cards' => 0,
        'red_cards' => 0,
        'minutes_played' => 0,
    ];

    protected function casts(): array
    {
        return [
            'goals' => 'integer',
            'assists' => 'integer',
            'yellow_cards' => 'integer',
            'red_cards' => 'integer',
            'minutes_played' => 'integer',
            'rating' => 'float',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
