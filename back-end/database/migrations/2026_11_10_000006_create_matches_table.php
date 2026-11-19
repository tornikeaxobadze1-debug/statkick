<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->integer('home_score')->nullable()->default(0);
            $table->integer('away_score')->nullable()->default(0);
            $table->string('status')->nullable()->default('SCHEDULED');
            $table->dateTime('match_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
