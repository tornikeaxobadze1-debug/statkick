<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamPlayer extends Pivot
{
    use HasFactory;

    protected $table = 'team_player';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'player_id',
        'season',
        'shirt_number',
        'is_current',
        'is_starter',
    ];

    protected $attributes = [
        'is_current' => true,
    ];

    protected function casts(): array
    {
        return [
            'shirt_number' => 'integer',
            'is_current' => 'boolean',
            'is_starter' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
