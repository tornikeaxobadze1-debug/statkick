<?php

/*
|--------------------------------------------------------------------------
| Curated club & player profiles used by FootballSeeder
|--------------------------------------------------------------------------
|
| Real-world reference data, compiled by hand from public sources (Transfermarkt,
| Capology-style wage estimates, club sites). It is APPROXIMATE and dated around
| the 2025/26 season — verify anything you rely on:
|   - market values, wages, transfer sums, caps: approximate, late-2025 snapshot;
|   - honours: counted through August 2025 (end of the 2024/25 season + 2025 CWC);
|   - transfer fees: headline fees in euros, add-ons excluded;
|   - agent / boot deal / kit supplier: only filled where publicly well known,
|     null otherwise rather than guessed.
|
| Season statistics (appearances, goals, xG, form, clean sheets…), heatmaps and
| transfer rumours are NOT here: FootballSeeder derives them from the generated
| fixtures so they stay consistent with /api/matches.
|
*/

return [

    /** Position codes used below => Transfermarkt-style labels. */
    'positions' => [
        'GK' => 'Goalkeeper', 'CB' => 'Centre-Back', 'LB' => 'Left-Back', 'RB' => 'Right-Back',
        'DM' => 'Defensive Midfield', 'CM' => 'Central Midfield', 'AM' => 'Attacking Midfield',
        'LM' => 'Left Midfield', 'RM' => 'Right Midfield', 'LW' => 'Left Winger', 'RW' => 'Right Winger', 'SS' => 'Second Striker', 'CF' => 'Centre-Forward',
    ],

    /** Trophy codes used in player rows => trophy names. */
    'trophy_names' => [
        'PL' => 'Premier League', 'FAC' => 'FA Cup', 'LC' => 'EFL Cup',
        'LL' => 'La Liga', 'CDR' => 'Copa del Rey',
        'SA' => 'Serie A', 'CI' => 'Coppa Italia',
        'BL' => 'Bundesliga', 'DFB' => 'DFB-Pokal',
        'L1' => 'Ligue 1', 'CDF' => 'Coupe de France',
        'ERE' => 'Eredivisie', 'SPL' => 'Scottish Premiership',
        'UCL' => 'UEFA Champions League', 'UEL' => 'UEFA Europa League', 'UECL' => 'UEFA Conference League',
        'CWC' => 'FIFA Club World Cup',
        'WC' => 'FIFA World Cup', 'EURO' => 'UEFA European Championship', 'CA' => 'Copa América',
        'NL' => 'UEFA Nations League',
    ],

    /*
     * Clubs, keyed by FootballSeeder team code. Money in € millions; income and
     * expenditure are for the 2025/26 transfer windows; trophies are major honours.
     */
    'clubs' => [
        // ---- England -------------------------------------------------------------
        'ARS' => [
            'short_name' => 'Arsenal', 'primary_color' => '#EF0107', 'secondary_color' => '#FFFFFF',
            'strength' => 88, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1886, 'city' => 'London', 'stadium_name' => 'Emirates Stadium', 'stadium_capacity' => 60704,
            'coach_name' => 'Mikel Arteta', 'president' => 'Stan Kroenke', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 1300, 'squad_size' => 25, 'average_age' => 26.1, 'foreigners_count' => 15,
            'preferred_formation' => '4-3-3 Attack', 'transfer_income' => 30, 'transfer_expenditure' => 292, 'annual_wage_bill' => 290,
            'trophies' => ['League title' => 13, 'FA Cup' => 14, 'League Cup' => 2, 'Cup Winners\' Cup' => 1, 'Inter-Cities Fairs Cup' => 1],
        ],
        'MCI' => [
            'short_name' => 'Man City', 'primary_color' => '#6CABDD', 'secondary_color' => '#1C2C5B',
            'strength' => 87, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1880, 'city' => 'Manchester', 'stadium_name' => 'Etihad Stadium', 'stadium_capacity' => 52900,
            'coach_name' => 'Pep Guardiola', 'president' => 'Khaldoon Al Mubarak', 'kit_sponsor' => 'Puma',
            'total_market_value' => 1200, 'squad_size' => 27, 'average_age' => 25.8, 'foreigners_count' => 21,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 95, 'transfer_expenditure' => 190, 'annual_wage_bill' => 460,
            'trophies' => ['League title' => 10, 'FA Cup' => 7, 'League Cup' => 8, 'UEFA Champions League' => 1, 'Cup Winners\' Cup' => 1, 'FIFA Club World Cup' => 1],
        ],
        'LIV' => [
            'short_name' => 'Liverpool', 'primary_color' => '#C8102E', 'secondary_color' => '#F6EB61',
            'strength' => 86, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1892, 'city' => 'Liverpool', 'stadium_name' => 'Anfield', 'stadium_capacity' => 61276,
            'coach_name' => 'Arne Slot', 'president' => 'Tom Werner', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 1250, 'squad_size' => 26, 'average_age' => 25.9, 'foreigners_count' => 19,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 210, 'transfer_expenditure' => 483, 'annual_wage_bill' => 450,
            'trophies' => ['League title' => 20, 'FA Cup' => 8, 'League Cup' => 10, 'UEFA Champions League' => 6, 'UEFA Europa League' => 3, 'FIFA Club World Cup' => 1],
        ],
        'MUN' => [
            'short_name' => 'Man United', 'primary_color' => '#DA291C', 'secondary_color' => '#FBE122',
            'strength' => 80, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1878, 'city' => 'Manchester', 'stadium_name' => 'Old Trafford', 'stadium_capacity' => 74310,
            // Coach left null: changed during 2025/26 and I can't confirm the current one; a
            // manager listed by TheSportsDB fills it in.
            'coach_name' => null, 'president' => 'Joel Glazer', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 750, 'squad_size' => 26, 'average_age' => 25.7, 'foreigners_count' => 17,
            'preferred_formation' => '3-4-2-1', 'transfer_income' => 90, 'transfer_expenditure' => 250, 'annual_wage_bill' => 370,
            'trophies' => ['League title' => 20, 'FA Cup' => 13, 'League Cup' => 6, 'UEFA Champions League' => 3, 'UEFA Europa League' => 1, 'Cup Winners\' Cup' => 1, 'FIFA Club World Cup' => 1],
        ],
        'CHE' => [
            'short_name' => 'Chelsea', 'primary_color' => '#034694', 'secondary_color' => '#FFFFFF',
            'strength' => 84, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1905, 'city' => 'London', 'stadium_name' => 'Stamford Bridge', 'stadium_capacity' => 40343,
            'coach_name' => 'Liam Rosenior', 'president' => 'Todd Boehly', 'kit_sponsor' => 'Nike',
            'total_market_value' => 1150, 'squad_size' => 30, 'average_age' => 23.9, 'foreigners_count' => 22,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 290, 'transfer_expenditure' => 330, 'annual_wage_bill' => 430,
            'trophies' => ['League title' => 6, 'FA Cup' => 8, 'League Cup' => 5, 'UEFA Champions League' => 2, 'UEFA Europa League' => 2, 'UEFA Conference League' => 1, 'Cup Winners\' Cup' => 2, 'FIFA Club World Cup' => 2],
        ],
        // ---- Spain ---------------------------------------------------------------
        'RMA' => [
            'short_name' => 'Real Madrid', 'primary_color' => '#FFFFFF', 'secondary_color' => '#00529F',
            'strength' => 88, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1902, 'city' => 'Madrid', 'stadium_name' => 'Santiago Bernabéu', 'stadium_capacity' => 83186,
            'coach_name' => 'Álvaro Arbeloa', 'president' => 'Florentino Pérez', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 1350, 'squad_size' => 24, 'average_age' => 26.6, 'foreigners_count' => 15,
            'preferred_formation' => '4-3-3', 'transfer_income' => 10, 'transfer_expenditure' => 176, 'annual_wage_bill' => 500,
            'trophies' => ['La Liga' => 36, 'Copa del Rey' => 20, 'UEFA Champions League' => 15, 'UEFA Europa League' => 2, 'FIFA Club World Cup' => 5],
        ],
        'BAR' => [
            'short_name' => 'Barça', 'primary_color' => '#A50044', 'secondary_color' => '#004D98',
            'strength' => 88, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1899, 'city' => 'Barcelona', 'stadium_name' => 'Spotify Camp Nou', 'stadium_capacity' => 99354,
            'coach_name' => 'Hansi Flick', 'president' => 'Joan Laporta', 'kit_sponsor' => 'Nike',
            'total_market_value' => 1250, 'squad_size' => 25, 'average_age' => 24.9, 'foreigners_count' => 9,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 45, 'transfer_expenditure' => 30, 'annual_wage_bill' => 450,
            'trophies' => ['La Liga' => 28, 'Copa del Rey' => 32, 'UEFA Champions League' => 5, 'Cup Winners\' Cup' => 4, 'FIFA Club World Cup' => 3],
        ],
        'ATM' => [
            'short_name' => 'Atlético', 'primary_color' => '#CB3524', 'secondary_color' => '#272E61',
            'strength' => 84, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1903, 'city' => 'Madrid', 'stadium_name' => 'Riyadh Air Metropolitano', 'stadium_capacity' => 70692,
            'coach_name' => 'Diego Simeone', 'president' => 'Enrique Cerezo', 'kit_sponsor' => 'Nike',
            'total_market_value' => 580, 'squad_size' => 25, 'average_age' => 27.0, 'foreigners_count' => 13,
            'preferred_formation' => '4-4-2', 'transfer_income' => 75, 'transfer_expenditure' => 180, 'annual_wage_bill' => 260,
            'trophies' => ['La Liga' => 11, 'Copa del Rey' => 10, 'UEFA Europa League' => 3, 'Cup Winners\' Cup' => 1],
        ],
        // ---- Italy ---------------------------------------------------------------
        'INT' => [
            'short_name' => 'Inter', 'primary_color' => '#0068A8', 'secondary_color' => '#000000',
            'strength' => 86, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1908, 'city' => 'Milan', 'stadium_name' => 'San Siro', 'stadium_capacity' => 75817,
            'coach_name' => 'Cristian Chivu', 'president' => 'Giuseppe Marotta', 'kit_sponsor' => 'Nike',
            'total_market_value' => 700, 'squad_size' => 26, 'average_age' => 28.1, 'foreigners_count' => 15,
            'preferred_formation' => '3-5-2', 'transfer_income' => 65, 'transfer_expenditure' => 115, 'annual_wage_bill' => 210,
            'trophies' => ['Serie A' => 20, 'Coppa Italia' => 9, 'UEFA Champions League' => 3, 'UEFA Europa League' => 3, 'FIFA Club World Cup' => 1],
        ],
        'MIL' => [
            'short_name' => 'Milan', 'primary_color' => '#FB090B', 'secondary_color' => '#000000',
            'strength' => 82, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1899, 'city' => 'Milan', 'stadium_name' => 'San Siro', 'stadium_capacity' => 75817,
            'coach_name' => 'Massimiliano Allegri', 'president' => 'Paolo Scaroni', 'kit_sponsor' => 'Puma',
            'total_market_value' => 560, 'squad_size' => 27, 'average_age' => 26.0, 'foreigners_count' => 18,
            'preferred_formation' => '3-5-2', 'transfer_income' => 190, 'transfer_expenditure' => 160, 'annual_wage_bill' => 170,
            'trophies' => ['Serie A' => 19, 'Coppa Italia' => 5, 'UEFA Champions League' => 7, 'Cup Winners\' Cup' => 2, 'FIFA Club World Cup' => 1],
        ],
        'JUV' => [
            'short_name' => 'Juventus', 'primary_color' => '#000000', 'secondary_color' => '#FFFFFF',
            'strength' => 82, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1897, 'city' => 'Turin', 'stadium_name' => 'Allianz Stadium', 'stadium_capacity' => 41507,
            'coach_name' => 'Luciano Spalletti', 'president' => 'Gianluca Ferrero', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 590, 'squad_size' => 26, 'average_age' => 25.7, 'foreigners_count' => 15,
            'preferred_formation' => '3-4-2-1', 'transfer_income' => 135, 'transfer_expenditure' => 160, 'annual_wage_bill' => 230,
            'trophies' => ['Serie A' => 36, 'Coppa Italia' => 15, 'UEFA Champions League' => 2, 'UEFA Europa League' => 3, 'Cup Winners\' Cup' => 1],
        ],
        'NAP' => [
            'short_name' => 'Napoli', 'primary_color' => '#12A0D7', 'secondary_color' => '#003C82',
            'strength' => 84, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1926, 'city' => 'Naples', 'stadium_name' => 'Stadio Diego Armando Maradona', 'stadium_capacity' => 54726,
            'coach_name' => 'Antonio Conte', 'president' => 'Aurelio De Laurentiis', 'kit_sponsor' => 'EA7 Emporio Armani',
            'total_market_value' => 480, 'squad_size' => 27, 'average_age' => 27.4, 'foreigners_count' => 14,
            'preferred_formation' => '4-3-3', 'transfer_income' => 100, 'transfer_expenditure' => 200, 'annual_wage_bill' => 140,
            'trophies' => ['Serie A' => 4, 'Coppa Italia' => 6, 'UEFA Europa League' => 1],
        ],
        // ---- Germany -------------------------------------------------------------
        'FCB' => [
            'short_name' => 'Bayern', 'primary_color' => '#DC052D', 'secondary_color' => '#0066B2',
            'strength' => 89, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1900, 'city' => 'Munich', 'stadium_name' => 'Allianz Arena', 'stadium_capacity' => 75024,
            'coach_name' => 'Vincent Kompany', 'president' => 'Herbert Hainer', 'kit_sponsor' => 'Adidas',
            'total_market_value' => 950, 'squad_size' => 25, 'average_age' => 26.9, 'foreigners_count' => 11,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 90, 'transfer_expenditure' => 130, 'annual_wage_bill' => 360,
            'trophies' => ['Bundesliga' => 34, 'DFB-Pokal' => 20, 'UEFA Champions League' => 6, 'UEFA Europa League' => 1, 'Cup Winners\' Cup' => 1, 'FIFA Club World Cup' => 2],
        ],
        'B04' => [
            'short_name' => 'Leverkusen', 'primary_color' => '#E32221', 'secondary_color' => '#000000',
            'strength' => 82, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1904, 'city' => 'Leverkusen', 'stadium_name' => 'BayArena', 'stadium_capacity' => 30210,
            'coach_name' => 'Kasper Hjulmand', 'president' => 'Fernando Carro', 'kit_sponsor' => null,
            'total_market_value' => 450, 'squad_size' => 27, 'average_age' => 23.9, 'foreigners_count' => 19,
            'preferred_formation' => '3-4-2-1', 'transfer_income' => 270, 'transfer_expenditure' => 230, 'annual_wage_bill' => 160,
            'trophies' => ['Bundesliga' => 1, 'DFB-Pokal' => 2, 'UEFA Europa League' => 1],
        ],
        'BVB' => [
            'short_name' => 'Dortmund', 'primary_color' => '#FDE100', 'secondary_color' => '#000000',
            'strength' => 82, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1909, 'city' => 'Dortmund', 'stadium_name' => 'Signal Iduna Park', 'stadium_capacity' => 81365,
            'coach_name' => 'Niko Kovač', 'president' => 'Hans-Joachim Watzke', 'kit_sponsor' => 'Puma',
            'total_market_value' => 480, 'squad_size' => 27, 'average_age' => 25.3, 'foreigners_count' => 17,
            'preferred_formation' => '3-4-2-1', 'transfer_income' => 95, 'transfer_expenditure' => 100, 'annual_wage_bill' => 240,
            'trophies' => ['Bundesliga' => 8, 'DFB-Pokal' => 5, 'UEFA Champions League' => 1, 'Cup Winners\' Cup' => 1],
        ],
        // ---- France --------------------------------------------------------------
        'PSG' => [
            'short_name' => 'PSG', 'primary_color' => '#004170', 'secondary_color' => '#DA291C',
            'strength' => 88, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1970, 'city' => 'Paris', 'stadium_name' => 'Parc des Princes', 'stadium_capacity' => 47929,
            'coach_name' => 'Luis Enrique', 'president' => 'Nasser Al-Khelaifi', 'kit_sponsor' => 'Nike',
            'total_market_value' => 1100, 'squad_size' => 26, 'average_age' => 24.0, 'foreigners_count' => 15,
            'preferred_formation' => '4-3-3 Attack', 'transfer_income' => 80, 'transfer_expenditure' => 110, 'annual_wage_bill' => 550,
            'trophies' => ['Ligue 1' => 13, 'Coupe de France' => 16, 'UEFA Champions League' => 1, 'Cup Winners\' Cup' => 1],
        ],
        'ASM' => [
            'short_name' => 'Monaco', 'primary_color' => '#E51B22', 'secondary_color' => '#FFFFFF',
            'strength' => 80, // match-simulation rating (reviewed estimate of squad quality)
            'founded_year' => 1924, 'city' => 'Monaco', 'stadium_name' => 'Stade Louis II', 'stadium_capacity' => 18523,
            'coach_name' => 'Sébastien Pocognoli', 'president' => 'Dmitry Rybolovlev', 'kit_sponsor' => 'Kappa',
            'total_market_value' => 400, 'squad_size' => 27, 'average_age' => 24.1, 'foreigners_count' => 20,
            'preferred_formation' => '4-2-3-1', 'transfer_income' => 110, 'transfer_expenditure' => 70, 'annual_wage_bill' => 110,
            'trophies' => ['Ligue 1' => 8, 'Coupe de France' => 5],
        ],
    ],

    /*
     * Players, keyed by display name ("First Last", or the single name for mononyms).
     *
     *  0 primary position code      1 secondary codes (comma list)   2 height cm   3 foot (R/L/B)
     *  4 market value €m (null = retired)   5 highest market value €m   6 its date (Y-m)
     *  7 contract until (year, 30 June)   8 weekly wage €k (gross)   9 release clause €m
     * 10 agent   11 boot deal   12 playing style   13 caps   14 intl goals
     * 15 honours "CODE:count …"   16 transfers "Y-m|from|to|fee; …"   17 injuries "Y-m|injury|days; …"
     *
     * Players not listed here (TheSportsDB-verified additions) get a profile built only from
     * TheSportsDB's own facts; unknown fields stay null.
     */
    'players' => [
        // Arsenal
        'David Raya' => ['GK', '', 183, 'R', 40, 40, '2024-12', 2028, 100, null, null, null, 'Sweeper Keeper', 10, 0, 'EURO:1 NL:1', '2019-07|Blackburn Rovers|Brentford|3.4; 2023-08|Brentford|Arsenal|Loan; 2024-07|Brentford|Arsenal|31.3', ''],
        'William Saliba' => ['CB', '', 192, 'R', 80, 80, '2024-03', 2030, 225, null, null, null, 'Ball-Playing Defender', 26, 0, '', '2019-07|Saint-Étienne|Arsenal|30; 2021-01|Arsenal|Nice|Loan; 2021-07|Arsenal|Marseille|Loan', ''],
        'Declan Rice' => ['DM', 'CM', 188, 'R', 120, 120, '2025-06', 2028, 280, null, null, null, 'Box-to-Box Midfielder', 68, 6, 'UECL:1', '2023-07|West Ham United|Arsenal|116.6', ''],
        'Martin Ødegaard' => ['AM', 'CM', 178, 'L', 80, 110, '2023-12', 2028, 270, null, null, 'Adidas', 'Advanced Playmaker', 66, 4, '', '2015-01|Strømsgodset|Real Madrid|4; 2019-07|Real Madrid|Real Sociedad|Loan; 2021-01|Real Madrid|Arsenal|Loan; 2021-08|Real Madrid|Arsenal|35', '2024-09|Ankle ligament damage|62'],
        'Bukayo Saka' => ['RW', 'LW,AM', 178, 'L', 150, 150, '2024-12', 2027, 230, null, 'Roc Nation Sports', 'New Balance', 'Inside Forward', 45, 13, 'FAC:1', '2018-11|Arsenal U23|Arsenal|Academy', '2024-12|Hamstring surgery|101'],
        'Gabriel Magalhães' => ['CB', '', 190, 'L', 75, 75, '2025-06', 2029, 230, null, null, null, 'Stopper', 12, 1, '', '2017-01|Avaí|Lille|3; 2020-09|Lille|Arsenal|26', ''],
        'Ben White' => ['RB', 'CB', 186, 'R', 35, 55, '2023-12', 2028, 140, null, null, null, 'Full-Back', 4, 0, '', '2021-07|Brighton|Arsenal|58.5', '2024-11|Knee surgery|90'],
        'Cristhian Mosquera' => ['CB', 'RB', 188, 'R', 30, 30, '2025-06', 2030, 60, null, null, null, 'Ball-Playing Defender', 0, 0, '', '2025-07|Valencia|Arsenal|15', ''],
        'Piero Hincapié' => ['CB', 'LB', 184, 'L', 50, 50, '2025-06', 2030, 110, null, null, null, 'Ball-Playing Defender', 45, 3, 'BL:1 DFB:1', '2021-08|Talleres|Bayer Leverkusen|6.4; 2025-08|Bayer Leverkusen|Arsenal|Loan', ''],
        'Jurriën Timber' => ['RB', 'CB,LB', 179, 'R', 60, 60, '2025-06', 2028, 150, null, null, null, 'Full-Back', 19, 0, 'ERE:2', '2023-07|Ajax|Arsenal|40', '2023-08|Cruciate ligament tear|250'],
        'Riccardo Calafiori' => ['LB', 'CB', 188, 'L', 40, 45, '2024-12', 2029, 110, null, null, null, 'Ball-Playing Defender', 14, 0, '', '2024-07|Bologna|Arsenal|45', ''],
        'Myles Lewis-Skelly' => ['LB', 'CM', 178, 'L', 40, 45, '2025-06', 2030, 60, null, null, null, 'Inverted Full-Back', 3, 1, '', '2024-12|Arsenal U21|Arsenal|Academy', ''],
        'Martín Zubimendi' => ['DM', 'CM', 181, 'R', 70, 70, '2025-06', 2030, 230, null, null, null, 'Deep-Lying Playmaker', 21, 3, 'CDR:1 EURO:1 NL:1', '2025-07|Real Sociedad|Arsenal|70', ''],
        'Mikel Merino' => ['CM', 'CF,DM', 189, 'L', 35, 50, '2023-06', 2028, 160, null, null, null, 'Box-to-Box Midfielder', 37, 8, 'CDR:1 EURO:1 NL:1', '2018-07|Newcastle United|Real Sociedad|12; 2024-08|Real Sociedad|Arsenal|37', ''],
        'Eberechi Eze' => ['AM', 'LW', 178, 'R', 65, 65, '2025-06', 2029, 210, null, null, null, 'Advanced Playmaker', 11, 1, 'FAC:1', '2020-08|Queens Park Rangers|Crystal Palace|19.5; 2025-08|Crystal Palace|Arsenal|69', ''],
        'Kai Havertz' => ['CF', 'AM', 193, 'L', 60, 81, '2020-06', 2029, 330, null, null, null, 'False Nine', 54, 20, 'UCL:1 CWC:1', '2020-09|Bayer Leverkusen|Chelsea|80; 2023-06|Chelsea|Arsenal|75', '2025-02|Hamstring surgery|200'],
        'Kepa Arrizabalaga' => ['GK', '', 186, 'R', 5, 45, '2018-12', 2027, 90, null, null, null, 'Shot Stopper', 13, 0, 'UCL:1 UEL:1 CWC:1', '2018-08|Athletic Club|Chelsea|80; 2025-07|Chelsea|Arsenal|5.8', ''],
        'Noni Madueke' => ['RW', 'LW', 182, 'L', 45, 45, '2025-06', 2030, 140, null, null, null, 'Inverted Winger', 10, 1, 'UECL:1 CWC:1', '2023-01|PSV Eindhoven|Chelsea|35; 2025-07|Chelsea|Arsenal|56', ''],
        'Viktor Gyökeres' => ['CF', '', 187, 'R', 75, 75, '2025-03', 2030, 230, null, null, null, 'Complete Forward', 30, 17, '', '2023-07|Coventry City|Sporting CP|24; 2025-07|Sporting CP|Arsenal|65.8', ''],
        // Manchester City
        'Rúben Dias' => ['CB', '', 187, 'R', 50, 80, '2023-03', 2029, 290, null, 'Gestifute (Jorge Mendes)', null, 'Ball-Playing Defender', 70, 3, 'PL:4 FAC:1 LC:2 UCL:1 CWC:1 NL:2', '2020-09|Benfica|Manchester City|71.6', ''],
        'Rodri' => ['DM', 'CM,CB', 191, 'R', 65, 130, '2024-05', 2027, 255, null, null, null, 'Deep-Lying Playmaker', 60, 4, 'PL:4 FAC:1 LC:2 UCL:1 CWC:1 EURO:1 NL:1', '2018-07|Villarreal|Atlético Madrid|20; 2019-07|Atlético Madrid|Manchester City|70', '2024-09|Cruciate ligament tear|230'],
        'Phil Foden' => ['AM', 'LW,RW,CM', 171, 'L', 80, 150, '2024-05', 2027, 260, null, null, 'Nike', 'Shadow Striker', 47, 4, 'PL:6 FAC:2 LC:4 UCL:1 CWC:1', '2017-07|Manchester City U23|Manchester City|Academy', ''],
        'Erling Haaland' => ['CF', '', 195, 'L', 200, 200, '2023-12', 2034, 600, null, 'Rafaela Pimenta', 'Nike', 'Poacher', 48, 55, 'PL:2 FAC:1 UCL:1 CWC:1 DFB:1', '2019-01|Molde|Red Bull Salzburg|8; 2020-01|Red Bull Salzburg|Borussia Dortmund|20; 2022-07|Borussia Dortmund|Manchester City|60', '2023-12|Bone stress injury (foot)|49'],
        // Liverpool
        'Alisson' => ['GK', '', 193, 'R', 15, 70, '2019-12', 2027, 175, null, null, null, 'Sweeper Keeper', 73, 0, 'PL:2 FAC:1 LC:2 UCL:1 CWC:1 CA:1', '2016-07|Internacional|Roma|8; 2018-07|Roma|Liverpool|72.5', '2024-10|Hamstring injury|45'],
        'Virgil van Dijk' => ['CB', '', 195, 'R', 23, 80, '2019-12', 2027, 400, null, null, 'Nike', 'Ball-Playing Defender', 88, 10, 'PL:2 FAC:1 LC:2 UCL:1 CWC:1', '2013-06|Groningen|Celtic|3; 2015-09|Celtic|Southampton|15.4; 2018-01|Southampton|Liverpool|84.65', '2020-10|Cruciate ligament tear|280'],
        'Alexis Mac Allister' => ['CM', 'DM,AM', 176, 'R', 80, 100, '2025-06', 2028, 175, null, null, null, 'Deep-Lying Playmaker', 40, 4, 'PL:1 LC:1 WC:1 CA:1', '2019-01|Argentinos Juniors|Brighton|8; 2023-06|Brighton|Liverpool|42', ''],
        'Florian Wirtz' => ['AM', 'LW,CM', 177, 'R', 110, 140, '2024-12', 2030, 230, null, null, null, 'Advanced Playmaker', 38, 8, 'BL:1 DFB:1', '2020-01|1. FC Köln U17|Bayer Leverkusen|0.2; 2025-06|Bayer Leverkusen|Liverpool|125', '2022-03|Cruciate ligament tear|280'],
        'Mohamed Salah' => ['RW', 'CF', 175, 'L', 40, 150, '2018-06', 2027, 465, null, 'Ramy Abbas Issa', 'Adidas', 'Inside Forward', 108, 63, 'PL:2 FAC:1 LC:2 UCL:1 CWC:1', '2014-01|FC Basel|Chelsea|16.5; 2015-08|Chelsea|Roma|Loan; 2016-07|Chelsea|Roma|15; 2017-06|Roma|Liverpool|42', '2024-02|Hamstring injury|25'],
        // Chelsea
        'Robert Sánchez' => ['GK', '', 197, 'R', 20, 25, '2023-06', 2030, 60, null, null, null, 'Sweeper Keeper', 2, 0, 'UECL:1 CWC:1', '2023-08|Brighton|Chelsea|29', ''],
        'Reece James' => ['RB', 'CB,CM', 179, 'R', 45, 75, '2022-06', 2028, 290, null, null, null, 'Complete Wing-Back', 22, 1, 'UCL:1 UECL:1 CWC:2', '2018-07|Chelsea|Wigan Athletic|Loan; 2019-07|Wigan Athletic|Chelsea|End of loan', '2023-12|Hamstring surgery|100'],
        'Moisés Caicedo' => ['DM', 'CM,RB', 178, 'R', 90, 100, '2025-06', 2031, 175, null, null, null, 'Ball-Winning Midfielder', 52, 3, 'UECL:1 CWC:1', '2021-02|Independiente del Valle|Brighton|5; 2023-08|Brighton|Chelsea|116', ''],
        'Enzo Fernández' => ['CM', 'DM,AM', 178, 'R', 80, 90, '2025-06', 2032, 210, null, null, null, 'Deep-Lying Playmaker', 37, 5, 'WC:1 CA:1 UECL:1 CWC:1', '2022-07|River Plate|Benfica|10; 2023-01|Benfica|Chelsea|121', ''],
        'Cole Palmer' => ['AM', 'RW,CF', 189, 'L', 120, 130, '2025-06', 2033, 120, null, null, 'Nike', 'Advanced Playmaker', 10, 2, 'PL:1 UCL:1 UECL:1 CWC:2', '2023-09|Manchester City|Chelsea|47', '2025-09|Groin injury|60'],
        // Real Madrid
        'Thibaut Courtois' => ['GK', '', 200, 'L', 25, 75, '2018-06', 2027, 290, 1000, null, null, 'Shot Stopper', 105, 0, 'LL:4 CDR:2 PL:2 FAC:1 UCL:2 UEL:1 CWC:2', '2011-07|Genk|Chelsea|8.95; 2011-07|Chelsea|Atlético Madrid|Loan; 2018-08|Chelsea|Real Madrid|35', '2023-08|Cruciate ligament tear|270; 2024-03|Meniscus tear|50'],
        'Antonio Rüdiger' => ['CB', '', 190, 'R', 12, 40, '2021-06', 2027, 190, 400, null, null, 'Stopper', 76, 3, 'LL:1 CDR:1 FAC:1 UCL:2 UEL:1 CWC:2', '2015-08|VfB Stuttgart|Roma|9; 2017-07|Roma|Chelsea|35; 2022-07|Chelsea|Real Madrid|Free', '2025-04|Knee surgery (meniscus)|90'],
        'Jude Bellingham' => ['AM', 'CM', 186, 'R', 160, 180, '2024-05', 2029, 400, 1000, null, 'Adidas', 'Box-to-Box Midfielder', 42, 6, 'LL:1 UCL:1 DFB:1', '2020-07|Birmingham City|Borussia Dortmund|25; 2023-07|Borussia Dortmund|Real Madrid|103', '2025-07|Shoulder surgery|85'],
        'Vinícius Júnior' => ['LW', 'CF', 176, 'R', 150, 200, '2024-05', 2027, 400, 1000, null, 'Nike', 'Inside Forward', 43, 8, 'LL:3 CDR:1 UCL:2 CWC:2', '2018-07|Flamengo|Real Madrid|45', ''],
        'Kylian Mbappé' => ['CF', 'LW', 178, 'R', 200, 200, '2018-12', 2029, 600, 1000, null, 'Nike', 'Advanced Forward', 93, 55, 'L1:7 CDF:4 WC:1 NL:1', '2017-08|Monaco|Paris Saint-Germain|Loan; 2018-07|Monaco|Paris Saint-Germain|180; 2024-07|Paris Saint-Germain|Real Madrid|Free', ''],
        'Franco Mastantuono' => ['AM', 'RW,LW', 176, 'L', 50, 50, '2025-06', 2031, 115, null, null, null, 'Inside Forward', 4, 0, '', '2024-01|River Plate Reserves|River Plate|Academy; 2025-08|River Plate|Real Madrid|45', ''],        // Barcelona
        'Pau Cubarsí' => ['CB', '', 184, 'R', 80, 80, '2025-06', 2029, 60, 500, null, null, 'Ball-Playing Defender', 8, 0, 'LL:1 CDR:1', '2024-01|Barcelona Atlètic|Barcelona|Academy', ''],
        'Pedri' => ['CM', 'AM,LW', 174, 'R', 140, 140, '2025-06', 2030, 280, 1000, null, null, 'Deep-Lying Playmaker', 38, 3, 'LL:2 CDR:1 EURO:1', '2020-07|Las Palmas|Barcelona|5', '2023-08|Hamstring injury|70; 2024-07|Knee ligament injury|30'],
        'Lamine Yamal' => ['RW', 'AM', 180, 'L', 200, 200, '2025-06', 2031, 300, 1000, 'Jorge Mendes', 'Adidas', 'Inverted Winger', 23, 6, 'LL:2 CDR:1 EURO:1', '2023-04|Barcelona Atlètic|Barcelona|Academy', '2025-09|Groin injury (pubalgia)|40'],
        'Robert Lewandowski' => ['CF', '', 185, 'R', 12, 90, '2018-03', 2027, 640, 500, 'Pini Zahavi', 'Nike', 'Complete Forward', 160, 86, 'BL:10 DFB:4 LL:2 CDR:1 UCL:1 CWC:1', '2010-07|Lech Poznań|Borussia Dortmund|4.75; 2014-07|Borussia Dortmund|Bayern Munich|Free; 2022-07|Bayern Munich|Barcelona|45', ''],
        'Raphinha' => ['LW', 'RW,AM', 176, 'L', 80, 80, '2025-06', 2028, 260, 1000, null, null, 'Inside Forward', 41, 12, 'LL:2 CDR:1', '2018-07|Sporting CP|Rennes|21; 2020-10|Rennes|Leeds United|21; 2022-07|Leeds United|Barcelona|58', ''],
        'Marc-André ter Stegen' => ['GK', '', 187, 'R', 6, 60, '2018-06', 2028, 340, 500, null, null, 'Sweeper Keeper', 44, 0, 'LL:6 CDR:6 UCL:1 CWC:1', '2014-07|Borussia Mönchengladbach|Barcelona|12', '2024-09|Patellar tendon rupture|240; 2025-07|Back surgery|120'],
        'Jules Koundé' => ['RB', 'CB', 178, 'R', 60, 65, '2025-06', 2030, 220, 1000, null, null, 'Full-Back', 42, 2, 'LL:2 CDR:1 UEL:1 NL:1', '2019-07|Bordeaux|Sevilla|25; 2022-07|Sevilla|Barcelona|50', ''],
        'Ronald Araújo' => ['CB', 'RB', 188, 'R', 40, 70, '2023-06', 2031, 220, 1000, null, null, 'Stopper', 26, 2, 'LL:2 CDR:2', '2018-08|Boston River|Barcelona B|1.7', '2024-07|Hamstring surgery|150'],
        'Alejandro Balde' => ['LB', '', 175, 'L', 50, 60, '2023-06', 2028, 120, 1000, null, null, 'Attacking Wing-Back', 10, 0, 'LL:2 CDR:1', '2021-09|Barcelona Atlètic|Barcelona|Academy', '2023-12|Hamstring surgery|160'],
        'Frenkie de Jong' => ['CM', 'DM', 181, 'R', 45, 90, '2019-12', 2029, 350, 500, null, null, 'Deep-Lying Playmaker', 64, 2, 'LL:2 CDR:2 ERE:1', '2016-07|Willem II|Ajax|1; 2019-07|Ajax|Barcelona|86', '2024-04|Ankle ligament injury|180'],
        'Gavi' => ['CM', 'AM,LW', 173, 'R', 50, 90, '2023-03', 2030, 270, 1000, 'Iván de la Peña', null, 'Box-to-Box Midfielder', 28, 5, 'LL:2 CDR:1 NL:1', '2021-08|Barcelona Atlètic|Barcelona|Academy', '2023-11|Cruciate ligament tear|310; 2025-09|Meniscus surgery|120'],
        'Dani Olmo' => ['AM', 'LW,SS', 179, 'R', 60, 60, '2024-06', 2030, 260, 500, null, null, 'Advanced Playmaker', 45, 11, 'LL:1 CDR:1 DFB:2 EURO:1 NL:1', '2014-07|Barcelona U17|Dinamo Zagreb|Free; 2020-01|Dinamo Zagreb|RB Leipzig|20; 2024-08|RB Leipzig|Barcelona|55', '2024-10|Hamstring injury|40'],        // Atlético Madrid
        'Jan Oblak' => ['GK', '', 188, 'R', 20, 90, '2019-06', 2028, 440, 120, null, null, 'Shot Stopper', 72, 0, 'LL:1 UEL:1', '2014-07|Benfica|Atlético Madrid|16', ''],
        'José María Giménez' => ['CB', 'RB', 185, 'R', 10, 50, '2019-06', 2028, 140, null, null, null, 'Stopper', 86, 8, 'LL:2 UEL:1', '2013-07|Danubio|Atlético Madrid|0.9', ''],
        'Koke' => ['CM', 'DM', 176, 'R', 4, 50, '2018-06', 2027, 200, null, null, null, 'Deep-Lying Playmaker', 72, 0, 'LL:2 CDR:1 UEL:2', '2009-10|Atlético Madrid B|Atlético Madrid|Academy', ''],
        'Antoine Griezmann' => ['SS', 'CF,AM', 176, 'L', 10, 150, '2018-06', 2027, 400, null, null, 'Puma', 'Deep-Lying Forward', 137, 44, 'CDR:1 UEL:1 WC:1 NL:1', '2014-07|Real Sociedad|Atlético Madrid|30; 2019-07|Atlético Madrid|Barcelona|120; 2021-08|Barcelona|Atlético Madrid|Loan; 2022-10|Barcelona|Atlético Madrid|22', ''],
        'Julián Álvarez' => ['CF', 'SS,RW', 170, 'R', 100, 100, '2025-06', 2030, 230, 500, null, null, 'Pressing Forward', 45, 13, 'PL:1 FAC:1 UCL:1 CWC:1 WC:1 CA:1', '2022-07|River Plate|Manchester City|17; 2024-08|Manchester City|Atlético Madrid|75', ''],
        // Inter
        'Yann Sommer' => ['GK', '', 183, 'R', 3, 15, '2016-06', 2027, 50, null, null, null, 'Shot Stopper', 94, 0, 'SA:1 BL:1', '2014-07|FC Basel|Borussia Mönchengladbach|7.5; 2023-01|Borussia Mönchengladbach|Bayern Munich|8; 2023-08|Bayern Munich|Inter|6.75', ''],
        'Alessandro Bastoni' => ['CB', 'LB', 190, 'L', 70, 80, '2024-05', 2028, 110, null, 'Tullio Tinti', null, 'Ball-Playing Defender', 40, 1, 'SA:2 CI:2 EURO:1', '2017-08|Atalanta|Inter|31.1', ''],
        'Nicolò Barella' => ['CM', 'AM,DM', 172, 'R', 70, 80, '2023-06', 2028, 130, null, null, null, 'Box-to-Box Midfielder', 68, 10, 'SA:2 CI:2 EURO:1', '2019-07|Cagliari|Inter|45', ''],
        'Hakan Çalhanoğlu' => ['DM', 'CM,AM', 178, 'R', 20, 40, '2016-06', 2027, 115, null, null, null, 'Regista', 98, 21, 'SA:1 CI:2', '2014-07|Hamburger SV|Bayer Leverkusen|14.5; 2017-07|Bayer Leverkusen|Milan|24; 2021-07|Milan|Inter|Free', ''],
        'Lautaro Martínez' => ['CF', 'SS', 174, 'R', 85, 110, '2024-05', 2029, 210, null, 'Alejandro Camaño', null, 'Complete Forward', 71, 33, 'SA:2 CI:2 WC:1 CA:2', '2018-07|Racing Club|Inter|25', ''],
        'Marcus Thuram' => ['CF', 'LW', 192, 'R', 60, 75, '2024-05', 2028, 115, null, null, null, 'Target Forward', 33, 4, 'SA:1', '2019-07|Guingamp|Borussia Mönchengladbach|12; 2023-07|Borussia Mönchengladbach|Inter|Free', ''],
        // Milan
        'Mike Maignan' => ['GK', '', 191, 'R', 35, 50, '2023-03', 2027, 95, null, null, null, 'Sweeper Keeper', 30, 0, 'SA:1 L1:1', '2015-07|Paris Saint-Germain|Lille|1; 2021-07|Lille|Milan|15', ''],
        'Fikayo Tomori' => ['CB', '', 185, 'R', 25, 45, '2022-06', 2027, 75, null, null, null, 'Ball-Playing Defender', 5, 0, 'SA:1', '2021-01|Chelsea|Milan|Loan; 2021-07|Chelsea|Milan|28', ''],
        'Luka Modrić' => ['CM', 'AM,DM', 172, 'R', 4, 65, '2016-06', 2027, 110, null, null, 'Nike', 'Advanced Playmaker', 192, 28, 'LL:4 CDR:2 UCL:6 CWC:5', '2008-07|Dinamo Zagreb|Tottenham Hotspur|21; 2012-08|Tottenham Hotspur|Real Madrid|35; 2025-07|Real Madrid|Milan|Free', ''],
        'Christian Pulisic' => ['RW', 'LW,AM', 177, 'R', 45, 60, '2019-06', 2027, 95, null, null, 'Nike', 'Inside Forward', 80, 32, 'UCL:1 CWC:1 DFB:1', '2019-07|Borussia Dortmund|Chelsea|64; 2023-07|Chelsea|Milan|20', ''],
        'Rafael Leão' => ['LW', 'CF', 188, 'R', 75, 90, '2023-06', 2028, 160, null, null, 'Nike', 'Inside Forward', 40, 5, 'SA:1', '2018-08|Sporting CP|Lille|Free; 2019-08|Lille|Milan|29.5', ''],
        // Juventus
        'Gleison Bremer' => ['CB', '', 188, 'R', 40, 60, '2023-06', 2029, 125, null, null, null, 'Stopper', 4, 0, 'CI:1', '2018-07|Atlético Mineiro|Torino|5.8; 2022-07|Torino|Juventus|41', '2024-10|Cruciate ligament tear|250'],
        'Manuel Locatelli' => ['DM', 'CM', 185, 'R', 25, 45, '2021-06', 2028, 95, null, null, null, 'Regista', 30, 3, 'CI:1 EURO:1', '2018-07|Milan|Sassuolo|12.5; 2021-08|Sassuolo|Juventus|Loan; 2023-07|Sassuolo|Juventus|25', ''],
        'Kenan Yıldız' => ['LW', 'AM,SS', 185, 'R', 75, 75, '2025-06', 2029, 115, null, null, null, 'Inside Forward', 26, 4, 'CI:1', '2022-07|Bayern Munich U19|Juventus|Free', ''],
        'Dušan Vlahović' => ['CF', '', 190, 'L', 25, 85, '2022-03', 2027, 230, null, null, null, 'Target Forward', 38, 15, 'CI:1', '2018-07|Partizan|Fiorentina|1.9; 2022-01|Fiorentina|Juventus|81.6', '2022-10|Pubalgia|60'],
        // Napoli
        'Alex Meret' => ['GK', '', 190, 'L', 12, 25, '2019-06', 2027, 55, null, null, null, 'Shot Stopper', 3, 0, 'SA:2 CI:1 EURO:1', '2018-07|Udinese|Napoli|22', ''],
        'Giovanni Di Lorenzo' => ['RB', 'CB', 183, 'R', 15, 35, '2023-06', 2028, 85, null, null, null, 'Complete Wing-Back', 45, 3, 'SA:2 CI:1 EURO:1', '2019-07|Empoli|Napoli|9', ''],
        'Scott McTominay' => ['CM', 'AM,DM', 191, 'R', 40, 45, '2025-06', 2028, 100, null, null, null, 'Box-to-Box Midfielder', 67, 13, 'SA:1 FAC:1 LC:1', '2017-05|Manchester United U23|Manchester United|Academy; 2024-08|Manchester United|Napoli|30.5', ''],
        'Kevin De Bruyne' => ['AM', 'CM,RW', 181, 'R', 15, 150, '2018-12', 2027, 115, null, 'Roc Nation Sports', 'Nike', 'Advanced Playmaker', 109, 30, 'PL:6 FAC:2 LC:5 UCL:1 CWC:1 DFB:1', '2012-01|Genk|Chelsea|8; 2014-01|Chelsea|VfL Wolfsburg|22; 2015-08|VfL Wolfsburg|Manchester City|76; 2025-07|Manchester City|Napoli|Free', '2023-08|Hamstring surgery|120'],
        'Romelu Lukaku' => ['CF', '', 191, 'L', 20, 100, '2021-06', 2027, 160, null, null, null, 'Target Forward', 119, 89, 'SA:2', '2014-07|Chelsea|Everton|35.36; 2017-07|Everton|Manchester United|84.7; 2019-08|Manchester United|Inter|74; 2021-08|Inter|Chelsea|113; 2024-08|Chelsea|Napoli|30', '2025-08|Thigh muscle injury|150'],
        // Roma
        'Mile Svilar' => ['GK', '', 189, 'L', 40, 40, '2025-06', 2030, 70, null, null, null, 'Sweeper Keeper', 5, 0, '', '2017-08|Anderlecht|Benfica|2.5; 2022-07|Benfica|Roma|Free', ''],
        'Gianluca Mancini' => ['CB', '', 190, 'R', 10, 25, '2021-06', 2027, 60, null, null, null, 'Stopper', 10, 1, 'UECL:1', '2019-07|Atalanta|Roma|23', ''],
        'Lorenzo Pellegrini' => ['AM', 'CM', 186, 'R', 12, 40, '2020-03', 2027, 115, null, null, null, 'Advanced Playmaker', 37, 6, 'UECL:1', '2017-07|Sassuolo|Roma|10', ''],
        'Paulo Dybala' => ['SS', 'RW,AM', 177, 'L', 8, 110, '2018-06', 2027, 110, null, null, 'Adidas', 'Deep-Lying Forward', 40, 4, 'SA:5 CI:4 UECL:1 WC:1', '2015-07|Palermo|Juventus|40; 2022-07|Juventus|Roma|Free', ''],
        // Bayern Munich
        'Manuel Neuer' => ['GK', '', 193, 'R', 4, 45, '2014-12', 2027, 365, null, null, 'Adidas', 'Sweeper Keeper', 124, 0, 'BL:12 DFB:6 UCL:2 CWC:2 WC:1', '2011-07|Schalke 04|Bayern Munich|30', '2022-12|Broken leg|300'],
        'Joshua Kimmich' => ['DM', 'RB,CM', 177, 'R', 40, 95, '2020-12', 2029, 365, null, null, 'Adidas', 'Deep-Lying Playmaker', 104, 8, 'BL:9 DFB:3 UCL:1 CWC:1', '2015-07|RB Leipzig|Bayern Munich|8.5', '2020-10|Meniscus surgery|60'],
        'Jamal Musiala' => ['AM', 'LW,CM', 184, 'R', 130, 140, '2025-03', 2030, 385, null, null, 'Adidas', 'Shadow Striker', 40, 8, 'BL:5 UCL:1 CWC:1', '2019-07|Chelsea U17|Bayern Munich|Free', '2025-07|Fractured fibula & ankle dislocation|180'],
        'Michael Olise' => ['RW', 'AM', 184, 'L', 110, 110, '2025-06', 2029, 190, null, null, null, 'Inverted Winger', 8, 2, 'BL:1', '2021-07|Reading|Crystal Palace|9.2; 2024-07|Crystal Palace|Bayern Munich|53', ''],
        'Harry Kane' => ['CF', 'SS', 188, 'R', 75, 150, '2018-06', 2027, 480, null, 'Charlie Kane', 'Nike', 'Complete Forward', 106, 75, 'BL:1', '2010-07|Tottenham Hotspur U18|Tottenham Hotspur|Academy; 2023-08|Tottenham Hotspur|Bayern Munich|95', ''],
        // Bayer Leverkusen
        'Alejandro Grimaldo' => ['LB', 'CM', 171, 'L', 25, 35, '2024-05', 2027, 100, null, null, null, 'Complete Wing-Back', 6, 0, 'BL:1 DFB:1', '2016-01|Barcelona B|Benfica|1.5; 2023-07|Benfica|Bayer Leverkusen|Free', ''],
        'Robert Andrich' => ['DM', 'CM,CB', 187, 'R', 8, 16, '2023-06', 2027, 75, null, null, null, 'Ball-Winning Midfielder', 19, 0, 'BL:1 DFB:1', '2021-07|Union Berlin|Bayer Leverkusen|6.5', ''],
        'Exequiel Palacios' => ['CM', 'DM', 177, 'R', 25, 35, '2024-05', 2028, 95, null, null, null, 'Box-to-Box Midfielder', 31, 0, 'BL:1 DFB:1 WC:1', '2020-01|River Plate|Bayer Leverkusen|17.5', ''],
        'Malik Tillman' => ['AM', 'RW,CM', 187, 'L', 30, 30, '2025-06', 2030, 80, null, null, null, 'Advanced Playmaker', 25, 4, '', '2024-07|Bayern Munich|PSV Eindhoven|12; 2025-07|PSV Eindhoven|Bayer Leverkusen|35', ''],
        'Patrik Schick' => ['CF', '', 191, 'L', 20, 40, '2021-12', 2027, 95, null, null, null, 'Target Forward', 46, 23, 'BL:1 DFB:1', '2017-08|Sampdoria|Roma|42; 2019-09|Roma|RB Leipzig|Loan; 2020-09|Roma|Bayer Leverkusen|26.5', ''],
        // Borussia Dortmund
        'Gregor Kobel' => ['GK', '', 194, 'R', 40, 45, '2023-12', 2028, 130, null, null, null, 'Sweeper Keeper', 18, 0, '', '2021-07|VfB Stuttgart|Borussia Dortmund|15', ''],
        'Nico Schlotterbeck' => ['CB', 'LB', 191, 'L', 45, 45, '2025-06', 2027, 130, null, null, null, 'Ball-Playing Defender', 17, 0, '', '2022-07|SC Freiburg|Borussia Dortmund|20', '2025-04|Meniscus surgery|120'],
        'Julian Brandt' => ['AM', 'CM,LW', 185, 'R', 15, 45, '2017-06', 2027, 155, null, null, null, 'Advanced Playmaker', 47, 3, 'DFB:1', '2014-01|VfL Wolfsburg U19|Bayer Leverkusen|0.35; 2019-07|Bayer Leverkusen|Borussia Dortmund|25', ''],
        'Karim Adeyemi' => ['LW', 'CF,RW', 180, 'R', 40, 40, '2025-06', 2027, 95, null, null, null, 'Inside Forward', 7, 2, '', '2018-07|SpVgg Unterhaching|Red Bull Salzburg|3; 2022-07|Red Bull Salzburg|Borussia Dortmund|30', ''],
        'Serhou Guirassy' => ['CF', '', 187, 'R', 45, 45, '2025-06', 2028, 135, null, null, null, 'Poacher', 30, 15, '', '2022-07|Rennes|VfB Stuttgart|9; 2024-07|VfB Stuttgart|Borussia Dortmund|18', ''],
        // Paris Saint-Germain
        'Lucas Chevalier' => ['GK', '', 189, 'R', 45, 45, '2025-06', 2030, 140, null, null, null, 'Sweeper Keeper', 0, 0, '', '2021-07|Lille B|Lille|Academy; 2025-08|Lille|Paris Saint-Germain|40', ''],
        'Achraf Hakimi' => ['RB', 'RW', 181, 'R', 80, 80, '2025-06', 2029, 375, null, 'Alejandro Camaño', 'Puma', 'Attacking Wing-Back', 90, 11, 'L1:4 CDF:2 SA:1 UCL:1', '2018-07|Real Madrid|Borussia Dortmund|Loan; 2020-07|Real Madrid|Inter|43; 2021-07|Inter|Paris Saint-Germain|60', ''],
        'Marquinhos' => ['CB', 'DM', 183, 'R', 35, 75, '2019-06', 2028, 390, null, null, null, 'Ball-Playing Defender', 97, 7, 'L1:10 CDF:8 UCL:1 CA:1', '2012-08|Corinthians|Roma|3; 2013-07|Roma|Paris Saint-Germain|31.4', ''],
        'Vitinha' => ['CM', 'DM', 172, 'R', 110, 110, '2025-06', 2029, 230, null, 'Jorge Mendes', null, 'Deep-Lying Playmaker', 30, 2, 'L1:3 CDF:2 UCL:1 NL:1', '2022-07|Porto|Paris Saint-Germain|41.5', ''],
        'Ousmane Dembélé' => ['RW', 'CF,LW', 178, 'B', 100, 100, '2025-09', 2028, 330, null, 'Moussa Sissoko', 'Puma', 'False Nine', 56, 6, 'L1:3 CDF:2 LL:2 CDR:2 DFB:1 UCL:1 WC:1', '2016-07|Rennes|Borussia Dortmund|15; 2017-08|Borussia Dortmund|Barcelona|148; 2023-08|Barcelona|Paris Saint-Germain|50.4', '2025-09|Hamstring injury|45'],
        'Khvicha Kvaratskhelia' => ['LW', 'RW', 183, 'R', 90, 90, '2025-06', 2029, 300, null, 'Mamuka Jugeli', 'Nike', 'Inside Forward', 45, 19, 'SA:1 L1:1 CDF:1 UCL:1', '2022-07|Dinamo Batumi|Napoli|10; 2025-01|Napoli|Paris Saint-Germain|70', ''],
        // Monaco
        'Thilo Kehrer' => ['CB', 'RB', 186, 'R', 5, 35, '2019-06', 2027, 60, null, null, null, 'Stopper', 26, 0, 'L1:2 CDF:2', '2018-08|Schalke 04|Paris Saint-Germain|37; 2022-08|Paris Saint-Germain|West Ham United|12; 2024-01|West Ham United|Monaco|11', ''],
        'Denis Zakaria' => ['DM', 'CM,CB', 191, 'R', 25, 32, '2019-06', 2027, 75, null, null, null, 'Ball-Winning Midfielder', 59, 4, '', '2017-07|Young Boys|Borussia Mönchengladbach|12; 2022-01|Borussia Mönchengladbach|Juventus|4.5; 2023-07|Juventus|Monaco|20', ''],
        'Aleksandr Golovin' => ['AM', 'CM,LW', 180, 'R', 10, 30, '2018-06', 2027, 115, null, null, null, 'Advanced Playmaker', 55, 9, '', '2018-07|CSKA Moscow|Monaco|30', ''],
        'Takumi Minamino' => ['LW', 'AM,SS', 174, 'R', 10, 15, '2020-01', 2027, 60, null, null, null, 'Shadow Striker', 71, 25, 'PL:1 FAC:1 LC:1 CWC:1', '2020-01|Red Bull Salzburg|Liverpool|8.5; 2022-06|Liverpool|Monaco|15', ''],
        'Folarin Balogun' => ['CF', '', 178, 'R', 25, 40, '2023-09', 2028, 70, null, null, null, 'Advanced Forward', 18, 5, '', '2023-08|Arsenal|Monaco|40', ''],
        // Marseille
        'Gerónimo Rulli' => ['GK', '', 189, 'R', 8, 12, '2017-06', 2028, 45, null, null, null, 'Shot Stopper', 3, 0, 'UEL:1', '2023-01|Villarreal|Ajax|8; 2024-08|Ajax|Marseille|3', ''],
        'Leonardo Balerdi' => ['CB', '', 187, 'R', 25, 25, '2025-06', 2028, 70, null, null, null, 'Ball-Playing Defender', 0, 0, '', '2019-01|Boca Juniors|Borussia Dortmund|15.5; 2021-07|Borussia Dortmund|Marseille|8', ''],
        'Pierre-Emile Højbjerg' => ['DM', 'CM', 185, 'R', 12, 40, '2021-06', 2028, 70, null, null, null, 'Ball-Winning Midfielder', 83, 5, '', '2016-07|Bayern Munich|Southampton|15; 2020-08|Southampton|Tottenham Hotspur|17.5; 2024-08|Tottenham Hotspur|Marseille|15', ''],
        'Pierre-Emerick Aubameyang' => ['CF', 'LW', 187, 'R', 1, 70, '2018-03', 2027, 75, null, null, null, 'Advanced Forward', 80, 37, 'FAC:1 DFB:1', '2013-07|Saint-Étienne|Borussia Dortmund|13; 2018-01|Borussia Dortmund|Arsenal|63.75; 2022-02|Arsenal|Barcelona|Free; 2022-09|Barcelona|Chelsea|12; 2023-07|Chelsea|Marseille|Free; 2025-07|Al-Qadsiah|Marseille|Free', ''],
    ],

    /** TheSportsDB team names, where they differ from ours (used to find the club id). */
    'tsdb_search' => [
        'INT' => 'Inter Milan', 'MIL' => 'AC Milan', 'ATM' => 'Atletico Madrid', 'PSG' => 'Paris SG',
        'ASM' => 'Monaco', 'MUN' => 'Manchester United',
    ],

    /*
     * Search names, NOT data: likely first-team players per club as of my latest
     * knowledge. `football:sync-tsdb` looks each one up and a player only joins a
     * squad if TheSportsDB currently places them at that club (squads are capped at
     * 18). Stale names are simply ignored.
     */
    'candidates' => [
        'ARS' => ['Gabriel Magalhães', 'Ben White', 'Jurriën Timber', 'Riccardo Calafiori', 'Piero Hincapié', 'Martín Zubimendi', 'Mikel Merino', 'Eberechi Eze', 'Gabriel Martinelli', 'Leandro Trossard', 'Viktor Gyökeres', 'Kai Havertz', 'Noni Madueke', 'Kepa Arrizabalaga', 'Myles Lewis-Skelly'],
        'MCI' => ['Gianluigi Donnarumma', 'Joško Gvardiol', 'Rayan Aït-Nouri', 'Matheus Nunes', "Nico O'Reilly", 'Abdukodir Khusanov', 'John Stones', 'Tijjani Reijnders', 'Mateo Kovačić', 'Bernardo Silva', 'Rayan Cherki', 'Jérémy Doku', 'Omar Marmoush', 'Savinho', 'James Trafford', 'Nathan Aké', 'Rico Lewis', 'Oscar Bobb', 'Claudio Echeverri', 'Stefan Ortega', 'Vitor Reis', 'Divine Mukasa', 'Sverre Nypan', 'Nico González', 'Kalvin Phillips'],
        'LIV' => ['Giorgi Mamardashvili', 'Ibrahima Konaté', 'Milos Kerkez', 'Jeremie Frimpong', 'Conor Bradley', 'Andrew Robertson', 'Ryan Gravenberch', 'Dominik Szoboszlai', 'Curtis Jones', 'Alexander Isak', 'Hugo Ekitiké', 'Cody Gakpo', 'Federico Chiesa', 'Joe Gomez', 'Wataru Endo', 'Harvey Elliott', 'Rio Ngumoha', 'Giovanni Leoni', 'Freddie Woodman', 'Kostas Tsimikas', 'Trey Nyoni', 'Jayden Danns', 'Stefan Bajčetić'],
        'MUN' => ['André Onana', 'Senne Lammens', 'Altay Bayındır', 'Harry Maguire', 'Lisandro Martínez', 'Matthijs de Ligt', 'Leny Yoro', 'Luke Shaw', 'Diogo Dalot', 'Noussair Mazraoui', 'Patrick Dorgu', 'Ayden Heaven', 'Bruno Fernandes', 'Casemiro', 'Kobbie Mainoo', 'Manuel Ugarte', 'Mason Mount', 'Amad Diallo', 'Bryan Mbeumo', 'Matheus Cunha', 'Benjamin Šeško', 'Joshua Zirkzee'],
        'CHE' => ['Filip Jørgensen', 'Levi Colwill', 'Wesley Fofana', 'Trevoh Chalobah', 'Marc Cucurella', 'Malo Gusto', 'Romeo Lavia', 'Andrey Santos', 'João Pedro', 'Liam Delap', 'Pedro Neto', 'Estêvão', 'Jamie Gittens', 'Alejandro Garnacho', 'Jorrel Hato', 'Tosin Adarabioyo', 'Josh Acheampong', 'Mamadou Sarr', 'Dário Essugo', 'Tyrique George', 'Marc Guiu', 'Gaga Slonina', 'Aaron Anselmino', 'Benoît Badiashile'],
        'RMA' => ['Éder Militão', 'Dean Huijsen', 'Trent Alexander-Arnold', 'Álvaro Carreras', 'Ferland Mendy', 'Dani Carvajal', 'Federico Valverde', 'Aurélien Tchouaméni', 'Eduardo Camavinga', 'Arda Güler', 'Rodrygo', 'Gonzalo García', 'Brahim Díaz', 'Andriy Lunin'],
        'BAR' => ['Joan García', 'Eric García', 'Andreas Christensen', 'Ferran Torres', 'Fermín López', 'Marcus Rashford', 'Marc Casadó', 'Gerard Martín', 'Wojciech Szczęsny', 'Marc Bernal'],
        'ATM' => ['Robin Le Normand', 'David Hancko', 'Clément Lenglet', 'Marc Pubill', 'Nahuel Molina', 'Matteo Ruggeri', 'Pablo Barrios', 'Conor Gallagher', 'Johnny Cardoso', 'Thiago Almada', 'Álex Baena', 'Giuliano Simeone', 'Alexander Sørloth', 'Giacomo Raspadori', 'Juan Musso', 'Javi Galán', 'Carlos Martín', 'Javi Serrano', 'Thiago Almada', 'Koke', 'Nahuel Molina', 'Nicolás González', 'Obed Vargas', 'Rodrigo Mendoza'],
        'INT' => ['Josep Martínez', 'Yann Bisseck', 'Francesco Acerbi', 'Stefan de Vrij', 'Manuel Akanji', 'Federico Dimarco', 'Denzel Dumfries', 'Henrikh Mkhitaryan', 'Davide Frattesi', 'Petar Sučić', 'Piotr Zieliński', 'Ange-Yoan Bonny', 'Francesco Pio Esposito', 'Carlos Augusto', 'Raffaele Di Gennaro', 'Tomás Palacios', 'Nicola Zalewski', 'Matteo Darmian', 'Luis Henrique', 'Valentín Carboni'],
        'MIL' => ['Strahinja Pavlović', 'Matteo Gabbia', 'Koni De Winter', 'Pervis Estupiñán', 'Alexis Saelemaekers', 'Youssouf Fofana', 'Adrien Rabiot', 'Samuele Ricci', 'Ruben Loftus-Cheek', 'Ardon Jashari', 'Christopher Nkunku', 'Santiago Giménez', 'Davide Bartesaghi', 'Pietro Terracciano', 'Lorenzo Torriani', 'Zachary Athekame', 'Kevin Zeroli', 'Warren Bondo', 'Álex Jiménez', 'Yunus Musah', 'Mattia Liberali'],
        'JUV' => ['Michele Di Gregorio', 'Pierre Kalulu', 'Federico Gatti', 'Lloyd Kelly', 'Andrea Cambiaso', 'Juan Cabal', 'Teun Koopmeiners', 'Khéphren Thuram', 'Weston McKennie', 'Francisco Conceição', 'Jonathan David', 'Loïs Openda', 'Edon Zhegrova', 'Mattia Perin'],
        'NAP' => ['Vanja Milinković-Savić', 'Amir Rrahmani', 'Alessandro Buongiorno', 'Sam Beukema', 'Mathías Olivera', 'Leonardo Spinazzola', 'Stanislav Lobotka', 'André-Frank Zambo Anguissa', 'Billy Gilmour', 'Matteo Politano', 'David Neres', 'Noa Lang', 'Rasmus Højlund', 'Miguel Gutiérrez', 'Nikita Contini', 'Juan Jesus', 'Pasquale Mazzocchi', 'Eljif Elmas', 'Antonio Vergara', 'Lorenzo Lucca', 'Luca Marianucci'],
        'FCB' => ['Jonathan Tah', 'Dayot Upamecano', 'Kim Min-jae', 'Alphonso Davies', 'Josip Stanišić', 'Konrad Laimer', 'Hiroki Ito', 'Leon Goretzka', 'Aleksandar Pavlović', 'Tom Bischof', 'Serge Gnabry', 'Luis Díaz', 'Nicolas Jackson', 'Lennart Karl', 'Jonas Urbig', 'Sven Ulreich', 'Daniel Peretz', 'Raphaël Guerreiro', 'Wisdom Mike', 'Sacha Boey', 'Min-jae Kim', 'David Santos Daiber'],
        'B04' => ['Mark Flekken', 'Jarell Quansah', 'Edmond Tapsoba', 'Loïc Badé', 'Lucas Vázquez', 'Aleix García', 'Ibrahim Maza', 'Ernest Poku', 'Christian Kofane', 'Eliesse Ben Seghir', 'Equi Fernández', 'Nathan Tella', 'Martin Terrier', 'Janis Blaswich'],
        'BVB' => ['Waldemar Anton', 'Niklas Süle', 'Ramy Bensebaini', 'Julian Ryerson', 'Yan Couto', 'Daniel Svensson', 'Emre Can', 'Marcel Sabitzer', 'Felix Nmecha', 'Pascal Groß', 'Jobe Bellingham', 'Maximilian Beier', 'Fábio Silva', 'Carney Chukwuemeka', 'Alexander Meyer'],
        'PSG' => ['Willian Pacho', 'Nuno Mendes', 'Lucas Beraldo', 'Illia Zabarnyi', 'Lucas Hernández', 'João Neves', 'Fabián Ruiz', 'Warren Zaïre-Emery', 'Désiré Doué', 'Bradley Barcola', 'Gonçalo Ramos', 'Senny Mayulu', 'Lee Kang-in', 'Matvey Safonov', 'Arnau Tenas', 'Renato Marin', 'Quentin Ndjantou', 'Noham Kamara', 'Lucas Hernández', 'Gonçalo Ramos'],
        'ASM' => ['Philipp Köhn', 'Mohammed Salisu', 'Vanderson', 'Caio Henrique', 'Eric Dier', 'Kassoum Ouattara', 'Lamine Camara', 'Mamadou Coulibaly', 'Paul Pogba', 'Maghnes Akliouche', 'Ansu Fati', 'Mika Biereth', 'Aladji Bamba', 'Jordan Teze', 'Lukáš Hrádecký', 'Radosław Majecki', 'Wilfried Singo', 'Christian Mawissa', 'Krépin Diatta', 'Soungoutou Magassa', 'Stanis Idumbo-Muzambo', 'George Ilenikhena', 'Yann Liénard', 'Pape Cabral'],
    ],
    /*
     * Human-reviewed corrections that win over TheSportsDB (applied by PlayerRosterVerifier).
     * TheSportsDB is community-edited and occasionally wrong; every entry says why.
     */
    'roster_overrides' => [
        // Never seeded at any club.
        'exclude' => [
            // A batch of "Arsenal" records added on 2026-11-02 (same photo template, older
            // kit design, previous clubs' shirt numbers) — not genuine Arsenal signings.
            'Christos Tzolis' => 'not an Arsenal player (bad TheSportsDB record, reported 2026-11-18)',
            'Ezri Konsa' => 'not an Arsenal player (bad TheSportsDB record, reported 2026-11-18)',
            'Bruno Guimarães' => 'same bad 2026-11-02 "Arsenal" batch as Tzolis/Konsa (carries his Newcastle #39)',
            // Kit review 2026-11-18: TheSportsDB lists them at one of our clubs, but their
            // newest photo (taken this season, after 2026-07-01) shows another club.
            'Ibrahim Mbaye' => 'listed at PSG, photographed in an Aston Villa kit on 2026-09-05',
            'Benoit Badiashile' => 'listed at Napoli, photographed in a Chelsea kit on 2026-08-25',
            // La Liga false transfers (product owner, 2026-11-19).
            'Anthony Gordon' => 'not a Barcelona player (false TheSportsDB transfer)',
            'Ademola Lookman' => 'not an Atlético Madrid player (false TheSportsDB transfer)',
            'Exequiel Palacios' => 'listed at Leverkusen, photographed in an Ipswich Town kit on 2026-09-10',
        ],
        // Listed players seeded at their club even though TheSportsDB can't confirm it.
        'include' => [
            'ARS' => [
                'William Saliba',     // TheSportsDB's only record for him is his France profile
                'Gabriel Magalhães',  // not found on TheSportsDB
            ],
            // Club review 2026-11-18. TheSportsDB's only record is the national-team profile
            // (or none): their club is taken from the reviewer's knowledge (2025/26 squads).
            'MCI' => ['Rayan Cherki', 'Jérémy Doku'],
            'CHE' => ['Malo Gusto', 'Wesley Fofana', 'Roméo Lavia', 'Reece James'],
            'RMA' => ['Dani Carvajal', 'Aurélien Tchouaméni', 'Eduardo Camavinga'],
            'BAR' => ['Jules Koundé'],
            'INT' => ['Francesco Pio Esposito', 'Marcus Thuram'],
            'MIL' => ['Koni De Winter', 'Alexis Saelemaekers', 'Adrien Rabiot', 'Mike Maignan'],
            'NAP' => ['Kevin De Bruyne', 'Romelu Lukaku'],
            'FCB' => ['Michael Olise'],
            'PSG' => ['Lucas Hernández', 'Warren Zaïre-Emery', 'Lee Kang-in', 'Désiré Doué', 'Lucas Chevalier', 'Ousmane Dembélé'],
        ],
        // Cut-outs never shown (jersey fallback instead). Kit review 2026-11-18: the image
        // shows another club's (or a national team's) kit — usually a photo from before a move.
        // Cut-outs never shown because the image is of the WRONG PERSON (identity problems only).
        // Kit-only rejections were lifted on 2026-11-19: a real photo in another club's or a
        // national kit is shown (players.cutout_source = "other_kit") rather than a placeholder.
        'reject_cutouts' => [],
        // Kit review findings, pinned to the exact image file reviewed: these photos show
        // another club's (or a national) kit, so they are labelled cutout_source = "other_kit"
        // even when TheSportsDB's record names the player's current club.
        'other_kit_images' => [
            'hvd7m11767640858.png' => 'Aleksandar Stankovic: Club Brugge kit', 'd9p3381766153512.png' => 'Benjamin Pavard: Marseille kit',
            'erd1vi1757875565.png' => 'Djed Spence: Tottenham kit', 'xl1q291772105450.png' => 'Noa Lang: Galatasaray kit',
            'p9ftr21776937845.png' => 'Daniele Rugani: Fiorentina kit', 'wiqihs1757163098.png' => 'Douglas Luiz: Nottingham Forest kit',
            'b8hg8o1758816005.png' => 'Zeki Çelik: Roma kit', '47i6wv1758886528.png' => 'Filippo Terracciano: Cremonese kit',
            'wjg2ub1759652150.png' => 'Francesco Camarda: Lecce kit', 'q7d6oi1763154547.png' => 'Arijon Ibrahimović: Heidenheim kit',
            'hqyj8f1766153184.png' => 'Facundo Medina: Marseille kit', 'l18qe01766402363.png' => 'Guéla Doué: Strasbourg kit',
            'ic7kq51781348516.png' => 'Folarin Balogun: USA national-team kit', '0jqkc01767799815.png' => 'Edan Diop: Cercle Brugge kit',
            'jqwk631767799910.png' => 'Flávio Nazinho: Cercle Brugge kit',
        ],
        // Reviewed reassignments, 2026-11-19 (product owner's decision): TheSportsDB-only 2026
        // "moves" that contradict the 2025/26 squads are untrusted — the Liverpool review showed
        // several were wrong — so each player is back at his 2025/26 club ("Name|dob" => code).
        'assign' => [
            'Rodri|1996-06-22' => 'MCI', 'Bernardo Silva|1994-08-10' => 'MCI', 'John Stones|1994-05-28' => 'MCI',
            'Enzo Fernández|2001-01-17' => 'CHE', 'Andrey Santos|2004-05-03' => 'CHE',
            'Denzel Dumfries|1996-04-18' => 'INT',
            'Marcus Rashford|1997-10-31' => 'BAR', 'Ferran Torres|2000-02-29' => 'BAR',
            'Karim Adeyemi|2002-01-18' => 'BVB', 'Alejandro Grimaldo|1995-09-20' => 'B04',
            'Miguel Gutiérrez|2001-07-27' => 'NAP', 'Nicolás González|1998-04-06' => 'ATM',
            'Maghnes Akliouche|2002-02-25' => 'ASM', 'Gonçalo Ramos|2001-06-20' => 'PSG',
        ],
        // Cut-outs accepted after a visual kit check against the player's (reviewed) club.
        'accept_cutouts' => [
            'Rodri' => 'Manchester City kit', 'John Stones' => 'Manchester City kit', 'Ferran Torres' => 'Barcelona kit',
            'Maghnes Akliouche' => 'Monaco kit', 'Gonçalo Ramos' => 'Paris Saint-Germain kit', 'Miguel Gutiérrez' => 'Napoli kit',
        ],
        // Locked squads: the club's confirmed current first team, exactly. Overrides
        // TheSportsDB entirely for that club ("Name|date of birth" => shirt number).
        'lock' => [
            // Confirmed by the product owner, 2026-11-19.
            'LIV' => [
                'Alisson|1992-10-02' => 1, 'Giorgi Mamardashvili|2000-09-29' => 25,
                'Virgil van Dijk|1991-07-08' => 4, 'Ibrahima Konaté|1999-05-25' => 5, 'Joe Gomez|1997-05-23' => 2,
                'Giovanni Leoni|2006-12-21' => 15, 'Andrew Robertson|1994-03-11' => 26, 'Milos Kerkez|2003-11-07' => 6,
                'Kostas Tsimikas|1996-05-12' => 21, 'Conor Bradley|2003-07-09' => 12, 'Jeremie Frimpong|2000-12-10' => 30,
                'Alexis Mac Allister|1998-12-24' => 10, 'Dominik Szoboszlai|2000-10-25' => 8, 'Ryan Gravenberch|2002-05-16' => 38,
                'Curtis Jones|2001-01-30' => 17, 'Wataru Endo|1993-02-09' => 3, 'Florian Wirtz|2003-05-03' => 7,
                'Mohamed Salah|1992-06-15' => 11, 'Cody Gakpo|1999-05-07' => 18, 'Federico Chiesa|1997-10-25' => 14,
                'Alexander Isak|1999-09-21' => 9, 'Hugo Ekitiké|2002-06-20' => 22,
            ],
            // Confirmed by the product owner, 2026-11-19. Numbers are the 2025/26 squad numbers, except
            // Akanji #15 (assigned: #25 is Donnarumma's).
            'MCI' => [
                'Gianluigi Donnarumma|1999-02-25' => 25,
                'James Trafford|2002-10-10' => 1,
                'Stefan Ortega|1992-11-06' => 18,
                'Rúben Dias|1997-05-14' => 3,
                'Joško Gvardiol|2002-01-23' => 24,
                'John Stones|1994-05-28' => 5,
                'Nathan Aké|1995-02-18' => 6,
                'Manuel Akanji|1995-07-19' => 15,
                'Rayan Aït-Nouri|2001-06-06' => 21,
                'Rico Lewis|2004-11-21' => 82,
                'Rodri|1996-06-22' => 16,
                'Tijjani Reijnders|1998-07-26' => 4,
                'Phil Foden|2000-05-28' => 47,
                'Bernardo Silva|1994-08-10' => 20,
                'Mateo Kovačić|1994-05-06' => 8,
                'Rayan Cherki|2003-08-17' => 10,
                'Matheus Nunes|1998-08-27' => 27,
                'Erling Haaland|2000-07-21' => 9,
                'Omar Marmoush|1999-02-07' => 7,
                'Jérémy Doku|2002-05-27' => 11,
                'Savinho|2004-04-10' => 26,
                'Oscar Bobb|2003-07-12' => 52,
            ],
            'ARS' => [
                'David Raya|1995-09-15' => 1,
                'Kepa Arrizabalaga|1994-10-03' => 13,
                'William Saliba|2001-03-24' => 2,
                'Gabriel Magalhães|1997-12-19' => 6,
                'Cristhian Mosquera|2004-06-27' => 3,
                'Piero Hincapié|2002-01-09' => 5,
                'Ben White|1997-11-08' => 4,
                'Jurriën Timber|2001-06-17' => 12,
                'Riccardo Calafiori|2002-05-19' => 33,
                'Myles Lewis-Skelly|2006-09-26' => 49,
                'Declan Rice|1999-01-14' => 41,
                'Martin Ødegaard|1998-12-17' => 8,
                'Martín Zubimendi|1999-02-02' => 36,
                'Mikel Merino|1996-06-22' => 23,
                'Ethan Nwaneri|2007-03-21' => 22,
                'Bukayo Saka|2001-09-05' => 7,
                'Viktor Gyökeres|1998-06-04' => 14,
                'Kai Havertz|1999-06-11' => 29,
                'Gabriel Martinelli|2001-06-18' => 11,
                'Leandro Trossard|1994-12-04' => 19,
                'Noni Madueke|2002-03-10' => 20,
                'Gabriel Jesus|1997-04-03' => 9,
            ],
            'CHE' => [
                'Robert Sánchez|1997-11-18' => 1,
                'Filip Jörgensen|2002-04-16' => 12,
                'Levi Colwill|2003-02-26' => 6,
                'Wesley Fofana|2000-12-17' => 29,
                'Trevoh Chalobah|1999-07-05' => 23,
                'Tosin Adarabioyo|1997-09-24' => 4,
                'Reece James|1999-12-08' => 24,
                'Malo Gusto|2003-05-19' => 27,
                'Marc Cucurella|1998-07-22' => 3,
                'Cole Palmer|2002-05-06' => 10,
                'Moisés Caicedo|2001-11-02' => 25,
                'Enzo Fernández|2001-01-17' => 8,
                'Roméo Lavia|2004-01-06' => 45,
                'Andrey Santos|2004-05-03' => 17,
                'Liam Delap|2003-02-08' => 9,
                'João Pedro|2001-09-26' => 20,
                'Nicolas Jackson|2001-06-20' => 15,
                'Pedro Neto|2000-03-09' => 7,
                'Jamie Gittens|2004-08-08' => 11,
                'Estêvão|2007-04-24' => 41,
                'Christopher Nkunku|1997-11-14' => 18,
            ],
            'MUN' => [
                'André Onana|1996-04-02' => 24,
                'Altay Bayındır|1998-04-14' => 1,
                'Lisandro Martínez|1998-01-18' => 6,
                'Matthijs de Ligt|1999-08-12' => 4,
                'Leny Yoro|2006-11-13' => 15,
                'Noussair Mazraoui|1997-11-14' => 3,
                'Diogo Dalot|1999-03-18' => 20,
                'Luke Shaw|1995-07-12' => 23,
                'Harry Maguire|1993-03-05' => 5,
                'Patrick Dorgu|2004-10-26' => 13,
                'Bruno Fernandes|1994-09-08' => 8,
                'Kobbie Mainoo|2005-04-19' => 37,
                'Manuel Ugarte|2001-04-11' => 25,
                'Casemiro|1992-02-23' => 18,
                'Mason Mount|1999-01-10' => 7,
                'Benjamin Šeško|2003-05-31' => 30,
                'Matheus Cunha|1999-05-27' => 10,
                'Bryan Mbeumo|1999-08-07' => 19,
                'Amad Diallo|2002-07-11' => 16,
                'Rasmus Højlund|2003-02-04' => 9,
                'Joshua Zirkzee|2001-05-22' => 11,
            ],
        ],
        // Real squad numbers (2025/26), winning over TheSportsDB's.
        'shirt_numbers' => [
            'ARS' => [
                'David Raya' => 1, 'William Saliba' => 2, 'Cristhian Mosquera' => 3, 'Ben White' => 4,
                'Piero Hincapié' => 5, 'Gabriel Magalhães' => 6, 'Bukayo Saka' => 7, 'Martin Ødegaard' => 8,
                'Eberechi Eze' => 10, 'Jurriën Timber' => 12, 'Kepa Arrizabalaga' => 13, 'Viktor Gyökeres' => 14,
                'Noni Madueke' => 20, 'Mikel Merino' => 23, 'Kai Havertz' => 29, 'Riccardo Calafiori' => 33,
                'Martín Zubimendi' => 36, 'Declan Rice' => 41, 'Myles Lewis-Skelly' => 49,
            ],
        ],
    ],

    /** Short display names where the default (surname / mononym) is wrong. */
    'display_names' => [
        'Vinícius Júnior' => 'Vinícius Jr.',
        'Gabriel Magalhães' => 'Gabriel',
        'Kylian Mbappé' => 'Mbappé',
        'Trent Alexander-Arnold' => 'Alexander-Arnold',
        'Alexis Mac Allister' => 'Mac Allister',
        'Andrew Robertson' => 'Robertson',
        'Virgil van Dijk' => 'Van Dijk',
        'Frenkie de Jong' => 'De Jong',
        'Kevin De Bruyne' => 'De Bruyne',
        'Matthijs de Ligt' => 'De Ligt',
        'Giovanni Di Lorenzo' => 'Di Lorenzo',
        'Lamine Yamal' => 'Lamine Yamal',
        'Julián Álvarez' => 'Julián Álvarez',
        'Lautaro Martínez' => 'Lautaro',
        'Bruno Fernandes' => 'Bruno Fernandes',
    ],
    /*
     * Reviewed squad facts for players without a full curated row:
     *   name => [shirt number (null = TheSportsDB's), market value €m, contract until (30 June)].
     * Values: approximate late-2025 Transfermarkt-style estimates. Shirt numbers are the
     * 2025/26 numbers; for players TheSportsDB moved in 2026 (marked "new") the number at
     * the new club is unconfirmed and was assigned to avoid clashes.
     */
    'squad_facts' => [
        'ARS' => [
            // Locked squad (2026-11-19): [shirt, value €m, contract] — reviewed estimates.
            'Ethan Nwaneri' => [22, 55, 2030], 'Gabriel Martinelli' => [11, 45, 2027], 'Leandro Trossard' => [19, 15, 2027], 'Gabriel Jesus' => [9, 20, 2027],
        ],
        'ATM' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Nicolás González' => [23, 20, 2029],
            'Cristian Romero' => [null, 45, 2030] /* new */,
            'Dávid Hancko' => [17, 35, 2030], 'Marc Pubill' => [18, 25, 2030], 'Robin Le Normand' => [24, 18, 2029],
            'Ademola Lookman' => [null, 35, 2030] /* new */, 'Alexander Sørloth' => [9, 18, 2027], 'Carlos Martín' => [null, 8, 2029],
            'Giuliano Simeone' => [20, 40, 2030], 'Juan Musso' => [1, 4, 2027], 'Arnau Ortiz' => [null, 3, 2028],
            'Johnny Cardoso' => [5, 35, 2030], 'Pablo Barrios' => [8, 50, 2030], 'Álex Baena' => [10, 50, 2030],
            'Obed Vargas' => [14, 6, 2030] /* new */, 'Rodrigo Mendoza' => [4, 12, 2030],
        ],
        'BAR' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Marcus Rashford' => [14, 40, 2028], 'Ferran Torres' => [7, 30, 2027], 'Fermín' => [16, 50, 2029],
            'Andreas Christensen' => [15, 12, 2027], 'Eric García' => [24, 20, 2030],
            'Anthony Gordon' => [null, 60, 2030] /* new */, 'Gabriel Jesus' => [null, 20, 2028] /* new */, 'Dominik Livaković' => [null, 8, 2028] /* new */,
            'Joan Garcia' => [13, 30, 2031], 'Wojciech Szczesny' => [31, 1, 2027],
            'Brian Fariñas' => [null, 2, 2028],  'Marc Bernal' => [22, 15, 2029],
        ],
        'B04' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Alejandro Grimaldo' => [20, 25, 2027],
            'Arthur' => [null, 6, 2028], 'Edmond Tapsoba' => [12, 35, 2029], 'Facundo Medina' => [15, 15, 2030] /* new */,
            'Guéla Doué' => [null, 10, 2030] /* new */, 'Jarell Quansah' => [4, 35, 2030], 'Loïc Badé' => [5, 30, 2029],
            'Lucas Vazquez' => [21, 3, 2027],  'Christian Kofane' => [35, 20, 2030],
            'Martin Terrier' => [7, 12, 2028], 'Nathan Tella' => [23, 15, 2028], 'Janis Blaswich' => [28, 2, 2027],
            'Mark Flekken' => [1, 8, 2029], 'Aleix García' => [24, 20, 2029], 'Eliesse Ben Seghir' => [11, 30, 2030],
            'Ezequiel Fernández' => [null, 18, 2030], 'Ibrahim Maza' => [30, 25, 2030],
        ],
        'FCB' => [
            'Alphonso Davies' => [19, 40, 2030], 'Hiroki Itō' => [null, 20, 2027], 'Jonathan Tah' => [4, 30, 2029],
            'Konrad Laimer' => [27, 25, 2027], 'Min-jae Kim' => [3, 25, 2028], 'Raphael Guerreiro' => [22, 6, 2027],
            'Sacha Boey' => [23, 10, 2028], 'Luis Díaz' => [14, 70, 2029], 'Serge Gnabry' => [7, 25, 2028],
            'Jonas Urbig' => [40, 15, 2029], 'Sven Ulreich' => [26, 0.5, 2027], 'Aleksandar Pavlović' => [45, 70, 2030],
            'Arijon Ibrahimović' => [38, 8, 2029], 'Bara Sapoko Ndiaye' => [null, 2, 2028], 'Lennart Karl' => [42, 30, 2029],
            'Tom Bischof' => [20, 25, 2029],
        ],
        'BVB' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Karim Adeyemi' => [27, 40, 2027],
            'Daniel Svensson' => [24, 20, 2029], 'Filippo Mané' => [null, 5, 2029], 'Julian Ryerson' => [26, 15, 2028],
            'Niklas Sule' => [25, 8, 2027], 'Ramy Bensebaini' => [5, 6, 2027], 'Waldemar Anton' => [3, 20, 2029],
            'Fábio Silva' => [21, 22, 2030], 'Maximilian Beier' => [14, 30, 2029], 'Alexander Meyer' => [33, 0.6, 2027],
            'Carney Chukwuemeka' => [17, 20, 2030], 'Emre Can' => [23, 3, 2027], 'Enzo dos Santos' => [null, 0.5, 2028],
            'Felix Nmecha' => [8, 40, 2028], 'Giannis Konstantelias' => [null, 8, 2029] /* new */, 'Jobe Bellingham' => [7, 35, 2030],
            'Marcel Sabitzer' => [20, 8, 2027],
        ],
        'CHE' => [
            // Locked squad (2026-11-19): [shirt, value €m, contract] — reviewed estimates.
            'Filip Jörgensen' => [12, 15, 2032], 'Trevoh Chalobah' => [23, 30, 2028], 'Tosin Adarabioyo' => [4, 15, 2028], 'Marc Cucurella' => [3, 35, 2028], 'Liam Delap' => [9, 40, 2031], 'Nicolas Jackson' => [15, 40, 2033], 'Christopher Nkunku' => [18, 25, 2029],
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Enzo Fernández' => [8, 80, 2032], 'Andrey Santos' => [17, 35, 2030],
            'Aarón Anselmino' => [30, 12, 2031], 'Jorrel Hato' => [21, 40, 2032], 'Josh Acheampong' => [34, 15, 2029],
            'Levi Colwill' => [6, 50, 2029], 'Danny Welbeck' => [null, 2, 2027] /* new */, 'Estêvão' => [41, 60, 2033],
            'João Pedro' => [null, 60, 2033], 'Pedro Neto' => [7, 50, 2031], 'Emiliano Martinez' => [null, 10, 2028] /* new */,
            'Gabriel Slonina' => [null, 3, 2028], 'Jamie Gittens' => [11, 45, 2032], 'Jordan Henderson' => [null, 2, 2027] /* new */,
            'Malo Gusto' => [27, 30, 2032], 'Roméo Lavia' => [45, 30, 2029], 'Wesley Fofana' => [29, 25, 2029],
        ],
        'INT' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Denzel Dumfries' => [2, 25, 2028],
            'Benjamin Pavard' => [28, 20, 2028], 'Djed Spence' => [null, 18, 2029] /* new */, 'Federico Dimarco' => [32, 40, 2027],
            'Manuel Akanji' => [25, 25, 2027], 'Yann Bisseck' => [31, 35, 2029],
            'Ange-Yoan Bonny' => [14, 25, 2030], 'Josep Martínez' => [13, 15, 2029], 'Raffaele Di Gennaro' => [12, 0.3, 2027],
            'Aleksandar Stankovic' => [17, 15, 2030], 'Carlos Neves' => [null, 15, 2028], 'Henrikh Mkhitaryan' => [22, 3, 2027],
            'Piotr Zieliński' => [7, 12, 2028], 'Francesco Pio Esposito' => [94, 20, 2030],
        ],
        'JUV' => [
            'Andrea Cambiaso' => [27, 45, 2029], 'Daniele Rugani' => [24, 2, 2027], 'Federico Gatti' => [4, 20, 2029],
            'Juan Cabal' => [32, 15, 2028], 'Lloyd Kelly' => [6, 15, 2029], 'Zeki Çelik' => [2, 5, 2027] /* new */,
            'Arkadiusz Milik' => [null, 2, 2027], 'Jonathan David' => [30, 40, 2030], 'Carlo Pinsoglio' => [null, 0.2, 2027],
            'Mattia Perin' => [1, 2, 2027], 'Douglas Luiz' => [26, 20, 2029], 'Edon Zhegrova' => [11, 30, 2030],
            'Francisco Conceição' => [7, 40, 2030], 'Khéphren Thuram' => [19, 40, 2029], 'Teun Koopmeiners' => [8, 30, 2029],
            'Weston McKennie' => [22, 20, 2028],
        ],
        'LIV' => [
            // Locked squad additions (value €m, contract year: reviewed estimates).
            'Giorgi Mamardashvili' => [25, 30, 2030], 'Ibrahima Konaté' => [5, 50, 2029], 'Andrew Robertson' => [26, 8, 2027],
            'Curtis Jones' => [17, 30, 2027], 'Hugo Ekitiké' => [22, 80, 2031],
            'Ronald Araújo' => [26, 40, 2031] /* new */, 'Conor Bradley' => [12, 30, 2029], 'Giovanni Leoni' => [15, 25, 2030],
            'Jeremie Frimpong' => [30, 45, 2030], 'Joe Gomez' => [2, 12, 2027], 'Kostas Tsimikas' => [21, 8, 2027],
            'Milos Kerkez' => [6, 40, 2030], 'Alexander Isak' => [9, 120, 2031], 'Bradley Barcola' => [29, 70, 2031] /* new */,
            'Cody Gakpo' => [18, 55, 2028], 'Federico Chiesa' => [14, 10, 2028], 'Jayden Danns' => [76, 3, 2028],
            'Rio Ngumoha' => [73, 10, 2029], 'Freddie Woodman' => [28, 0.5, 2027], 'Dominik Szoboszlai' => [8, 80, 2028],
            'Ryan Gravenberch' => [38, 80, 2029], 'Trey Nyoni' => [42, 8, 2028], 'Wataru Endo' => [3, 3, 2027],
        ],
        'MCI' => [
            // Locked squad (2026-11-19): [shirt, value €m, contract] — reviewed estimates.
            'James Trafford' => [1, 25, 2030], 'Stefan Ortega' => [18, 4, 2027], 'Nathan Aké' => [6, 20, 2027], 'Manuel Akanji' => [15, 25, 2027], 'Tijjani Reijnders' => [4, 60, 2030], 'Omar Marmoush' => [7, 55, 2029], 'Savinho' => [26, 50, 2029], 'Oscar Bobb' => [52, 20, 2029],
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Rodri' => [16, 65, 2027], 'Bernardo Silva' => [20, 25, 2027], 'John Stones' => [5, 15, 2027],
            'Joško Gvardiol' => [24, 75, 2028], 'Matheus Nunes' => [27, 35, 2028], "Nico O'Reilly" => [33, 35, 2030],
            'Rayan Aït-Nouri' => [21, 35, 2030], 'Rico Lewis' => [82, 30, 2030], 'Vitor Reis' => [2, 20, 2029],
            'Allan Elias' => [null, 2, 2029], 'Antoine Semenyo' => [null, 60, 2031] /* new */, 'Gianluigi Donnarumma' => [25, 40, 2030],
            'Ayyoub Bouaddi' => [null, 25, 2031] /* new */, 'Elliot Anderson' => [6, 60, 2031] /* new: number assigned, #5 is Stones */, 'Mateo Kovačić' => [8, 12, 2027],
            'Gerónimo Rulli' => [31, 8, 2030] /* new */,
            'Rayan Cherki' => [10, 60, 2030], 'Jérémy Doku' => [11, 60, 2028],
        ],
        'MUN' => [
            // Locked squad (2026-11-19): [shirt, value €m, contract] — reviewed estimates.
            'André Onana' => [24, 15, 2028], 'Altay Bayındır' => [1, 2, 2027], 'Leny Yoro' => [15, 55, 2029], 'Casemiro' => [18, 5, 2027], 'Rasmus Højlund' => [9, 35, 2030],
            'Diego León' => [null, 3, 2030], 'Diogo Dalot' => [20, 25, 2028], 'Harry Maguire' => [5, 6, 2027],
            'Lisandro Martínez' => [6, 30, 2027], 'Luke Shaw' => [23, 5, 2027], 'Matthijs de Ligt' => [4, 30, 2029],
            'Noussair Mazraoui' => [3, 15, 2028], 'Patrick Dorgu' => [13, 30, 2030], 'Amad Diallo' => [16, 45, 2030],
            'Benjamin Šeško' => [30, 70, 2030], 'Bryan Mbeumo' => [19, 60, 2030], 'Joshua Zirkzee' => [11, 18, 2029],
            'Marcus Rashford' => [9, 40, 2028], 'Matheus Cunha' => [10, 60, 2030], 'Dermot Mee' => [null, 0.2, 2027],
            'Senne Lammens' => [31, 20, 2030],  'Bruno Fernandes' => [8, 40, 2027],
            'Carlos Baleba' => [24, 50, 2031] /* new */, 'Kobbie Mainoo' => [37, 45, 2027], 'Manuel Ugarte' => [25, 30, 2029],
            'Mason Mount' => [7, 20, 2028],
        ],
        'MIL' => [
            'Davide Bartesaghi' => [33, 15, 2030], 'Matteo Gabbia' => [46, 12, 2029], 'Pervis Estupiñán' => [2, 15, 2028],
            'Strahinja Pavlović' => [31, 30, 2029], 'Francesco Camarda' => [null, 10, 2027],
            'Lorenzo Torriani' => [96, 2, 2029], 'Pietro Terracciano' => [1, 0.5, 2027], 'Ardon Jashari' => [30, 35, 2030],
            'Cristian Comotto' => [70, 0.5, 2028], 'Filippo Terracciano' => [null, 5, 2028], 'Ruben Loftus-Cheek' => [8, 12, 2027],
            'Samuele Ricci' => [4, 25, 2029], 'Yunus Musah' => [80, 15, 2028], 'Koni De Winter' => [5, 20, 2030],
            'Alexis Saelemaekers' => [56, 20, 2027], 'Adrien Rabiot' => [12, 15, 2027],
        ],
        'ASM' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Maghnes Akliouche' => [11, 45, 2028],
            'Christian Mawissa' => [13, 20, 2029], 'Eric Dier' => [3, 4, 2027], 'Flávio Nazinho' => [null, 2, 2028],
            'Jordan Teze' => [4, 12, 2028], 'Mohammed Salisu' => [22, 15, 2028], 'Vanderson' => [2, 25, 2028],
            'Ansu Fati' => [31, 10, 2028], 'Mika Biereth' => [14, 25, 2029], 'Lukas Hradecky' => [1, 1.5, 2027],
            'Philipp Köhn' => [16, 6, 2028], 'Edan Diop' => [null, 3, 2028], 'Lamine Camara' => [15, 30, 2029],
            'Yann Lienard' => [30, 0.3, 2027], // TheSportsDB spelling
        ],
        'NAP' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Miguel Gutiérrez' => [3, 25, 2029],
            'Alessandro Buongiorno' => [4, 35, 2029], 'Amir Rrahmani' => [13, 8, 2027], 'Leonardo Spinazzola' => [37, 2, 2027],
            'Luca Marianucci' => [6, 5, 2030], 'Mathías Olivera' => [17, 15, 2027], 'Sam Beukema' => [31, 30, 2030],
            'David Neres' => [7, 20, 2028], 'Lorenzo Lucca' => [27, 15, 2030], 'Matteo Politano' => [21, 8, 2027],
            'Noa Lang' => [70, 20, 2030], 'Rasmus Højlund' => [19, 35, 2030], 'Vanja Milinković-Savić' => [32, 10, 2029],
            'Andre-Frank Zambo Anguissa' => [99, 15, 2027], 'Stanislav Lobotka' => [68, 15, 2027],
        ],
        'PSG' => [
            // Restored to his 2025/26 club (roster_overrides.assign).
            'Gonçalo Ramos' => [9, 30, 2028],
            'Ilya Zabarnyi' => [null, 50, 2030], 'Lucas Beraldo' => [35, 25, 2028], 'Nuno Mendes' => [25, 70, 2029],
            'Willian Pacho' => [51, 60, 2029],
            'Matvey Safonov' => [39, 12, 2029], 'Dro Fernández' => [null, 10, 2030] /* new */, 'Fabián Ruiz' => [null, 30, 2029],
            'João Neves' => [null, 100, 2029], 'Senny Mayulu' => [24, 25, 2029], 'Lucas Hernández' => [21, 15, 2028],
            'Warren Zaïre-Emery' => [33, 45, 2029], 'Désiré Doué' => [14, 90, 2030], 'Lee Kang-in' => [19, 25, 2028],
        ],
        'RMA' => [
            'Dean Huijsen' => [24, 70, 2030],  'Ferland Mendy' => [23, 10, 2028],
            'Trent Alexander-Arnold' => [12, 70, 2031], 'Álvaro Carreras' => [18, 50, 2031], 'Éder Militão' => [3, 25, 2028],
            'Brahim Díaz' => [21, 25, 2027], 'Carlos Espí' => [null, 3, 2029], 'Rodrygo' => [11, 60, 2028],
            'Andriy Lunin' => [13, 10, 2030], 'Arda Güler' => [15, 80, 2029],
            'Federico Valverde' => [8, 120, 2029], 'Aurélien Tchouaméni' => [14, 75, 2028], 'Eduardo Camavinga' => [6, 50, 2029],
            'Dani Carvajal' => [2, 4, 2027],
        ],
    ],

    /**
     * Publicly well-known representation for players without a curated row. Everyone
     * else gets FootballSeeder::AGENT_NOT_PUBLIC — never an invented agency.
     */
    'agents' => [
        'Bruno Fernandes' => 'Gestifute (Jorge Mendes)', 'João Neves' => 'Gestifute (Jorge Mendes)',
        'Bernardo Silva' => 'Gestifute (Jorge Mendes)', 'Diogo Dalot' => 'Gestifute (Jorge Mendes)',
        'Nuno Mendes' => 'Gestifute (Jorge Mendes)', 'Gonçalo Ramos' => 'Gestifute (Jorge Mendes)',
        'Pedro Neto' => 'Gestifute (Jorge Mendes)', 'Matheus Nunes' => 'Gestifute (Jorge Mendes)',
        'Viktor Gyökeres' => 'Hasan Çetinkaya',
    ],

    /*
     * Senior international [caps, goals] for players without a curated row — APPROXIMATE
     * (reviewer's knowledge, 2025/26; TheSportsDB has no caps data). 0 = uncapped at senior
     * level. Seeded players get 'intl_caps' in profile_estimates.
     */
    'caps' => [
        'Ethan Nwaneri' => [0, 0], 'Gabriel Martinelli' => [15, 2], 'Leandro Trossard' => [50, 10], 'Gabriel Jesus' => [64, 19], 'Ansu Fati' => [10, 3], 'Christian Mawissa' => [0, 0],
        'Edan Diop' => [0, 0], 'Eric Dier' => [49, 3], 'Flávio Nazinho' => [0, 0], 'Jordan Teze' => [0, 0], 'Lamine Camara' => [20, 1], 'Lukas Hradecky' => [105, 0],
        'Mika Biereth' => [3, 0], 'Mohammed Salisu' => [15, 0], 'Philipp Köhn' => [1, 0], 'Vanderson' => [2, 0], 'Yann Lienard' => [0, 0], 'Maghnes Akliouche' => [2, 0],
        'Alexander Sørloth' => [63, 20], 'Arnau Ortiz' => [0, 0], 'Carlos Martín' => [0, 0], 'Cristian Romero' => [50, 4], 'Dávid Hancko' => [50, 3], 'Giuliano Simeone' => [4, 0],
        'Johnny Cardoso' => [20, 1], 'Juan Musso' => [3, 0], 'Marc Pubill' => [0, 0], 'Obed Vargas' => [15, 0], 'Pablo Barrios' => [2, 0], 'Robin Le Normand' => [15, 0],
        'Rodrigo Mendoza' => [0, 0], 'Álex Baena' => [10, 2], 'Nicolás González' => [38, 5], 'Aleix García' => [2, 0], 'Arthur' => [0, 0], 'Christian Kofane' => [2, 0],
        'Edmond Tapsoba' => [50, 2], 'Eliesse Ben Seghir' => [1, 0], 'Ezequiel Fernández' => [0, 0], 'Facundo Medina' => [8, 0], 'Guéla Doué' => [3, 0], 'Ibrahim Maza' => [3, 0],
        'Janis Blaswich' => [0, 0], 'Jarell Quansah' => [0, 0], 'Loïc Badé' => [0, 0], 'Lucas Vazquez' => [33, 1], 'Mark Flekken' => [8, 0], 'Martin Terrier' => [1, 0],
        'Nathan Tella' => [0, 0], 'Andreas Christensen' => [75, 3], 'Brian Fariñas' => [0, 0], 'Dominik Livaković' => [61, 0], 'Eric García' => [30, 1], 'Fermín' => [9, 3],
        'Joan Garcia' => [1, 0], 'Marc Bernal' => [0, 0], 'Wojciech Szczesny' => [84, 0], 'Marcus Rashford' => [62, 17], 'Ferran Torres' => [51, 22], 'Alexander Meyer' => [0, 0],
        'Carney Chukwuemeka' => [2, 0], 'Daniel Svensson' => [10, 1], 'Emre Can' => [47, 1], 'Enzo dos Santos' => [0, 0], 'Felix Nmecha' => [2, 0], 'Filippo Mané' => [0, 0],
        'Fábio Silva' => [0, 0], 'Giannis Konstantelias' => [8, 1], 'Jobe Bellingham' => [0, 0], 'Julian Ryerson' => [20, 0], 'Marcel Sabitzer' => [85, 17], 'Maximilian Beier' => [5, 0],
        'Niklas Sule' => [49, 1], 'Ramy Bensebaini' => [70, 8], 'Waldemar Anton' => [6, 0], 'Filip Jörgensen' => [0, 0], 'Levi Colwill' => [4, 0], 'Wesley Fofana' => [1, 0],
        'Trevoh Chalobah' => [3, 0], 'Tosin Adarabioyo' => [0, 0], 'Malo Gusto' => [5, 0], 'Marc Cucurella' => [18, 0], 'Roméo Lavia' => [2, 0], 'Andrey Santos' => [1, 0],
        'Liam Delap' => [0, 0], 'João Pedro' => [5, 1], 'Nicolas Jackson' => [22, 5], 'Pedro Neto' => [23, 3], 'Jamie Gittens' => [0, 0], 'Estêvão' => [10, 4],
        'Christopher Nkunku' => [16, 0], 'Aleksandar Pavlović' => [6, 0], 'Alphonso Davies' => [60, 15], 'Arijon Ibrahimović' => [0, 0], 'Bara Sapoko Ndiaye' => [0, 0], 'Hiroki Itō' => [26, 1],
        'Jonas Urbig' => [0, 0], 'Jonathan Tah' => [38, 1], 'Konrad Laimer' => [42, 4], 'Lennart Karl' => [0, 0], 'Luis Díaz' => [68, 19], 'Min-jae Kim' => [70, 4],
        'Raphael Guerreiro' => [64, 4], 'Sacha Boey' => [0, 0], 'Serge Gnabry' => [49, 23], 'Sven Ulreich' => [0, 0], 'Tom Bischof' => [1, 0], 'Francesco Pio Esposito' => [4, 1],
        'Aleksandar Stankovic' => [2, 0], 'Ange-Yoan Bonny' => [0, 0], 'Benjamin Pavard' => [55, 5], 'Carlos Neves' => [1, 0], 'Djed Spence' => [1, 0], 'Federico Dimarco' => [30, 2],
        'Henrikh Mkhitaryan' => [95, 32], 'Josep Martínez' => [0, 0], 'Piotr Zieliński' => [98, 13], 'Raffaele Di Gennaro' => [0, 0], 'Yann Bisseck' => [0, 0], 'Denzel Dumfries' => [70, 10],
        'Andrea Cambiaso' => [18, 0], 'Arkadiusz Milik' => [77, 17], 'Carlo Pinsoglio' => [0, 0], 'Daniele Rugani' => [7, 0], 'Douglas Luiz' => [13, 0], 'Edon Zhegrova' => [40, 6],
        'Federico Gatti' => [7, 1], 'Francisco Conceição' => [10, 1], 'Jonathan David' => [65, 37], 'Juan Cabal' => [2, 0], 'Khéphren Thuram' => [3, 0], 'Lloyd Kelly' => [0, 0],
        'Mattia Perin' => [5, 0], 'Teun Koopmeiners' => [25, 3], 'Weston McKennie' => [58, 11], 'Zeki Çelik' => [50, 2], 'Giorgi Mamardashvili' => [30, 0], 'Ibrahima Konaté' => [25, 0],
        'Joe Gomez' => [11, 0], 'Giovanni Leoni' => [0, 0], 'Andrew Robertson' => [85, 4], 'Milos Kerkez' => [22, 1], 'Kostas Tsimikas' => [35, 0], 'Conor Bradley' => [20, 1],
        'Jeremie Frimpong' => [6, 0], 'Dominik Szoboszlai' => [52, 13], 'Ryan Gravenberch' => [20, 1], 'Curtis Jones' => [3, 1], 'Wataru Endo' => [70, 4], 'Cody Gakpo' => [40, 14],
        'Federico Chiesa' => [51, 7], 'Alexander Isak' => [52, 16], 'Hugo Ekitiké' => [4, 1], 'Gianluigi Donnarumma' => [75, 0], 'James Trafford' => [1, 0], 'Stefan Ortega' => [0, 0],
        'Joško Gvardiol' => [40, 5], 'John Stones' => [84, 3], 'Nathan Aké' => [50, 5], 'Manuel Akanji' => [75, 3], 'Rayan Aït-Nouri' => [20, 2], 'Rico Lewis' => [5, 0],
        'Tijjani Reijnders' => [25, 6], 'Bernardo Silva' => [100, 13], 'Mateo Kovačić' => [105, 5], 'Rayan Cherki' => [3, 1], 'Matheus Nunes' => [20, 1], 'Omar Marmoush' => [40, 8],
        'Jérémy Doku' => [30, 4], 'Savinho' => [6, 0], 'Oscar Bobb' => [1, 0], 'Koni De Winter' => [7, 0], 'Alexis Saelemaekers' => [18, 1], 'Adrien Rabiot' => [55, 5],
        'Ardon Jashari' => [5, 0], 'Cristian Comotto' => [0, 0], 'Davide Bartesaghi' => [0, 0], 'Filippo Terracciano' => [0, 0], 'Francesco Camarda' => [0, 0], 'Lorenzo Torriani' => [0, 0],
        'Matteo Gabbia' => [3, 0], 'Pervis Estupiñán' => [50, 2], 'Pietro Terracciano' => [0, 0], 'Ruben Loftus-Cheek' => [10, 0], 'Samuele Ricci' => [8, 0], 'Strahinja Pavlović' => [35, 3],
        'Yunus Musah' => [45, 0], 'André Onana' => [45, 0], 'Altay Bayındır' => [3, 0], 'Lisandro Martínez' => [22, 0], 'Matthijs de Ligt' => [47, 2], 'Leny Yoro' => [0, 0],
        'Noussair Mazraoui' => [25, 1], 'Diogo Dalot' => [35, 2], 'Luke Shaw' => [34, 3], 'Harry Maguire' => [64, 7], 'Patrick Dorgu' => [10, 1], 'Bruno Fernandes' => [75, 25],
        'Kobbie Mainoo' => [10, 0], 'Manuel Ugarte' => [50, 0], 'Casemiro' => [80, 7], 'Mason Mount' => [36, 5], 'Benjamin Šeško' => [40, 15], 'Matheus Cunha' => [20, 1],
        'Bryan Mbeumo' => [25, 4], 'Amad Diallo' => [10, 1], 'Rasmus Højlund' => [30, 10], 'Joshua Zirkzee' => [4, 0], 'Alessandro Buongiorno' => [12, 0], 'Amir Rrahmani' => [55, 3],
        'Andre-Frank Zambo Anguissa' => [70, 5], 'David Neres' => [11, 1], 'Leonardo Spinazzola' => [26, 0], 'Lorenzo Lucca' => [3, 0], 'Luca Marianucci' => [0, 0], 'Mathías Olivera' => [25, 0],
        'Matteo Politano' => [15, 3], 'Noa Lang' => [15, 3], 'Sam Beukema' => [3, 0], 'Stanislav Lobotka' => [50, 1], 'Vanja Milinković-Savić' => [15, 0], 'Miguel Gutiérrez' => [2, 0],
        'Lucas Hernández' => [40, 0], 'Warren Zaïre-Emery' => [8, 1], 'Lee Kang-in' => [40, 10], 'Désiré Doué' => [6, 1], 'Dro Fernández' => [0, 0], 'Fabián Ruiz' => [40, 6],
        'Ilya Zabarnyi' => [50, 1], 'João Neves' => [15, 1], 'Lucas Beraldo' => [3, 0], 'Matvey Safonov' => [10, 0], 'Nuno Mendes' => [40, 1], 'Senny Mayulu' => [0, 0],
        'Willian Pacho' => [25, 1], 'Gonçalo Ramos' => [15, 6], 'Dani Carvajal' => [50, 1], 'Aurélien Tchouaméni' => [45, 3], 'Eduardo Camavinga' => [25, 1], 'Andriy Lunin' => [15, 0],
        'Arda Güler' => [25, 6], 'Brahim Díaz' => [15, 5], 'Carlos Espí' => [0, 0], 'Dean Huijsen' => [6, 1], 'Federico Valverde' => [70, 7], 'Ferland Mendy' => [10, 0],
        'Rodrygo' => [35, 7], 'Trent Alexander-Arnold' => [34, 4], 'Álvaro Carreras' => [2, 0], 'Éder Militão' => [31, 2],
    ],

    /*
     * Reviewed fees for notable career moves of players without a curated row: "Y-m|to club|fee
     * (€m, or Free/Loan); …". Merged onto TheSportsDB's career path (matched by destination
     * club and year ±1), which supplies the rest of the path but no fees or months.
     */
    'deals' => [
        'Giorgi Mamardashvili' => '2021-07|Valencia|Loan; 2024-08|Liverpool|30', 'Jérémy Doku' => '2020-10|Rennes|26; 2023-08|Manchester City|60', 'Alexander Isak' => '2019-07|Real Sociedad|6.5; 2025-09|Liverpool|145',
        'Hugo Ekitiké' => '2024-07|Eintracht Frankfurt|16.5; 2025-07|Liverpool|95', 'Jeremie Frimpong' => '2021-01|Bayer Leverkusen|11; 2025-07|Liverpool|35', 'Milos Kerkez' => '2023-07|AFC Bournemouth|18; 2025-06|Liverpool|46',
        'Dominik Szoboszlai' => '2021-01|RB Leipzig|20; 2023-07|Liverpool|70', 'Ryan Gravenberch' => '2022-07|Bayern Munich|18.5; 2023-09|Liverpool|40', 'Cody Gakpo' => '2023-01|Liverpool|42',
        'Federico Chiesa' => '2020-10|Juventus|40; 2024-08|Liverpool|12.8', 'Ibrahima Konaté' => '2021-07|Liverpool|40', 'Andrew Robertson' => '2017-07|Liverpool|9',
        'Wataru Endo' => '2023-08|Liverpool|19', 'Giovanni Leoni' => '2025-08|Liverpool|31', 'Joško Gvardiol' => '2023-08|Manchester City|90',
        'John Stones' => '2016-08|Manchester City|55.6', 'Nathan Aké' => '2020-08|Manchester City|45.3', 'Manuel Akanji' => '2022-09|Manchester City|17.5',
        'Rayan Aït-Nouri' => '2025-06|Manchester City|36', 'Tijjani Reijnders' => '2025-06|Manchester City|55', 'Bernardo Silva' => '2017-07|Manchester City|50',
        'Mateo Kovačić' => '2023-06|Manchester City|29', 'Rayan Cherki' => '2025-06|Manchester City|36.5', 'Matheus Nunes' => '2023-08|Manchester City|62',
        'Omar Marmoush' => '2025-01|Manchester City|75', 'Savinho' => '2024-07|Manchester City|25', 'Gianluigi Donnarumma' => '2021-07|Paris Saint-Germain|Free; 2025-09|Manchester City|30',
        'James Trafford' => '2025-07|Manchester City|31', 'Gabriel Martinelli' => '2019-07|Arsenal|7.1', 'Leandro Trossard' => '2023-01|Arsenal|24',
        'Gabriel Jesus' => '2017-01|Manchester City|32; 2022-07|Arsenal|52.2', 'Wesley Fofana' => '2022-08|Chelsea|80.4', 'Malo Gusto' => '2023-01|Chelsea|30',
        'Marc Cucurella' => '2022-08|Chelsea|65.3', 'Roméo Lavia' => '2023-08|Chelsea|62.2', 'Andrey Santos' => '2023-01|Chelsea|20',
        'Liam Delap' => '2024-08|Ipswich Town|20; 2025-06|Chelsea|35', 'João Pedro' => '2023-07|Brighton|35.4; 2025-07|Chelsea|63.7', 'Nicolas Jackson' => '2023-06|Chelsea|37',
        'Pedro Neto' => '2019-08|Wolverhampton Wanderers|17.9; 2024-08|Chelsea|60', 'Jamie Gittens' => '2025-07|Chelsea|56', 'Estêvão' => '2025-07|Chelsea|34',
        'Christopher Nkunku' => '2019-07|RB Leipzig|13; 2023-07|Chelsea|60', 'Filip Jörgensen' => '2024-08|Chelsea|24.5', 'André Onana' => '2022-07|Inter|Free; 2023-07|Manchester United|52.5',
        'Altay Bayındır' => '2023-09|Manchester United|5', 'Lisandro Martínez' => '2019-07|Ajax|7; 2022-07|Manchester United|57.4', 'Matthijs de Ligt' => '2019-07|Juventus|85.5; 2022-07|Bayern Munich|67; 2024-08|Manchester United|45',
        'Leny Yoro' => '2024-07|Manchester United|62', 'Noussair Mazraoui' => '2022-07|Bayern Munich|Free; 2024-08|Manchester United|15', 'Diogo Dalot' => '2018-06|Manchester United|22',
        'Harry Maguire' => '2017-07|Leicester City|20; 2019-08|Manchester United|87', 'Patrick Dorgu' => '2025-02|Manchester United|30', 'Bruno Fernandes' => '2017-07|Sporting CP|8.5; 2020-01|Manchester United|63',
        'Manuel Ugarte' => '2023-07|Paris Saint-Germain|60; 2024-08|Manchester United|50', 'Casemiro' => '2022-08|Manchester United|70.6', 'Mason Mount' => '2023-07|Manchester United|64.2',
        'Benjamin Šeško' => '2023-07|RB Leipzig|24; 2025-08|Manchester United|76.5', 'Matheus Cunha' => '2023-01|Wolverhampton Wanderers|50; 2025-06|Manchester United|74.2', 'Bryan Mbeumo' => '2019-08|Brentford|5.8; 2025-07|Manchester United|75',
        'Amad Diallo' => '2021-01|Manchester United|21.3', 'Rasmus Højlund' => '2022-08|Atalanta|17; 2023-08|Manchester United|73.9', 'Joshua Zirkzee' => '2022-08|Bologna|8.5; 2024-07|Manchester United|42.5',
        'Luke Shaw' => '2014-06|Manchester United|37.5', 'Aurélien Tchouaméni' => '2020-01|Monaco|18; 2022-06|Real Madrid|80', 'Eduardo Camavinga' => '2021-08|Real Madrid|31',
        'Dean Huijsen' => '2024-08|AFC Bournemouth|15.2; 2025-05|Real Madrid|62.5', 'Trent Alexander-Arnold' => '2025-06|Real Madrid|10', 'Álvaro Carreras' => '2025-07|Real Madrid|50',
        'Éder Militão' => '2018-09|Porto|7; 2019-07|Real Madrid|50', 'Ferland Mendy' => '2017-07|Lyon|5; 2019-06|Real Madrid|48', 'Arda Güler' => '2023-07|Real Madrid|20',
        'Rodrygo' => '2019-06|Real Madrid|45', 'Brahim Díaz' => '2019-01|Real Madrid|17', 'Andriy Lunin' => '2018-07|Real Madrid|8.5',
        'Federico Valverde' => '2016-07|Real Madrid|5', 'Joan Garcia' => '2025-07|Barcelona|25', 'Ferran Torres' => '2020-08|Manchester City|23; 2022-01|Barcelona|55',
        'Marcus Rashford' => '2025-07|Barcelona|Loan', 'Andreas Christensen' => '2022-07|Barcelona|Free', 'Eric García' => '2021-06|Barcelona|Free',
        'Wojciech Szczesny' => '2017-07|Juventus|12.2; 2024-10|Barcelona|Free', 'Alexander Sørloth' => '2023-08|Villarreal|10; 2024-08|Atlético Madrid|32', 'Dávid Hancko' => '2022-07|Feyenoord|7; 2025-07|Atlético Madrid|26',
        'Álex Baena' => '2025-07|Atlético Madrid|42', 'Robin Le Normand' => '2024-07|Atlético Madrid|34.5', 'Johnny Cardoso' => '2024-01|Real Betis|6; 2025-07|Atlético Madrid|30',
        'Marc Pubill' => '2024-07|Almería|Free; 2025-07|Atlético Madrid|16', 'Nicolás González' => '2023-07|Fiorentina|23; 2024-08|Juventus|33; 2025-09|Atlético Madrid|Loan', 'Denzel Dumfries' => '2020-08|PSV Eindhoven|12.5; 2021-08|Inter|12.5',
        'Yann Bisseck' => '2023-07|Inter|7', 'Benjamin Pavard' => '2016-07|Bayern Munich|35; 2023-08|Inter|30', 'Ange-Yoan Bonny' => '2025-07|Inter|23',
        'Piotr Zieliński' => '2016-07|Napoli|16; 2024-07|Inter|Free', 'Henrikh Mkhitaryan' => '2022-07|Inter|Free', 'Francisco Conceição' => '2023-07|Porto|5; 2025-07|Juventus|32',
        'Teun Koopmeiners' => '2021-07|Atalanta|12; 2024-08|Juventus|51.4', 'Khéphren Thuram' => '2024-07|Juventus|20', 'Douglas Luiz' => '2019-07|Aston Villa|17.5; 2024-06|Juventus|50',
        'Edon Zhegrova' => '2022-07|Lille|11; 2025-08|Juventus|18.5', 'Lloyd Kelly' => '2025-02|Juventus|16', 'Jonathan David' => '2020-08|Lille|30; 2025-07|Juventus|Free',
        'Juan Cabal' => '2023-07|Juventus|12.8', 'Alessandro Buongiorno' => '2024-07|Napoli|35', 'Sam Beukema' => '2023-07|Bologna|9; 2025-07|Napoli|31',
        'David Neres' => '2022-07|Benfica|15.3; 2024-08|Napoli|28', 'Noa Lang' => '2023-07|PSV Eindhoven|12.5; 2025-07|Napoli|25', 'Vanja Milinković-Savić' => '2025-07|Napoli|21',
        'Stanislav Lobotka' => '2020-01|Napoli|20', 'Amir Rrahmani' => '2020-07|Napoli|14', 'Andre-Frank Zambo Anguissa' => '2018-08|Fulham|25; 2022-07|Napoli|15',
        'Miguel Gutiérrez' => '2023-07|Girona|4; 2025-07|Napoli|18', 'Adrien Rabiot' => '2019-07|Juventus|Free; 2024-09|Marseille|Free; 2025-08|Milan|10', 'Ardon Jashari' => '2025-08|Milan|36',
        'Samuele Ricci' => '2022-01|Torino|9.5; 2025-07|Milan|23', 'Koni De Winter' => '2025-07|Milan|20', 'Pervis Estupiñán' => '2022-08|Brighton|17.8; 2025-07|Milan|17',
        'Strahinja Pavlović' => '2022-07|Red Bull Salzburg|5; 2024-08|Milan|18', 'Ruben Loftus-Cheek' => '2023-07|Milan|16', 'Yunus Musah' => '2023-07|Milan|20',
        'Alexis Saelemaekers' => '2022-07|Milan|3.5', 'Désiré Doué' => '2024-08|Paris Saint-Germain|50', 'João Neves' => '2024-08|Paris Saint-Germain|59.9',
        'Willian Pacho' => '2023-07|Eintracht Frankfurt|13.6; 2024-07|Paris Saint-Germain|40', 'Ilya Zabarnyi' => '2023-01|AFC Bournemouth|23.5; 2025-08|Paris Saint-Germain|63', 'Nuno Mendes' => '2022-06|Paris Saint-Germain|38',
        'Fabián Ruiz' => '2022-08|Paris Saint-Germain|21.5', 'Lucas Hernández' => '2019-07|Bayern Munich|80; 2023-07|Paris Saint-Germain|45', 'Lee Kang-in' => '2023-07|Paris Saint-Germain|22',
        'Matvey Safonov' => '2024-07|Paris Saint-Germain|20', 'Lucas Beraldo' => '2024-01|Paris Saint-Germain|20', 'Gonçalo Ramos' => '2023-08|Paris Saint-Germain|65',
        'Luis Díaz' => '2020-07|Porto|7; 2022-01|Liverpool|45; 2025-07|Bayern Munich|70', 'Min-jae Kim' => '2022-07|Napoli|18; 2023-07|Bayern Munich|50', 'Alphonso Davies' => '2019-01|Bayern Munich|10',
        'Hiroki Itō' => '2024-07|Bayern Munich|23.5', 'Sacha Boey' => '2024-01|Bayern Munich|30', 'Serge Gnabry' => '2017-06|Bayern Munich|8',
        'Jonas Urbig' => '2025-02|Bayern Munich|7', 'Jonathan Tah' => '2015-07|Bayer Leverkusen|7.5; 2025-07|Bayern Munich|Free', 'Konrad Laimer' => '2023-07|Bayern Munich|Free',
        'Felix Nmecha' => '2023-07|Borussia Dortmund|30', 'Jobe Bellingham' => '2025-06|Borussia Dortmund|30.5', 'Waldemar Anton' => '2024-07|Borussia Dortmund|22.5',
        'Maximilian Beier' => '2024-07|Borussia Dortmund|28.5', 'Fábio Silva' => '2020-09|Wolverhampton Wanderers|40; 2025-08|Borussia Dortmund|22.5', 'Marcel Sabitzer' => '2023-07|Borussia Dortmund|19',
        'Julian Ryerson' => '2023-01|Borussia Dortmund|5', 'Niklas Sule' => '2022-07|Borussia Dortmund|Free', 'Jarell Quansah' => '2025-07|Bayer Leverkusen|35',
        'Eliesse Ben Seghir' => '2025-08|Bayer Leverkusen|32', 'Christian Kofane' => '2025-07|Bayer Leverkusen|20', 'Nathan Tella' => '2023-08|Bayer Leverkusen|20',
        'Mark Flekken' => '2023-07|Brentford|13; 2025-07|Bayer Leverkusen|10', 'Loïc Badé' => '2025-08|Bayer Leverkusen|25', 'Aleix García' => '2024-07|Bayer Leverkusen|18',
        'Martin Terrier' => '2024-01|Bayer Leverkusen|20', 'Mika Biereth' => '2025-01|Monaco|16', 'Philipp Köhn' => '2023-07|Monaco|11',
        'Lamine Camara' => '2024-07|Monaco|11', 'Vanderson' => '2022-01|Monaco|11', 'Mohammed Salisu' => '2023-07|Monaco|12',
        'Eric Dier' => '2023-01|Bayern Munich|4; 2025-07|Monaco|Free', 'Lukas Hradecky' => '2025-07|Monaco|Free',
    ],

    /** Academy products with no transfer to record: shown as a single "Academy" entry (no invented date). */
    'academy' => ['Carlos Espí', 'Fermín', 'Carlos Martín', 'Francesco Pio Esposito', 'Warren Zaïre-Emery'],
];
