<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which profile fields were filled by a labelled rule-based estimate (no curated or
 * TheSportsDB value existed), e.g. ["weekly_salary", "height_cm"]. Empty = all real data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->json('profile_estimates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('profile_estimates');
        });
    }
};
