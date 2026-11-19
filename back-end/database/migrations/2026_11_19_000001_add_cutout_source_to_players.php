<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why the player's photo was chosen: "current_kit" (TheSportsDB club confirmed and the
 * upload postdates the move), "club_shoot" (uploaded with the club's photo batch),
 * "reviewed" (kit checked by eye), "other_kit" (a real photo of the player in another
 * club's or a national kit) or "fallback" (no photo exists; club-colour jersey).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('cutout_source', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('cutout_source');
        });
    }
};
