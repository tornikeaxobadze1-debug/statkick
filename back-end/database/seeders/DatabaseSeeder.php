<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Safe to re-run: records are looked up by natural keys, fixtures are only
     * generated for leagues that don't have any yet, and club/player profiles are
     * recomputed from the curated data and the stored fixtures.
     */
    public function run(): void
    {
        DB::transaction(function () {
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                User::factory()->raw(['name' => 'Test User', 'email' => 'test@example.com']),
            );

            $this->call(FootballSeeder::class);
        });

        // HTTP lookups (TheSportsDB) run outside the transaction so they never hold the DB lock.
        $this->call(PlayerCutoutSeeder::class);
    }
}
