<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Division;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerStat;
use App\Models\Team;
use App\Models\TeamPlayer;
use App\Support\Roster\PlayerDataValidator;
use App\Support\Roster\PlayerRosterVerifier;
use Database\Seeders\Support\TheSportsDb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Five leagues (England, Spain, Italy, Germany, France) with clubs, squads,
 * a double round-robin fixture list and per-player match stats.
 *
 * Squads are real players at their clubs as of the 2025/26 season (verify before
 * relying on them — transfers move fast). Results, goals, assists, cards and match
 * ratings are GENERATED (deterministically, from a fixed seed):
 *   - each player's match ratings stay in a band around their `overall`, so their
 *     average ×10 (the card rating) lands close to it;
 *   - a squad here is only the club's headline players, so ~40% of a team's goals
 *     are credited to teammates who aren't in the database — player goals never
 *     exceed the team's score.
 *
 * Club and player profiles (stadium, finances, market values, honours, transfers…)
 * come from the curated reference data in data/football_profiles.php. Season
 * metrics on players (appearances, goals, xG, key passes…) and clubs (last 5 form,
 * clean sheets) are derived from the generated fixtures so every endpoint agrees.
 * Heatmaps and transfer rumours are generated.
 *
 * photo_url is left null; PlayerCutoutSeeder fills cutout_image_url from TheSportsDB.
 */
class FootballSeeder extends Seeder
{
    /** Matches are dated around "now" (autumn 2026), i.e. the 2026/27 season. */
    private const SEASON = '2026/27';

    /** Every club plays at least this many finished league matches. */
    private const MIN_GAMES_PER_TEAM = 10;

    /** Share of a team's goals scored by players listed in its squad here. */
    private const SQUAD_GOAL_SHARE = 60;

    private const RNG_SEED = 20260812;

    /**
     * Nationalities used by players, keyed by FIFA code (league countries are created
     * from LEAGUES). Every player's country must resolve to one of these or a league
     * country, so the API always returns a code; an unknown nationality fails the seed.
     */
    private const NATIONS = [
        'ARG' => 'Argentina', 'BEL' => 'Belgium', 'BRA' => 'Brazil', 'CHI' => 'Chile', 'COL' => 'Colombia', 'CRO' => 'Croatia', 'CZE' => 'Czechia',
        'DEN' => 'Denmark', 'GEO' => 'Georgia', 'ECU' => 'Ecuador', 'KOR' => 'South Korea', 'EGY' => 'Egypt', 'GUI' => 'Guinea', 'GAB' => 'Gabon',
        'JPN' => 'Japan', 'MAR' => 'Morocco', 'NED' => 'Netherlands', 'NOR' => 'Norway', 'POL' => 'Poland',
        'POR' => 'Portugal', 'RUS' => 'Russia', 'SCO' => 'Scotland', 'SRB' => 'Serbia', 'SUI' => 'Switzerland',
        'SVN' => 'Slovenia', 'TUR' => 'Turkey', 'URU' => 'Uruguay', 'USA' => 'United States',
        // Nationalities of squad members added from TheSportsDB (which supplies names only).
        'ALG' => 'Algeria', 'ARM' => 'Armenia', 'AUT' => 'Austria', 'BFA' => 'Burkina Faso', 'CMR' => 'Cameroon',
        'CAN' => 'Canada', 'FIN' => 'Finland', 'GHA' => 'Ghana', 'GRE' => 'Greece', 'HUN' => 'Hungary',
        'CIV' => 'Ivory Coast', 'KVX' => 'Kosovo', 'LUX' => 'Luxembourg', 'MEX' => 'Mexico', 'NGA' => 'Nigeria',
        'NIR' => 'Northern Ireland', 'PAR' => 'Paraguay', 'SEN' => 'Senegal', 'SVK' => 'Slovakia', 'SWE' => 'Sweden',
        'UKR' => 'Ukraine',
        // In the TheSportsDB cache but not in a current squad: coded so a roster refresh can't fail.
        'AUS' => 'Australia', 'CTA' => 'Central African Republic', 'IRL' => 'Ireland', 'ISR' => 'Israel',
        'JAM' => 'Jamaica', 'MKD' => 'North Macedonia', 'PER' => 'Peru', 'UZB' => 'Uzbekistan',
    ];

    /**
     * country code => [country name, league name, [team code => [team name, [players...]]]]
     * player: [first, last, position, date_of_birth, nationality code, shirt, overall]
     * Players known by a single name are stored with an empty first name.
     */
    public const LEAGUES = [
        'ENG' => ['England', 'Premier League', [
            'ARS' => ['Arsenal', [
                ['David', 'Raya', 'Goalkeeper', '1995-09-15', 'ESP', 22, 86],
                ['William', 'Saliba', 'Defender', '2001-03-24', 'FRA', 2, 87],
                ['Gabriel', 'Magalhães', 'Defender', '1997-12-19', 'BRA', 6, 87],
                ['Declan', 'Rice', 'Midfielder', '1999-01-14', 'ENG', 41, 87],
                ['Martin', 'Ødegaard', 'Midfielder', '1998-12-17', 'NOR', 8, 87],
                ['Bukayo', 'Saka', 'Forward', '2001-09-05', 'ENG', 7, 88],
            ]],
            'MCI' => ['Manchester City', [
                ['Rúben', 'Dias', 'Defender', '1997-05-14', 'POR', 3, 87],
                ['', 'Rodri', 'Midfielder', '1996-06-22', 'ESP', 16, 90],
                ['Phil', 'Foden', 'Midfielder', '2000-05-28', 'ENG', 47, 86],
                ['Erling', 'Haaland', 'Forward', '2000-07-21', 'NOR', 9, 91],
                ['', 'Savinho', 'Forward', '2004-04-10', 'BRA', 26, 82], // not on TheSportsDB; placed by the MCI lock
                ['Rayan', 'Cherki', 'Midfielder', '2003-08-17', 'FRA', 10, 85],
                ['Jérémy', 'Doku', 'Forward', '2002-05-27', 'BEL', 11, 84],
            ]],
            'LIV' => ['Liverpool', [
                ['', 'Alisson', 'Goalkeeper', '1992-10-02', 'BRA', 1, 88],
                ['Virgil', 'van Dijk', 'Defender', '1991-07-08', 'NED', 4, 89],
                ['Alexis', 'Mac Allister', 'Midfielder', '1998-12-24', 'ARG', 10, 87],
                ['Florian', 'Wirtz', 'Midfielder', '2003-05-03', 'GER', 7, 88],
                ['Mohamed', 'Salah', 'Forward', '1992-06-15', 'EGY', 11, 90],
            ]],
            // Squad comes entirely from TheSportsDB-verified players (squad lookup + candidates).
            'MUN' => ['Manchester United', []],
            'CHE' => ['Chelsea', [
                ['Robert', 'Sánchez', 'Goalkeeper', '1997-11-18', 'ESP', 1, 80],
                ['Reece', 'James', 'Defender', '1999-12-08', 'ENG', 24, 84],
                ['Moisés', 'Caicedo', 'Midfielder', '2001-11-02', 'ECU', 25, 86],
                ['Enzo', 'Fernández', 'Midfielder', '2001-01-17', 'ARG', 8, 85],
                ['Cole', 'Palmer', 'Midfielder', '2002-05-06', 'ENG', 10, 87],
                ['Malo', 'Gusto', 'Defender', '2003-05-19', 'FRA', 27, 82],
                ['Wesley', 'Fofana', 'Defender', '2000-12-17', 'FRA', 29, 81],
                ['Roméo', 'Lavia', 'Midfielder', '2004-01-06', 'BEL', 45, 80],
            ]],
        ]],
        'ESP' => ['Spain', 'La Liga', [
            'RMA' => ['Real Madrid', [
                ['Thibaut', 'Courtois', 'Goalkeeper', '1992-05-11', 'BEL', 1, 89],
                ['Antonio', 'Rüdiger', 'Defender', '1993-03-03', 'GER', 22, 85],
                ['Jude', 'Bellingham', 'Midfielder', '2003-06-29', 'ENG', 5, 89],
                ['Vinícius', 'Júnior', 'Forward', '2000-07-12', 'BRA', 7, 89],
                ['Kylian', 'Mbappé', 'Forward', '1998-12-20', 'FRA', 10, 91],
                ['Franco', 'Mastantuono', 'Midfielder', '2007-08-14', 'ARG', 30, 80],
                ['Dani', 'Carvajal', 'Defender', '1992-01-11', 'ESP', 2, 83],
                ['Aurélien', 'Tchouaméni', 'Midfielder', '2000-01-27', 'FRA', 14, 85],
                ['Eduardo', 'Camavinga', 'Midfielder', '2002-11-10', 'FRA', 6, 83],
            ]],
            // Full first-team spine, not just headline players.
            'BAR' => ['Barcelona', [
                ['Marc-André', 'ter Stegen', 'Goalkeeper', '1992-04-30', 'GER', 1, 85],
                ['Jules', 'Koundé', 'Defender', '1998-11-12', 'FRA', 23, 85],
                ['Ronald', 'Araújo', 'Defender', '1999-03-07', 'URU', 4, 84],
                ['Pau', 'Cubarsí', 'Defender', '2007-01-22', 'ESP', 5, 82],
                ['Alejandro', 'Balde', 'Defender', '2003-10-18', 'ESP', 3, 83],
                ['Frenkie', 'de Jong', 'Midfielder', '1997-05-12', 'NED', 21, 86],
                ['', 'Pedri', 'Midfielder', '2002-11-25', 'ESP', 8, 88],
                ['', 'Gavi', 'Midfielder', '2004-08-05', 'ESP', 6, 84],
                ['Dani', 'Olmo', 'Midfielder', '1998-05-07', 'ESP', 20, 85],
                ['Lamine', 'Yamal', 'Forward', '2007-07-13', 'ESP', 10, 89],
                ['Robert', 'Lewandowski', 'Forward', '1988-08-21', 'POL', 9, 86],
                ['', 'Raphinha', 'Forward', '1996-12-14', 'BRA', 11, 87],
            ]],
            'ATM' => ['Atlético Madrid', [
                ['Jan', 'Oblak', 'Goalkeeper', '1993-01-07', 'SVN', 13, 87],
                ['José María', 'Giménez', 'Defender', '1995-01-20', 'URU', 2, 82],
                ['', 'Koke', 'Midfielder', '1992-01-08', 'ESP', 6, 81],
                ['Antoine', 'Griezmann', 'Forward', '1991-03-21', 'FRA', 7, 85],
                ['Julián', 'Álvarez', 'Forward', '2000-01-31', 'ARG', 19, 87],
            ]],
        ]],
        'ITA' => ['Italy', 'Serie A', [
            'INT' => ['Inter', [
                ['Yann', 'Sommer', 'Goalkeeper', '1988-12-17', 'SUI', 1, 84],
                ['Alessandro', 'Bastoni', 'Defender', '1999-04-13', 'ITA', 95, 87],
                ['Nicolò', 'Barella', 'Midfielder', '1997-02-07', 'ITA', 23, 87],
                ['Hakan', 'Çalhanoğlu', 'Midfielder', '1994-02-08', 'TUR', 20, 85],
                ['Lautaro', 'Martínez', 'Forward', '1997-08-22', 'ARG', 10, 89],
                ['Marcus', 'Thuram', 'Forward', '1997-08-06', 'FRA', 9, 85],
                ['Francesco Pio', 'Esposito', 'Forward', '2005-06-28', 'ITA', 94, 77],
            ]],
            'MIL' => ['Milan', [
                ['Mike', 'Maignan', 'Goalkeeper', '1995-07-03', 'FRA', 16, 87],
                ['Fikayo', 'Tomori', 'Defender', '1997-12-19', 'ENG', 23, 81],
                ['Luka', 'Modrić', 'Midfielder', '1985-09-09', 'CRO', 14, 84],
                ['Christian', 'Pulisic', 'Forward', '1998-09-18', 'USA', 11, 84],
                ['Rafael', 'Leão', 'Forward', '1999-06-10', 'POR', 10, 85],
                ['Koni', 'De Winter', 'Defender', '2002-06-12', 'BEL', 5, 79],
                ['Alexis', 'Saelemaekers', 'Midfielder', '1999-06-27', 'BEL', 56, 80],
                ['Adrien', 'Rabiot', 'Midfielder', '1995-04-03', 'FRA', 12, 83],
            ]],
            'JUV' => ['Juventus', [
                ['Gleison', 'Bremer', 'Defender', '1997-03-18', 'BRA', 3, 84],
                ['Manuel', 'Locatelli', 'Midfielder', '1998-01-08', 'ITA', 5, 80],
                ['Kenan', 'Yıldız', 'Forward', '2005-05-04', 'TUR', 10, 83],
                ['Dušan', 'Vlahović', 'Forward', '2000-01-28', 'SRB', 9, 82],
            ]],
            'NAP' => ['Napoli', [
                ['Alex', 'Meret', 'Goalkeeper', '1997-03-22', 'ITA', 1, 81],
                ['Giovanni', 'Di Lorenzo', 'Defender', '1993-08-04', 'ITA', 22, 82],
                ['Scott', 'McTominay', 'Midfielder', '1996-12-08', 'SCO', 8, 85],
                ['Kevin', 'De Bruyne', 'Midfielder', '1991-06-28', 'BEL', 11, 86],
                ['Romelu', 'Lukaku', 'Forward', '1993-05-13', 'BEL', 9, 82],
            ]],
        ]],
        'GER' => ['Germany', 'Bundesliga', [
            'FCB' => ['Bayern Munich', [
                ['Manuel', 'Neuer', 'Goalkeeper', '1986-03-27', 'GER', 1, 85],
                ['Joshua', 'Kimmich', 'Midfielder', '1995-02-08', 'GER', 6, 87],
                ['Jamal', 'Musiala', 'Midfielder', '2003-02-26', 'GER', 10, 88],
                ['Michael', 'Olise', 'Forward', '2001-12-12', 'FRA', 17, 87],
                ['Harry', 'Kane', 'Forward', '1993-07-28', 'ENG', 9, 90],
            ]],
            'B04' => ['Bayer Leverkusen', [
                ['Alejandro', 'Grimaldo', 'Defender', '1995-09-20', 'ESP', 20, 84],
                ['Robert', 'Andrich', 'Midfielder', '1994-09-22', 'GER', 8, 80],
                ['Exequiel', 'Palacios', 'Midfielder', '1998-10-05', 'ARG', 25, 82],
                ['Malik', 'Tillman', 'Midfielder', '2002-05-28', 'USA', 10, 80],
                ['Patrik', 'Schick', 'Forward', '1996-01-24', 'CZE', 14, 82],
            ]],
            'BVB' => ['Borussia Dortmund', [
                ['Gregor', 'Kobel', 'Goalkeeper', '1997-12-06', 'SUI', 1, 86],
                ['Nico', 'Schlotterbeck', 'Defender', '1999-12-01', 'GER', 4, 84],
                ['Julian', 'Brandt', 'Midfielder', '1996-05-02', 'GER', 10, 82],
                ['Karim', 'Adeyemi', 'Forward', '2002-01-18', 'GER', 27, 80],
                ['Serhou', 'Guirassy', 'Forward', '1996-03-12', 'GUI', 9, 85],
            ]],
        ]],
        'FRA' => ['France', 'Ligue 1', [
            'PSG' => ['Paris Saint-Germain', [
                ['Lucas', 'Chevalier', 'Goalkeeper', '2001-11-06', 'FRA', 30, 83],
                ['Achraf', 'Hakimi', 'Defender', '1998-11-04', 'MAR', 2, 88],
                ['', 'Marquinhos', 'Defender', '1994-05-14', 'BRA', 5, 86],
                ['', 'Vitinha', 'Midfielder', '2000-02-13', 'POR', 17, 88],
                ['Ousmane', 'Dembélé', 'Forward', '1997-05-15', 'FRA', 10, 90],
                ['Khvicha', 'Kvaratskhelia', 'Forward', '2001-02-12', 'GEO', 7, 88],
                ['Lucas', 'Hernández', 'Defender', '1996-02-14', 'FRA', 21, 82],
                ['Warren', 'Zaïre-Emery', 'Midfielder', '2006-03-08', 'FRA', 33, 81],
                ['Lee', 'Kang-in', 'Midfielder', '2001-02-19', 'KOR', 19, 81],
                ['Désiré', 'Doué', 'Forward', '2005-06-03', 'FRA', 14, 85],
            ]],
            'ASM' => ['Monaco', [
                ['Thilo', 'Kehrer', 'Defender', '1996-09-21', 'GER', 5, 78],
                ['Denis', 'Zakaria', 'Midfielder', '1996-11-20', 'SUI', 6, 81],
                ['Aleksandr', 'Golovin', 'Midfielder', '1996-05-30', 'RUS', 10, 80],
                ['Takumi', 'Minamino', 'Forward', '1995-01-16', 'JPN', 18, 79],
                ['Folarin', 'Balogun', 'Forward', '2001-07-03', 'USA', 9, 79],
            ]],
        ]],
    ];

    /** Real past spells kept as non-current career entries: [first, last, team code, season, shirt]. */
    private const CAREER_HISTORY = [
        ['Florian', 'Wirtz', 'B04', '2024/25', 10],
        ['Luka', 'Modrić', 'RMA', '2024/25', 10],
        ['Kevin', 'De Bruyne', 'MCI', '2024/25', 17],
    ];

    /** Goal and assist weights by position. */
    private const GOAL_WEIGHT = ['Goalkeeper' => 0, 'Defender' => 1, 'Midfielder' => 3, 'Forward' => 7];

    private const ASSIST_WEIGHT = ['Goalkeeper' => 0.2, 'Defender' => 2, 'Midfielder' => 5, 'Forward' => 3];

    public function run(): void
    {
        mt_srand(self::RNG_SEED);

        $nations = [];
        foreach (self::NATIONS as $code => $name) {
            $nations[$code] = Country::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (self::LEAGUES as $code => [$countryName]) {
            $nations[$code] = Country::firstOrCreate(['code' => $code], ['name' => $countryName]);
        }
        $nationByName = [];
        foreach ($nations as $nation) {
            $nationByName[$nation->name] = $nation;
        }

        $tsdb = TheSportsDb::loadCache();
        $profiles = require __DIR__.'/data/football_profiles.php';
        $overrides = $profiles['roster_overrides'] ?? [];
        // squad_facts shirt numbers are reviewed numbers too; explicit overrides win.
        foreach ($profiles['squad_facts'] ?? [] as $code => $facts) {
            $overrides['shirt_numbers'][$code] = ($overrides['shirt_numbers'][$code] ?? []) + array_filter(array_map(fn ($f) => $f[0], $facts));
        }
        $verifier = new PlayerRosterVerifier(self::LEAGUES, $tsdb, $overrides);
        $currentSquads = $verifier->squads();
        $this->reportVerification($verifier->log());

        $teamsByCode = [];
        $strengths = []; // team id => club strength, for the match simulation
        $roster = [];
        foreach (self::LEAGUES as $countryCode => [, $leagueName, $clubs]) {
            $country = $nations[$countryCode] ?? throw new \LogicException("Missing country {$countryCode}");
            $division = Division::firstOrCreate(['name' => $leagueName], ['country_id' => $country->id]);

            $squads = [];
            $leagueTeams = [];
            foreach ($clubs as $teamCode => [$teamName]) {
                $team = Team::firstOrCreate(
                    ['name' => $teamName],
                    ['short_code' => $teamCode, 'division_id' => $division->id, 'country_id' => $country->id],
                );
                $teamsByCode[$teamCode] = $team;
                $leagueTeams[] = $team;

                $strengths[$team->id] = $profiles['clubs'][$teamCode]['strength'] ?? self::DEFAULT_STRENGTH;
                foreach ($currentSquads[$teamCode] as $member) {
                    if ($member['nation_code'] === null) { // added from TheSportsDB: no hand-set rating
                        $member['overall'] = $strengths[$team->id] - 4;
                    }
                    $nation = $member['nation_code'] !== null
                        ? $nations[$member['nation_code']]
                        : ($nationByName[self::countryName($member['nation_name'] ?? '')] ?? throw new \LogicException(
                            "No country code for nationality '{$member['nation_name']}' ({$member['first']} {$member['last']}): add it to NATIONS."
                        ));
                    // whereDate: the column stores "Y-m-d 00:00:00", so a plain equality on "Y-m-d" never matches.
                    $player = Player::where(['first_name' => $member['first'], 'last_name' => $member['last']])
                        ->whereDate('date_of_birth', $member['dob'])
                        ->first()
                        ?? Player::create([
                            'first_name' => $member['first'], 'last_name' => $member['last'], 'date_of_birth' => $member['dob'],
                            'position' => $member['position'], 'country_id' => $nation->id, 'external_api_id' => $member['external_id'],
                        ]);
                    TeamPlayer::firstOrCreate(
                        ['team_id' => $team->id, 'player_id' => $player->id, 'season' => self::SEASON],
                        ['shirt_number' => PlayerDataValidator::clean(['shirt_number' => $member['shirt']])[0]['shirt_number'], 'is_current' => true],
                    );
                    $squads[$team->id][] = ['player' => $player, 'position' => $member['position'], 'overall' => $member['overall']];
                    $roster[] = [...$member, 'player' => $player, 'team' => $team, 'nationality' => $nation->name];
                }
            }

            // Generate fixtures once per league so re-running the seeder doesn't duplicate them.
            // A league needs at least two clubs to play fixtures.
            if (count($leagueTeams) >= 2 && ! GameMatch::where('division_id', $division->id)->exists()) {
                $this->seedFixtures($division, $leagueTeams, $squads, $strengths);
            }
        }

        foreach (self::CAREER_HISTORY as [$first, $last, $teamCode, $season, $shirt]) {
            $player = Player::where(['first_name' => $first, 'last_name' => $last])->first();
            if ($player && isset($teamsByCode[$teamCode])) {
                TeamPlayer::firstOrCreate(
                    ['team_id' => $teamsByCode[$teamCode]->id, 'player_id' => $player->id, 'season' => $season],
                    ['shirt_number' => $shirt, 'is_current' => false],
                );
            }
        }

        $this->seedClubProfiles($teamsByCode, $profiles, $tsdb);
        $this->seedPlayerProfiles($roster, $profiles);
        $this->seedDisplayNamesAndLineups($teamsByCode, $profiles);
    }

    /**
     * Prints the verifier's decisions: every block, move and cap in full, additions
     * as a count (there are many).
     *
     * @param  list<array{level: string, message: string}>  $log
     */
    private function reportVerification(array $log): void
    {
        foreach ($log as ['level' => $level, 'message' => $message]) {
            if ($level !== 'ADDED') {
                $this->command?->warn(sprintf('  %-7s %s', $level, $message));
            }
        }
        $counts = collect($log)->countBy('level');
        $this->command?->info(sprintf(
            '  Roster verification: %d added from TheSportsDB, %d moved to their true club, %d blocked, %d capped, %d excluded and %d included by manual review.',
            $counts['ADDED'] ?? 0, $counts['MOVED'] ?? 0, $counts['BLOCKED'] ?? 0, $counts['CAPPED'] ?? 0, $counts['EXCLUDED'] ?? 0, $counts['MANUAL'] ?? 0,
        ));
    }

    /** TheSportsDB position => our position code. */
    private static function positionCode(?string $position, string $category): string
    {
        // Compared without spaces/hyphens/case: TheSportsDB writes "Left Wing", "Centre Forward"…
        $key = strtolower(preg_replace('/[^a-z]/i', '', (string) $position));

        return [
            'goalkeeper' => 'GK', 'centreback' => 'CB', 'centerback' => 'CB', 'sweeper' => 'CB', 'defender' => 'CB',
            'leftback' => 'LB', 'leftwingback' => 'LB', 'rightback' => 'RB', 'rightwingback' => 'RB',
            'defensivemidfield' => 'DM', 'defensivemidfielder' => 'DM', 'centralmidfield' => 'CM', 'centralmidfielder' => 'CM',
            'midfielder' => 'CM', 'midfield' => 'CM', 'attackingmidfield' => 'AM', 'attackingmidfielder' => 'AM',
            'leftmidfield' => 'LM', 'leftmidfielder' => 'LM', 'rightmidfield' => 'RM', 'rightmidfielder' => 'RM',
            'leftwing' => 'LW', 'leftwinger' => 'LW', 'rightwing' => 'RW', 'rightwinger' => 'RW', 'winger' => 'RW',
            'secondstriker' => 'SS', 'centreforward' => 'CF', 'centerforward' => 'CF', 'striker' => 'CF', 'forward' => 'CF',
        ][$key] ?? ['Goalkeeper' => 'GK', 'Defender' => 'CB', 'Midfielder' => 'CM', 'Forward' => 'CF'][$category];
    }

    /** TheSportsDB nationality => the country name used here. */
    private static function countryName(string $name): string
    {
        return [
            'The Netherlands' => 'Netherlands', 'Holland' => 'Netherlands', 'USA' => 'United States',
            'Czech Republic' => 'Czechia', 'Korea Republic' => 'South Korea', 'Türkiye' => 'Turkey',
            "Côte d'Ivoire" => 'Ivory Coast', "Cote d'Ivoire" => 'Ivory Coast', 'Republic of Kosovo' => 'Kosovo',
            'Macedonia' => 'North Macedonia', 'FYR Macedonia' => 'North Macedonia', 'Republic of Ireland' => 'Ireland',
        ][$name] ?? $name;
    }

    /**
     * Double round-robin, repeated until every club has played MIN_GAMES_PER_TEAM
     * matches, one round per week. The final two rounds are in the future (SCHEDULED);
     * everything before is FINISHED with generated scores and player stats.
     *
     * @param  list<Team>  $teams
     * @param  array<int, list<array{player: Player, position: string, overall: int}>>  $squads
     * @param  array<int, int>  $strengths  team id => club strength (football_profiles `strength`)
     */
    private function seedFixtures(Division $division, array $teams, array $squads, array $strengths): void
    {
        $cycle = $this->roundRobin($teams);
        $gamesPerCycle = 2 * (count($teams) - 1);
        $cycles = (int) ceil(self::MIN_GAMES_PER_TEAM / $gamesPerCycle);
        // Two extra rounds become the upcoming (SCHEDULED) fixtures.
        $rounds = array_merge(...array_fill(0, $cycles, $cycle));
        $rounds = [...$rounds, ...array_slice($cycle, 0, 2)];
        $kickoff = Carbon::now()->startOfWeek()->addDays(5)->setTime(17, 30)->subWeeks(count($rounds) - 2);

        foreach ($rounds as $i => $pairings) {
            $date = $kickoff->copy()->addWeeks($i);
            foreach ($pairings as $slot => [$home, $away]) {
                $matchDate = $date->copy()->addHours($slot * 2);
                $finished = $matchDate->isPast();

                $match = GameMatch::create([
                    'division_id' => $division->id,
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'status' => $finished ? 'FINISHED' : 'SCHEDULED',
                    'match_date' => $matchDate,
                    'home_score' => 0,
                    'away_score' => 0,
                ]);

                if ($finished) {
                    $homeSquad = $squads[$home->id] ?? [];
                    $awaySquad = $squads[$away->id] ?? [];
                    $homeStrength = $strengths[$home->id] ?? self::DEFAULT_STRENGTH;
                    $awayStrength = $strengths[$away->id] ?? self::DEFAULT_STRENGTH;
                    $homeGoals = $this->poisson($this->expectedGoals($homeStrength, $awayStrength) * self::HOME_ADVANTAGE);
                    $awayGoals = $this->poisson($this->expectedGoals($awayStrength, $homeStrength) / self::HOME_ADVANTAGE);
                    $match->update(['home_score' => $homeGoals, 'away_score' => $awayGoals]);

                    $this->seedPlayerStats($match, $homeSquad, $homeGoals, $awayGoals);
                    $this->seedPlayerStats($match, $awaySquad, $awayGoals, $homeGoals);
                }
            }
        }
    }

    /**
     * Circle-method schedule: every team meets every other team home and away.
     *
     * @param  list<Team>  $teams
     * @return list<list<array{0: Team, 1: Team}>>
     */
    private function roundRobin(array $teams): array
    {
        if (count($teams) % 2 === 1) {
            $teams[] = null; // bye
        }
        $n = count($teams);
        $firstLeg = [];
        for ($r = 0; $r < $n - 1; $r++) {
            $round = [];
            for ($i = 0; $i < $n / 2; $i++) {
                [$a, $b] = [$teams[$i], $teams[$n - 1 - $i]];
                if ($a && $b) {
                    $round[] = $r % 2 === 0 ? [$a, $b] : [$b, $a];
                }
            }
            $firstLeg[] = $round;
            // Rotate all but the first team.
            $teams = [$teams[0], $teams[$n - 1], ...array_slice($teams, 1, $n - 2)];
        }
        $secondLeg = array_map(fn ($round) => array_map(fn ($p) => [$p[1], $p[0]], $round), $firstLeg);

        return [...$firstLeg, ...$secondLeg];
    }

    /** Strength for a club without a rating. */
    private const DEFAULT_STRENGTH = 82;

    /** Home side scores ~10% more, the visitors ~10% less (typical of the top leagues). */
    private const HOME_ADVANTAGE = 1.1;

    /**
     * Expected goals for a side from the two clubs' strength ratings. Moderate on
     * purpose: ~0.05 goals per rating point, so even a big gap (10 points) gives
     * roughly 1.9 v 0.9 — favourites usually win, upsets still happen, and nobody
     * goes a season without a win. Clamped to a believable 0.55–2.6.
     */
    private function expectedGoals(float $attack, float $defence): float
    {
        return max(0.55, min(2.6, 1.4 + ($attack - $defence) * 0.05));
    }

    /** Knuth's Poisson sampler, capped to keep scorelines believable. */
    private function poisson(float $lambda): int
    {
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);

        return min($k - 1, 5);
    }

    /**
     * Distributes the team's goals and assists among its squad, then adds minutes,
     * cards and a match rating around each player's overall (nudged by the result).
     *
     * @param  list<array{player: Player, position: string, overall: int}>  $squad
     */
    private function seedPlayerStats(GameMatch $match, array $squad, int $scored, int $conceded): void
    {
        if ($squad === []) {
            return;
        }

        $goals = array_fill(0, count($squad), 0);
        $assists = array_fill(0, count($squad), 0);
        for ($g = 0; $g < $scored; $g++) {
            if (mt_rand(1, 100) > self::SQUAD_GOAL_SHARE) {
                continue; // scored by a teammate not in the database
            }
            $scorer = $this->weightedPick($squad, self::GOAL_WEIGHT);
            $goals[$scorer]++;
            if (count($squad) > 1 && mt_rand(1, 100) <= 65) {
                $assister = $this->weightedPick($squad, self::ASSIST_WEIGHT, exclude: $scorer);
                $assists[$assister]++;
            }
        }

        $resultBoost = $scored <=> $conceded; // +1 win, 0 draw, -1 loss
        foreach ($squad as $i => ['player' => $player, 'position' => $position, 'overall' => $overall]) {
            $minutes = $position === 'Goalkeeper' || mt_rand(1, 100) <= 75 ? 90 : mt_rand(58, 89);
            $yellow = mt_rand(1, 100) <= ($position === 'Defender' ? 20 : 10) ? 1 : 0;
            $red = mt_rand(1, 1000) <= 12 ? 1 : 0;

            // Form swings around the player's level, but stays within a band of it.
            $base = $overall / 10;
            $rating = $base
                + (mt_rand(-50, 50) / 100)
                + $goals[$i] * 0.25
                + $assists[$i] * 0.15
                + $resultBoost * 0.15
                - $red * 1.5;
            $rating = max($base - 0.9, min($base + 0.6, $rating));

            PlayerStat::create([
                'player_id' => $player->id,
                'match_id' => $match->id,
                'goals' => $goals[$i],
                'assists' => $assists[$i],
                'yellow_cards' => $yellow,
                'red_cards' => $red,
                'minutes_played' => $red ? mt_rand(30, 80) : $minutes,
                'rating' => round(max(5.0, min(10.0, $rating)), 1),
            ]);
        }
    }

    /**
     * Index of a squad member chosen in proportion to position weight × quality.
     *
     * @param  list<array{player: Player, position: string, overall: int}>  $squad
     * @param  array<string, float|int>  $weights
     */
    private function weightedPick(array $squad, array $weights, ?int $exclude = null): int
    {
        $scores = [];
        foreach ($squad as $i => $member) {
            $scores[$i] = $i === $exclude ? 0 : ($weights[$member['position']] ?? 1) * max(1, $member['overall'] - 65);
        }
        $total = array_sum($scores);
        if ($total <= 0) {
            return array_key_first($squad);
        }
        $roll = mt_rand() / mt_getrandmax() * $total;
        foreach ($scores as $i => $score) {
            $roll -= $score;
            if ($roll <= 0) {
                return $i;
            }
        }

        return array_key_last($squad);
    }

    // ---- Profiles ------------------------------------------------------------------

    /**
     * Per-90 profile by position code:
     * [xG, xA, key passes, pass% low, pass% high, progressive carries, dribble% low, dribble% high, tackles+interceptions]
     */
    private const METRIC_PROFILES = [
        'GK' => [0.00, 0.005, 0.05, 68, 84, 0.1, null, null, 0.2],
        'CB' => [0.06, 0.03, 0.30, 88, 94, 1.0, 50, 75, 3.2],
        'LB' => [0.05, 0.12, 1.00, 78, 86, 3.0, 45, 65, 2.8],
        'RB' => [0.05, 0.12, 1.00, 78, 86, 3.0, 45, 65, 2.8],
        'LM' => [0.08, 0.14, 1.20, 78, 86, 3.2, 45, 65, 2.4],
        'RM' => [0.08, 0.14, 1.20, 78, 86, 3.2, 45, 65, 2.4],
        'DM' => [0.06, 0.08, 0.90, 88, 93, 1.5, 50, 70, 3.5],
        'CM' => [0.12, 0.15, 1.50, 86, 92, 2.2, 50, 70, 2.4],
        'AM' => [0.25, 0.28, 2.40, 80, 88, 3.5, 45, 65, 1.3],
        'LW' => [0.32, 0.25, 2.00, 76, 85, 5.0, 40, 60, 1.1],
        'RW' => [0.32, 0.25, 2.00, 76, 85, 5.0, 40, 60, 1.1],
        'SS' => [0.40, 0.22, 1.80, 76, 84, 3.0, 40, 60, 0.9],
        'CF' => [0.55, 0.12, 1.10, 68, 78, 2.0, 35, 55, 0.7],
    ];

    /**
     * Share of touches per pitch zone, attacking left to right as seen from the
     * player's own goal: [def_left, def_centre, def_right, mid_left, mid_centre,
     * mid_right, att_left, att_centre, att_right].
     */
    private const HEATMAP_TEMPLATES = [
        'GK' => [8, 80, 8, 1, 2, 1, 0, 0, 0],
        'CB' => [14, 46, 14, 5, 15, 5, 0, 1, 0],
        'LB' => [30, 8, 2, 30, 6, 2, 15, 4, 3],
        'RB' => [2, 8, 30, 2, 6, 30, 3, 4, 15],
        'LM' => [22, 6, 2, 34, 8, 2, 18, 5, 3],
        'RM' => [2, 6, 22, 2, 8, 34, 3, 5, 18],
        'DM' => [5, 25, 5, 12, 35, 12, 1, 4, 1],
        'CM' => [4, 12, 4, 15, 30, 15, 6, 9, 5],
        'AM' => [1, 4, 1, 10, 22, 10, 14, 26, 12],
        'LW' => [3, 2, 1, 22, 8, 2, 40, 18, 4],
        'RW' => [1, 2, 3, 2, 8, 22, 4, 18, 40],
        'SS' => [1, 2, 1, 8, 20, 8, 15, 32, 13],
        'CF' => [1, 2, 1, 5, 15, 5, 15, 42, 14],
    ];

    /** Shown when a player's representation is not public (product decision 2026-11-19). */
    public const AGENT_NOT_PUBLIC = 'Not publicly disclosed';

    /** Position-typical height (cm), used only as a labelled estimate. */
    private const TYPICAL_HEIGHT = [
        'GK' => 190, 'CB' => 188, 'LB' => 178, 'RB' => 178, 'LM' => 176, 'RM' => 176, 'DM' => 182,
        'CM' => 180, 'AM' => 177, 'LW' => 175, 'RW' => 175, 'SS' => 178, 'CF' => 185,
    ];

    private const HEATMAP_ZONES = ['def_left', 'def_centre', 'def_right', 'mid_left', 'mid_centre', 'mid_right', 'att_left', 'att_centre', 'att_right'];

    private const DEFAULT_STYLES = [
        'GK' => 'Shot Stopper', 'CB' => 'Stopper', 'LB' => 'Full-Back', 'RB' => 'Full-Back', 'LM' => 'Wide Midfielder', 'RM' => 'Wide Midfielder',
        'DM' => 'Anchor', 'CM' => 'Box-to-Box Midfielder', 'AM' => 'Advanced Playmaker', 'LW' => 'Winger',
        'RW' => 'Winger', 'SS' => 'Shadow Striker', 'CF' => 'Poacher',
    ];

    /**
     * Club facts from the curated data, plus last-5 form and clean sheets from the
     * finished fixtures.
     *
     * @param  array<string, Team>  $teamsByCode
     * @param  array<string, mixed>  $profiles
     * @param  array{teams: array}  $tsdb  a manager TheSportsDB lists replaces the curated coach
     */
    private function seedClubProfiles(array $teamsByCode, array $profiles, array $tsdb): void
    {
        $finished = GameMatch::where('status', 'FINISHED')->orderByDesc('match_date')->get();

        foreach ($teamsByCode as $code => $team) {
            $club = $profiles['clubs'][$code] ?? throw new \LogicException("Missing club profile {$code}");

            $form = [];
            $cleanSheets = 0;
            foreach ($finished as $match) {
                $isHome = (int) $match->home_team_id === $team->id;
                if (! $isHome && (int) $match->away_team_id !== $team->id) {
                    continue;
                }
                $for = $isHome ? $match->home_score : $match->away_score;
                $against = $isHome ? $match->away_score : $match->home_score;
                $cleanSheets += $against === 0 ? 1 : 0;
                if (count($form) < 5) {
                    $form[] = match ($for <=> $against) {
                        1 => 'W', 0 => 'D', -1 => 'L'
                    };
                }
            }

            $team->update([
                ...collect($club)->only([
                    'short_name', 'primary_color', 'secondary_color', 'founded_year', 'city', 'stadium_name',
                    'stadium_capacity', 'coach_name', 'president', 'kit_sponsor', 'squad_size', 'average_age',
                    'foreigners_count', 'preferred_formation',
                ])->all(),
                'total_market_value' => self::money($club['total_market_value']),
                'foreigners_pct' => round($club['foreigners_count'] / $club['squad_size'] * 100, 1),
                'transfer_income' => self::money($club['transfer_income']),
                'transfer_expenditure' => self::money($club['transfer_expenditure']),
                'net_spend' => self::money($club['transfer_income'] - $club['transfer_expenditure'], signed: true),
                'annual_wage_bill' => self::money($club['annual_wage_bill']),
                'coach_name' => $tsdb['teams'][$code]['manager'] ?? $club['coach_name'],
                'last_5_form' => $form,
                'clean_sheets' => $cleanSheets,
                'trophies' => collect($club['trophies'])->map(fn ($count, $title) => ['title' => $title, 'count' => $count])->values()->all(),
            ]);
        }
    }

    /**
     * @param  list<array{player: Player, team: Team, position: string, nationality: string, shirt: int, overall: int}>  $roster
     * @param  array<string, mixed>  $profiles
     */
    private function seedPlayerProfiles(array $roster, array $profiles): void
    {
        $totals = PlayerStat::query()
            ->selectRaw('player_id, count(*) as apps, sum(goals) as goals, sum(assists) as assists, sum(minutes_played) as minutes,
                sum(yellow_cards) as yellows, sum(red_cards) as reds, avg(rating) as rating')
            ->groupBy('player_id')
            ->get()
            ->keyBy('player_id');

        foreach ($roster as $entry) {
            /** @var Player $player */
            $player = $entry['player'];
            $name = trim("{$player->first_name} {$player->last_name}");
            // Seed per player so generated values are stable across re-runs.
            mt_srand(crc32($name));

            $row = $profiles['players'][$name] ?? $this->tsdbProfileRow($entry);
            [$pos, $secondary, $height, $foot, $value, $peak, $peakDate, $contract, $wage, $clause,
                $agent, $boots, $style, $caps, $intlGoals, $trophies, $transfers, $injuries] = $row;
            $transferHistory = self::parseList($transfers, fn ($date, $from, $to, $fee) => [
                'date' => $date, 'from' => $from, 'to' => $to,
                'fee' => is_numeric($fee) ? self::money((float) $fee) : $fee,
            ]);

            // TheSportsDB has them at a different club than the curated data knows about:
            // record the move (date/fee unknown) and drop contract terms from the old club.
            if ($entry['moved']) {
                $transferHistory[] = ['date' => null, 'from' => end($transferHistory)['to'] ?? null, 'to' => $entry['team']->name, 'fee' => null];
                [$contract, $clause] = [null, null];
                $wage = self::weeklyWageK($entry['tsdb']['wage'] ?? null);
            }

            // Reviewed market value / contract year (squad_facts) fill what the row lacks.
            $facts = $profiles['squad_facts'][$entry['team']->short_code][$name] ?? null;
            if ($facts !== null) {
                [, $factValue, $factContract] = $facts;
                $value ??= $factValue;
                $contract ??= $factContract;
            }

            // Curated rows can still lack fields: take TheSportsDB's real profile data next.
            $tsdbProfile = $entry['tsdb']['profile'] ?? [];
            $height ??= self::heightCm($tsdbProfile['height'] ?? $entry['tsdb']['height'] ?? null);
            $foot ??= self::footCode($tsdbProfile['side'] ?? null);
            $wage ??= self::weeklyWageK($tsdbProfile['wage'] ?? $entry['tsdb']['wage'] ?? null);
            $agent ??= ($tsdbProfile['agent'] ?? null) ?: ($profiles['agents'][$name] ?? null);

            // Last resort: labelled rule-based estimates (product decision 2026-11-19), recorded
            // in profile_estimates so real and estimated values stay distinguishable.
            $estimated = [];
            $code = $entry['team']->short_code;
            if ($height === null) {
                $height = self::TYPICAL_HEIGHT[$pos];
                $estimated[] = 'height_cm';
            }
            if ($foot === null) {
                $foot = in_array($pos, ['LB', 'LM'], true) ? 'L' : 'R';
                $estimated[] = 'preferred_foot';
            }
            if ($wage === null && $value !== null) {
                // Weekly €k ≈ market value (€m) × club tier, rounded to €5k.
                $tier = ($profiles['clubs'][$code]['strength'] ?? 82) >= 86 ? 3.0 : 2.2;
                $wage = max(15, (int) round($value * $tier / 5) * 5);
                $estimated[] = 'weekly_salary';
            }
            if ($peak === null && $value !== null) {
                $peak = $value; // career peak unknown: at least the current value
                $estimated[] = 'highest_market_value';
            }
            if ($peak !== null && $value !== null && $peak < $value) {
                $peak = $value; // a peak is never below the current value
            }
            $spanish = in_array($code, ['RMA', 'BAR', 'ATM'], true);
            if ($spanish && $clause === null && $value !== null) {
                // Every La Liga contract has a clause; amount by value band.
                $clause = match (true) {
                    $value >= 80 => 1000, $value >= 40 => 500, $value >= 15 => 200, default => 100
                };
                $estimated[] = 'release_clause';
            }
            $agent ??= self::AGENT_NOT_PUBLIC;

            // Back view: caps (approximate list when not curated), career path, honours.
            if ($caps === null && isset($profiles['caps'][$name])) {
                [$caps, $intlGoals] = $profiles['caps'][$name];
                $estimated[] = 'intl_caps';
            }
            $career = $entry['tsdb']['career'] ?? null;
            if ($transferHistory === [] && $career !== null) {
                $transferHistory = self::careerTransfers($career['former_teams'] ?? [], $entry['team']->name, $profiles['deals'][$name] ?? '');
            } elseif ($transferHistory === [] && isset($profiles['deals'][$name])) {
                $transferHistory = self::careerTransfers([], $entry['team']->name, $profiles['deals'][$name]);
            }
            if ($transferHistory === [] && in_array($name, $profiles['academy'] ?? [], true)) {
                $club = $entry['team']->name;
                $transferHistory = [['date' => null, 'from' => "{$club} academy", 'to' => $club, 'fee' => 'Academy']];
            }
            $honours = self::mergeHonours(self::parseTrophies($trophies, $profiles['trophy_names']), $career['honours'] ?? []);

            $stats = $totals->get($player->id);
            $season = [
                'appearances' => (int) ($stats->apps ?? 0),
                'goals' => (int) ($stats->goals ?? 0),
                'assists' => (int) ($stats->assists ?? 0),
                'minutes_played' => (int) ($stats->minutes ?? 0),
                'yellow_cards' => (int) ($stats->yellows ?? 0),
                'red_cards' => (int) ($stats->reds ?? 0),
                'avg_rating' => isset($stats->rating) ? round((float) $stats->rating, 2) : null,
            ];

            $attributes = [
                'shirt_number' => $entry['shirt'],
                'primary_position' => $profiles['positions'][$pos],
                'secondary_positions' => array_values(array_map(
                    fn ($code) => $profiles['positions'][$code],
                    array_filter(explode(',', $secondary)),
                )),
                'age' => $player->date_of_birth?->age,
                'height_cm' => $height,
                'nationality' => $entry['nationality'],
                'preferred_foot' => ['R' => 'Right', 'L' => 'Left', 'B' => 'Both'][$foot] ?? null,
                'market_value' => $value === null ? null : self::money($value),
                'highest_market_value' => $peak === null ? null : self::money($peak),
                'highest_market_value_date' => $peakDate === null ? null : "{$peakDate}-01",
                'contract_expires' => $contract === null ? null : "{$contract}-06-30",
                'weekly_salary' => $wage === null ? null : self::money($wage / 1000),
                'annual_salary' => $wage === null ? null : self::money($wage * 52 / 1000),
                // Outside Spain a buy-out clause is not standard: an explicit 'None'.
                'release_clause' => $clause !== null ? self::money($clause) : (($spanish ?? false) ? null : 'None'),
                'profile_estimates' => $estimated,
                'agent_name' => $agent,
                'boot_sponsor' => $boots,
                'playing_style' => $style ?? self::DEFAULT_STYLES[$pos],
                ...$season,
                ...$this->metrics($pos, $season, $entry['overall'], $height),
                'heatmap_zones' => $this->heatmap($pos),
                'injury_status' => 'Fit',
                'injury_history' => self::parseList($injuries, fn ($date, $injury, $days) => [
                    'date' => $date, 'injury' => $injury, 'days_out' => (int) $days,
                ]),
                'national_team' => $entry['nationality'],
                'intl_caps' => $caps,
                'intl_goals' => $intlGoals,
                'trophies' => $honours,
                'transfer_history' => $transferHistory,
                // Unknown: there is no verified source for rumours, so none are invented.
                'transfer_rumors' => null,
            ];

            [$attributes, $problems] = PlayerDataValidator::clean([...$attributes, 'position' => $entry['position']]);
            foreach ($problems as $problem) {
                $this->command?->warn("  INVALID {$name}: {$problem} -> stored as null");
            }
            $player->update($attributes);
        }
    }

    /**
     * Short display names and each club's projected starting XI.
     *
     * Display name: a reviewed override (football_profiles `display_names`), else the
     * single name for mononyms, "First Jr." for a "Júnior" surname, else the surname —
     * with "F. Surname" when two clubmates would otherwise share one.
     *
     * Starting XI: exactly 11, shaped by the club's preferred formation (DEF = first
     * number, FWD = last, MID = the rest), best players first by market value then
     * season rating; a short line borrows from the remaining outfield players.
     *
     * @param  array<string, Team>  $teamsByCode
     * @param  array<string, mixed>  $profiles
     */
    private function seedDisplayNamesAndLineups(array $teamsByCode, array $profiles): void
    {
        foreach ($teamsByCode as $team) {
            $players = $team->currentPlayers()->get();

            $names = $players->mapWithKeys(fn (Player $p) => [$p->id => self::displayName(
                $p->first_name, $p->last_name, $profiles['display_names'][trim("{$p->first_name} {$p->last_name}")] ?? null,
            )]);
            $clashes = array_filter(array_count_values($names->all()), fn ($n) => $n > 1);
            foreach ($players as $p) {
                $name = $names[$p->id];
                if (isset($clashes[$name]) && $p->first_name !== '' && ! isset($profiles['display_names'][trim("{$p->first_name} {$p->last_name}")])) {
                    $name = mb_substr($p->first_name, 0, 1).'. '.$p->last_name;
                }
                $p->update(['display_name' => $name]);
            }

            $ranked = $players->sortByDesc(fn (Player $p) => [PlayerDataValidator::money($p->market_value) ?? 0, $p->avg_rating ?? 0])->values();
            $starters = collect();
            foreach (self::formationLines($team->preferred_formation) as $line => $count) {
                $starters = $starters->merge($ranked->where('position', $line)->take($count));
            }
            // A short line borrows from its neighbours: midfield and attack from each other first
            // (a 4-2-3-1 "3" is often wingers, counted as forwards here), defenders last.
            $remaining = $ranked->whereNotIn('id', $starters->pluck('id'));
            $fill = $remaining->whereIn('position', ['Midfielder', 'Forward'])->merge($remaining->where('position', 'Defender'));
            $starters = $starters->merge($fill->take(11 - $starters->count()));

            TeamPlayer::where(['team_id' => $team->id, 'is_current' => true])->update(['is_starter' => false]);
            TeamPlayer::where(['team_id' => $team->id, 'is_current' => true])
                ->whereIn('player_id', $starters->pluck('id'))
                ->update(['is_starter' => true]);
        }
    }

    /** "4-2-3-1" => [Goalkeeper => 1, Defender => 4, Midfielder => 5, Forward => 1]; falls back to 4-3-3. */
    public static function formationLines(?string $formation): array
    {
        preg_match_all('/\d+/', (string) $formation, $m);
        $lines = array_map('intval', $m[0]);
        if (count($lines) < 3 || array_sum($lines) !== 10) {
            $lines = [4, 3, 3];
        }

        return [
            'Goalkeeper' => 1,
            'Defender' => $lines[0],
            'Midfielder' => array_sum(array_slice($lines, 1, -1)),
            'Forward' => end($lines),
        ];
    }

    /** "Vinícius Júnior" => "Vinícius Jr."; "Rodri" => "Rodri"; "Bukayo Saka" => "Saka". */
    public static function displayName(string $first, string $last, ?string $override = null): string
    {
        return match (true) {
            $override !== null => $override,
            $first === '' => $last,
            in_array($last, ['Júnior', 'Junior', 'Jr.', 'Jr'], true) => "{$first} Jr.",
            default => $last,
        };
    }

    /**
     * Profile for a player without curated data, in the same shape as a curated row,
     * built only from what TheSportsDB knows (position, height, wage). Everything else
     * — market value, contract, caps, honours… — stays null/empty rather than invented.
     *
     * @param  array{position: string, tsdb: ?array}  $entry
     * @return array<int, mixed>
     */
    private function tsdbProfileRow(array $entry): array
    {
        $record = $entry['tsdb'] ?? [];
        $profile = $record['profile'] ?? [];

        return [
            self::positionCode($profile['position'] ?? $record['position'] ?? null, $entry['position']), '',
            self::heightCm($profile['height'] ?? $record['height'] ?? null), self::footCode($profile['side'] ?? null),
            null, null, null, null, self::weeklyWageK($profile['wage'] ?? $record['wage'] ?? null), null,
            $profile['agent'] ?? null, null, null, null, null, '', '', '',
        ];
    }

    /** "1.77 m (5 ft 10 in)", "6 ft 5 in (1.96 m)", "186 cm" => centimetres; null when unparseable. */
    private static function heightCm(?string $height): ?int
    {
        if (preg_match('/(\d)\.(\d{2})\s*m/', (string) $height, $m)) {
            return (int) ($m[1].$m[2]);
        }

        return preg_match('/(\d{3})\s*cm/', (string) $height, $m) ? (int) $m[1] : null;
    }

    /** TheSportsDB strSide ("Right", "Left", "Both"/"Either") => R / L / B. */
    private static function footCode(?string $side): ?string
    {
        return match (strtolower(trim((string) $side))) {
            'right' => 'R', 'left' => 'L', 'both', 'either' => 'B', default => null,
        };
    }

    /** TheSportsDB annual wage ("£10,140,000", "€5,000,000", "$1,200,000") => weekly €k. */
    private static function weeklyWageK(?string $wage): ?float
    {
        if ($wage === null || ! preg_match('/^([£€$])\s*([\d,.]+)/u', trim($wage), $m)) {
            return null;
        }
        $toEuro = ['£' => 1.17, '€' => 1.0, '$' => 0.92][$m[1]];
        $annual = (float) str_replace(',', '', $m[2]) * $toEuro;

        $weekly = round($annual / 52 / 1000);

        // Under €1k a week at these clubs is a data error (seen: Zaïre-Emery "€0k"): treat as unknown.
        return $weekly >= 1 ? $weekly : null;
    }

    /**
     * Opta/Sofascore-style season metrics, anchored to the player's generated goals,
     * assists and minutes and scaled by quality.
     *
     * @param  array{goals: int, assists: int, minutes_played: int}  $season
     * @return array<string, float|int|null>
     */
    private function metrics(string $pos, array $season, int $overall, ?int $height): array
    {
        [$xg90, $xa90, $kp90, $passLo, $passHi, $pc90, $dribLo, $dribHi, $ti90] = self::METRIC_PROFILES[$pos];
        $nineties = $season['minutes_played'] / 90;
        $quality = 0.7 + ($overall - 70) / 40;
        $jitter = fn (float $spread = 0.15) => 1 + mt_rand((int) (-$spread * 100), (int) ($spread * 100)) / 100;
        $between = fn (float $lo, float $hi) => round($lo + ($hi - $lo) * mt_rand(0, 100) / 100, 1);
        $qualityShift = ($overall - 80) * 0.2;

        $aerial = match ($pos) {
            'GK' => null,
            'CB' => $between(58, 72),
            'CF' => $between(35, 52),
            default => $between(30, 50),
        };

        return [
            'xg' => round(max(0, 0.6 * $season['goals'] + 0.4 * $xg90 * $nineties * $quality * $jitter()), 2),
            'xa' => round(max(0, 0.6 * $season['assists'] + 0.4 * $xa90 * $nineties * $quality * $jitter()), 2),
            'key_passes' => (int) round($kp90 * $nineties * $quality * $jitter()),
            'pass_accuracy_pct' => min(96.0, $between($passLo, $passHi) + max(0, $qualityShift / 2)),
            'progressive_carries' => (int) round($pc90 * $nineties * $quality * $jitter()),
            'dribble_success_pct' => $dribLo === null ? null : min(85.0, $between($dribLo, $dribHi) + max(0, $qualityShift)),
            'tackles_interceptions' => (int) round($ti90 * $nineties * $jitter(0.2)),
            'aerial_duels_won_pct' => $aerial === null ? null : round(max(15, min(85, $aerial + (($height ?? 182) - 182) * 0.8)), 1),
        ];
    }

    /** @return array<string, int> zone => % of touches (sums to 100) */
    private function heatmap(string $pos): array
    {
        $weights = array_map(fn ($w) => $w * (1 + mt_rand(-20, 20) / 100), self::HEATMAP_TEMPLATES[$pos]);
        $total = array_sum($weights);
        $shares = array_map(fn ($w) => (int) round($w / $total * 100), $weights);
        // Put any rounding remainder in the busiest zone so the shares sum to 100.
        $busiest = array_search(max($shares), $shares, true);
        $shares[$busiest] += 100 - array_sum($shares);

        return array_combine(self::HEATMAP_ZONES, $shares);
    }

    /**
     * Career transfers from TheSportsDB's former teams (club, joined year, move type), one node
     * per change of club, ending at the current club; reviewed deals ("Y-m|to|fee; …") then give
     * matching moves their month and fee (or are added when TheSportsDB lacks the move).
     * Year-only dates stay "YYYY": shown in the list, not plotted (the chart needs a month).
     *
     * @return list<array{date: ?string, from: ?string, to: string, fee: ?string}>
     */
    private static function careerTransfers(array $formerTeams, string $currentClub, string $deals): array
    {
        $same = fn (?string $a, ?string $b) => $a !== null && $b !== null && (TheSportsDb::normalise($a) === TheSportsDb::normalise($b)
            || (PlayerRosterVerifier::codeForClubName($a, ['X' => $b]) !== null) || (PlayerRosterVerifier::codeForClubName($b, ['X' => $a]) !== null));
        $teams = array_values(array_filter($formerTeams, fn ($t) => ! empty($t['team'])));
        usort($teams, fn ($a, $b) => [(int) ($a['joined'] ?? 0), (int) ($a['departed'] ?? 9999)] <=> [(int) ($b['joined'] ?? 0), (int) ($b['departed'] ?? 9999)]);

        $nodes = [];
        $previous = null;
        foreach ($teams as $t) {
            if ($same($previous, $t['team']) && ($t['move'] ?? '') !== 'Loan') {
                continue; // youth → first team at the same club is not a transfer
            }
            $nodes[] = [
                'date' => $t['joined'] ?? null, 'from' => $previous, 'to' => $t['team'],
                'fee' => match ($t['move'] ?? null) {
                    'Loan' => 'Loan', 'Youth' => 'Youth', default => null
                },
            ];
            $previous = $t['team'];
        }
        if ($nodes === [] || ! $same(end($nodes)['to'], $currentClub)) {
            $last = end($teams) ?: [];
            $nodes[] = ['date' => $last['departed'] ?? null, 'from' => $previous, 'to' => $currentClub, 'fee' => null];
        }

        foreach (self::parseList($deals, fn ($date, $to, $fee) => compact('date', 'to', 'fee')) as $deal) {
            $fee = is_numeric($deal['fee']) ? self::money((float) $deal['fee']) : $deal['fee'];
            $year = (int) substr($deal['date'], 0, 4);
            $matched = false;
            foreach ($nodes as &$node) {
                if ($same($node['to'], $deal['to']) && abs((int) substr((string) $node['date'], 0, 4) - $year) <= 1) {
                    $node['date'] = $deal['date'];
                    $node['fee'] = $fee;
                    $matched = true;
                    break;
                }
            }
            unset($node);
            if (! $matched) {
                $nodes[] = ['date' => $deal['date'], 'from' => null, 'to' => $deal['to'], 'fee' => $fee];
            }
        }
        usort($nodes, fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));
        // Re-link "from" after sorting, so every node starts where the previous one ended.
        foreach ($nodes as $i => &$node) {
            if ($i > 0 && $node['from'] === null) {
                $node['from'] = $nodes[$i - 1]['to'];
            }
        }
        unset($node);

        return array_values($nodes);
    }

    /**
     * Curated honours merged with TheSportsDB's (title per season): titles normalised
     * ("English Premier League" → "Premier League"), the larger count kept per title.
     *
     * @return list<array{title: string, count: int}>
     */
    private static function mergeHonours(array $curated, array $tsdbHonours): array
    {
        $counts = [];
        foreach ($curated as ['title' => $title, 'count' => $count]) {
            $counts[$title] = max($counts[$title] ?? 0, $count);
        }
        $fromTsdb = [];
        foreach ($tsdbHonours as $h) {
            $title = self::honourTitle((string) ($h['title'] ?? ''));
            if ($title !== '') {
                $fromTsdb[$title][(string) ($h['season'] ?? '').'|'.($h['team'] ?? '')] = true;
            }
        }
        foreach ($fromTsdb as $title => $seasons) {
            $counts[$title] = max($counts[$title] ?? 0, count($seasons));
        }
        arsort($counts);

        return array_values(array_map(fn ($t, $c) => ['title' => $t, 'count' => $c], array_keys($counts), $counts));
    }

    /** TheSportsDB honour names → the names used here. */
    private static function honourTitle(string $title): string
    {
        $map = [
            'English Premier League' => 'Premier League', 'English FA Cup' => 'FA Cup', 'English Football League Cup' => 'EFL Cup',
            'English League Cup' => 'EFL Cup', 'English FA Community Shield' => 'Community Shield', 'Spanish La Liga' => 'La Liga',
            'Spanish Copa del Rey' => 'Copa del Rey', 'Spanish Supercopa' => 'Supercopa de España', 'Italian Serie A' => 'Serie A',
            'Italian Coppa Italia' => 'Coppa Italia', 'Italian Supercoppa' => 'Supercoppa Italiana', 'German Bundesliga' => 'Bundesliga',
            'German DFB-Pokal' => 'DFB-Pokal', 'German DFL-Supercup' => 'DFL-Supercup', 'French Ligue 1' => 'Ligue 1',
            'French Coupe de France' => 'Coupe de France', 'French Trophée des Champions' => 'Trophée des Champions',
            'UEFA Champions League' => 'UEFA Champions League', 'UEFA Europa League' => 'UEFA Europa League',
            'UEFA Europa Conference League' => 'UEFA Conference League', 'UEFA Conference League' => 'UEFA Conference League',
            'FIFA World Cup' => 'FIFA World Cup', 'UEFA European Championship' => 'UEFA European Championship', 'UEFA Euro' => 'UEFA European Championship',
            'Copa America' => 'Copa América', 'UEFA Nations League' => 'UEFA Nations League', 'FIFA Club World Cup' => 'FIFA Club World Cup',
        ];
        $title = trim($title);

        return $map[$title] ?? trim(preg_replace('/^(English|Spanish|Italian|German|French|Dutch|Portuguese|Scottish|Belgian|Turkish|Austrian|Swiss|Greek|Croatian|Danish)\s+/', '', $title));
    }

    /** "PL:2 UCL:1" => [{title: "Premier League", count: 2}, …] */
    private static function parseTrophies(string $codes, array $names): array
    {
        $trophies = [];
        foreach (array_filter(explode(' ', $codes)) as $item) {
            [$code, $count] = explode(':', $item);
            $trophies[] = ['title' => $names[$code] ?? throw new \LogicException("Unknown trophy code {$code}"), 'count' => (int) $count];
        }

        return $trophies;
    }

    /** "a|b|c; d|e|f" => list of $map(a, b, c) */
    private static function parseList(string $list, callable $map): array
    {
        return array_values(array_map(
            fn ($item) => $map(...array_map('trim', explode('|', $item))),
            array_filter(array_map('trim', explode(';', $list))),
        ));
    }

    /** € millions => "€1.30bn", "€180.00m", "€600k" (optionally with a +/- sign). */
    private static function money(float $millions, bool $signed = false): string
    {
        $abs = abs($millions);
        $text = match (true) {
            $abs >= 1000 => sprintf('€%.2fbn', $abs / 1000),
            $abs >= 1 => sprintf('€%.2fm', $abs),
            default => sprintf('€%dk', round($abs * 1000)),
        };

        return match (true) {
            $millions < 0 => "-{$text}",
            $signed && $millions > 0 => "+{$text}",
            default => $text,
        };
    }
}
