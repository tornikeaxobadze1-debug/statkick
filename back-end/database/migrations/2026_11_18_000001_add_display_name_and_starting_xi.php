<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - players.display_name: the short name shown on cards and lists ("Vinícius Jr.",
 *   "Saka", "E. García" when two clubmates share a surname);
 * - team_player.is_starter: the club's projected starting XI (exactly 11 per
 *   club, shaped by its preferred formation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('display_name')->nullable();
        });

        Schema::table('team_player', function (Blueprint $table) {
            $table->boolean('is_starter')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('team_player', function (Blueprint $table) {
            $table->dropColumn('is_starter');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
