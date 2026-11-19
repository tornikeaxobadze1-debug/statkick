<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->integer('goals')->nullable()->default(0);
            $table->integer('assists')->nullable()->default(0);
            $table->integer('yellow_cards')->nullable()->default(0);
            $table->integer('red_cards')->nullable()->default(0);
            $table->integer('minutes_played')->nullable()->default(0);
            $table->float('rating')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
