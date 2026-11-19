<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Club and player profile fields (Transfermarkt-style facts, financials and
 * Opta/Sofascore-style metrics). Money values are display strings such as
 * "€180.00m", matching how the frontend shows them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('short_name')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('city')->nullable();
            $table->string('stadium_name')->nullable();
            $table->unsignedInteger('stadium_capacity')->nullable();
            $table->string('coach_name')->nullable();
            $table->string('president')->nullable();
            $table->string('kit_sponsor')->nullable();

            $table->string('total_market_value')->nullable();
            $table->unsignedSmallInteger('squad_size')->nullable();
            $table->decimal('average_age', 4, 1)->nullable();
            $table->unsignedSmallInteger('foreigners_count')->nullable();
            $table->decimal('foreigners_pct', 4, 1)->nullable();

            $table->string('preferred_formation')->nullable();
            $table->string('transfer_income')->nullable();
            $table->string('transfer_expenditure')->nullable();
            $table->string('net_spend')->nullable();
            $table->string('annual_wage_bill')->nullable();

            $table->json('last_5_form')->nullable();
            $table->unsignedSmallInteger('clean_sheets')->nullable();
            $table->json('trophies')->nullable();
        });

        Schema::table('players', function (Blueprint $table) {
            // Transfermarkt core
            $table->unsignedTinyInteger('shirt_number')->nullable();
            $table->string('primary_position')->nullable();
            $table->json('secondary_positions')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_foot')->nullable();
            $table->string('market_value')->nullable();
            $table->string('highest_market_value')->nullable();
            $table->date('highest_market_value_date')->nullable();
            $table->date('contract_expires')->nullable();

            // Financials
            $table->string('weekly_salary')->nullable();
            $table->string('annual_salary')->nullable();
            $table->string('release_clause')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('boot_sponsor')->nullable();

            // Opta / Sofascore metrics (season)
            $table->string('playing_style')->nullable();
            $table->unsignedSmallInteger('appearances')->default(0);
            $table->unsignedSmallInteger('goals')->default(0);
            $table->unsignedSmallInteger('assists')->default(0);
            $table->unsignedInteger('minutes_played')->default(0);
            $table->unsignedSmallInteger('yellow_cards')->default(0);
            $table->unsignedSmallInteger('red_cards')->default(0);
            $table->decimal('avg_rating', 4, 2)->nullable();
            $table->decimal('xg', 5, 2)->nullable();
            $table->decimal('xa', 5, 2)->nullable();
            $table->unsignedSmallInteger('key_passes')->nullable();
            $table->decimal('pass_accuracy_pct', 4, 1)->nullable();
            $table->unsignedSmallInteger('progressive_carries')->nullable();
            $table->decimal('dribble_success_pct', 4, 1)->nullable();
            $table->unsignedSmallInteger('tackles_interceptions')->nullable();
            $table->decimal('aerial_duels_won_pct', 4, 1)->nullable();
            $table->json('heatmap_zones')->nullable();

            // Career, health & international
            $table->string('injury_status')->nullable();
            $table->json('injury_history')->nullable();
            $table->string('national_team')->nullable();
            $table->unsignedSmallInteger('intl_caps')->nullable();
            $table->unsignedSmallInteger('intl_goals')->nullable();
            $table->json('trophies')->nullable();
            $table->json('transfer_history')->nullable();
            $table->json('transfer_rumors')->nullable();

            // Visuals
            $table->string('cutout_image_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'short_name', 'primary_color', 'secondary_color', 'founded_year', 'city', 'stadium_name',
                'stadium_capacity', 'coach_name', 'president', 'kit_sponsor', 'total_market_value', 'squad_size',
                'average_age', 'foreigners_count', 'foreigners_pct', 'preferred_formation', 'transfer_income',
                'transfer_expenditure', 'net_spend', 'annual_wage_bill', 'last_5_form', 'clean_sheets', 'trophies',
            ]);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'shirt_number', 'primary_position', 'secondary_positions', 'age', 'height_cm', 'nationality',
                'preferred_foot', 'market_value', 'highest_market_value', 'highest_market_value_date',
                'contract_expires', 'weekly_salary', 'annual_salary', 'release_clause', 'agent_name', 'boot_sponsor',
                'playing_style', 'appearances', 'goals', 'assists', 'minutes_played', 'yellow_cards', 'red_cards',
                'avg_rating', 'xg', 'xa', 'key_passes', 'pass_accuracy_pct', 'progressive_carries',
                'dribble_success_pct', 'tackles_interceptions', 'aerial_duels_won_pct', 'heatmap_zones',
                'injury_status', 'injury_history', 'national_team', 'intl_caps', 'intl_goals', 'trophies',
                'transfer_history', 'transfer_rumors', 'cutout_image_url',
            ]);
        });
    }
};
