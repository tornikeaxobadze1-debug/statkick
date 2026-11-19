<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use Database\Seeders\FootballSeeder;
use Database\Seeders\PlayerCutoutSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the football data (countries, leagues, clubs, players, fixtures, stats,
 * photos) WITHOUT touching user accounts, tokens or preferences — use this instead of
 * `migrate:fresh --seed`, which wipes the users table.
 *
 * Favorites survive: player and club ids change on a rebuild, so each favorite is saved
 * by a stable identity (player name + date of birth, club code) and re-attached
 * afterwards. A favorite whose player/club is no longer in the database is reported.
 *
 * Runs in one transaction: until it commits, the API keeps serving the previous data,
 * so clients never see an empty database mid-rebuild.
 */
class ReseedFootball extends Command
{
    protected $signature = 'football:reseed';

    protected $description = 'Rebuild football data while keeping users and their favorites';

    /** Football tables, children first (deleted in this order). */
    private const TABLES = ['favorite_players', 'favorite_teams', 'player_stats', 'matches', 'team_player', 'players', 'teams', 'divisions', 'countries'];

    public function handle(): int
    {
        $users = DB::table('users')->count();
        $restored = ['players' => 0, 'teams' => 0];
        $lost = [];

        DB::transaction(function () use (&$restored, &$lost) {
            $favoritePlayers = DB::table('favorite_players')->join('players', 'players.id', '=', 'favorite_players.player_id')
                ->get(['favorite_players.user_id', 'players.first_name', 'players.last_name', 'players.date_of_birth', 'favorite_players.created_at', 'favorite_players.updated_at']);
            $favoriteTeams = DB::table('favorite_teams')->join('teams', 'teams.id', '=', 'favorite_teams.team_id')
                ->get(['favorite_teams.user_id', 'teams.short_code', 'teams.name', 'favorite_teams.created_at', 'favorite_teams.updated_at']);

            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }

            $this->callSilently('db:seed', ['--class' => FootballSeeder::class, '--force' => true]);
            $this->call('db:seed', ['--class' => PlayerCutoutSeeder::class, '--force' => true]);

            foreach ($favoritePlayers as $fav) {
                $player = Player::where(['first_name' => $fav->first_name, 'last_name' => $fav->last_name])
                    ->whereDate('date_of_birth', substr((string) $fav->date_of_birth, 0, 10))->first();
                if ($player === null) {
                    $lost[] = trim("{$fav->first_name} {$fav->last_name}")." (user {$fav->user_id})";

                    continue;
                }
                DB::table('favorite_players')->insertOrIgnore([
                    'user_id' => $fav->user_id, 'player_id' => $player->id, 'created_at' => $fav->created_at, 'updated_at' => $fav->updated_at,
                ]);
                $restored['players']++;
            }
            foreach ($favoriteTeams as $fav) {
                $team = Team::where('short_code', $fav->short_code)->first();
                if ($team === null) {
                    $lost[] = "{$fav->name} (user {$fav->user_id})";

                    continue;
                }
                DB::table('favorite_teams')->insertOrIgnore([
                    'user_id' => $fav->user_id, 'team_id' => $team->id, 'created_at' => $fav->created_at, 'updated_at' => $fav->updated_at,
                ]);
                $restored['teams']++;
            }
        });

        $this->info("Football data rebuilt. Kept {$users} users; restored {$restored['players']} favorite players and {$restored['teams']} favorite clubs.");
        if ($lost !== []) {
            $this->warn('Favorites no longer in the database (player/club removed): '.implode(', ', $lost));
        }

        return self::SUCCESS;
    }
}
