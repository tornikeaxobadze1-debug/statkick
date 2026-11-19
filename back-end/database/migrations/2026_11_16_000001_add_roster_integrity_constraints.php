<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roster integrity:
 *  - a player is one row: unique (first_name, last_name, date_of_birth), and unique
 *    TheSportsDB id when known;
 *  - a player has at most ONE current club: partial unique index on
 *    team_player(player_id) WHERE is_current.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('external_api_id')->nullable()->unique();
            $table->unique(['first_name', 'last_name', 'date_of_birth'], 'players_identity_unique');
        });

        // Partial indexes: SQLite and PostgreSQL. (MySQL has none; the seeder's
        // verifier still guarantees one current club per player there.)
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement('CREATE UNIQUE INDEX team_player_one_current_club ON team_player (player_id) WHERE is_current = '
                .(DB::getDriverName() === 'pgsql' ? 'true' : '1'));
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX IF EXISTS team_player_one_current_club');
        }

        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique('players_identity_unique');
            $table->dropUnique(['external_api_id']);
            $table->dropColumn('external_api_id');
        });
    }
};
