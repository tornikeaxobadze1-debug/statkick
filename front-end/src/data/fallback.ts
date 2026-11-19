/**
 * Demo data shown only when the Laravel API is unreachable (behind an "offline"
 * banner), so every screen — including the 360° stage — still works.
 * Shapes mirror the real API; profile numbers are illustrative, not real records.
 */
import { hashOf } from '../lib/stats'
import type {
  Country,
  CountryTree,
  Division,
  FormResult,
  ID,
  Player,
  PlayerDetail,
  PlayerProfile,
  PlayerSummary,
  PlayerTotals,
  Team,
  TeamDetail,
  TeamProfile,
  TeamRecord,
  TeamSummary,
} from '../types'

const country = (id: ID, name: string, code: string): Country => ({ id, name, code, flag_url: null })
const ENG = country(901, 'England', 'ENG')
const ESP = country(902, 'Spain', 'ESP')
const ITA = country(903, 'Italy', 'ITA')
const GER = country(904, 'Germany', 'GER')
const FRA = country(905, 'France', 'FRA')
/** Nationality only (Kvaratskhelia); Georgia has no league in the demo data. */
const GEO = country(906, 'Georgia', 'GEO')
/** Nationality only (Lautaro Martínez); Argentina has no league in the demo data. */
const ARG = country(907, 'Argentina', 'ARG')
const POR = country(908, 'Portugal', 'POR')
const NOR = country(909, 'Norway', 'NOR')
const BEL = country(910, 'Belgium', 'BEL')
const TUR = country(911, 'Turkey', 'TUR')
const BRA = country(912, 'Brazil', 'BRA')
const GIN = country(913, 'Guinea', 'GUI')
const DEN = country(914, 'Denmark', 'DEN')
const POL = country(915, 'Poland', 'POL')
const NED = country(916, 'Netherlands', 'NED')
const NATIONS = [ENG, ESP, ITA, GER, FRA, GEO, ARG, POR, NOR, BEL, TUR, BRA, GIN, DEN, POL, NED]
const LEAGUE_COUNTRIES = [ENG, ESP, ITA, GER, FRA]

const division = (id: ID, name: string, c: Country): Division => ({ id, name, country_id: c.id, logo_url: null })
const DIVISIONS = [
  division(911, 'Premier League', ENG),
  division(912, 'La Liga', ESP),
  division(913, 'Serie A', ITA),
  division(914, 'Bundesliga', GER),
  division(915, 'Ligue 1', FRA),
]
const divisionOf = (id: ID) => DIVISIONS.find((d) => d.id === id)!
const countryOf = (id: ID) => NATIONS.find((c) => c.id === id)!

type ClubSeed = [id: ID, name: string, code: string, divisionId: ID, primary: string, secondary: string, profile: TeamProfile]

const CLUBS: ClubSeed[] = [
  [921, 'Arsenal', 'ARS', 911, '#EF0107', '#FFFFFF', { city: 'London', stadium_name: 'Emirates Stadium', stadium_capacity: 60704, coach_name: 'Mikel Arteta', preferred_formation: '4-3-3 Attack', total_market_value: '€1.30bn' }],
  [922, 'Manchester City', 'MCI', 911, '#6CABDD', '#1C2C5B', { city: 'Manchester', stadium_name: 'Etihad Stadium', stadium_capacity: 53400, coach_name: 'Pep Guardiola', preferred_formation: '4-2-3-1', total_market_value: '€1.20bn' }],
  [923, 'Barcelona', 'BAR', 912, '#004D98', '#A50044', {
    short_name: 'Barcelona', city: 'Barcelona', founded_year: 1899, stadium_name: 'Spotify Camp Nou', stadium_capacity: 99354,
    coach_name: 'Hansi Flick', president: 'Joan Laporta', preferred_formation: '4-2-3-1', total_market_value: '€1.10bn',
    trophies: [
      { title: 'La Liga', count: 28 },
      { title: 'Copa del Rey', count: 32 },
      { title: 'UEFA Champions League', count: 5 },
    ],
  }],
  [924, 'Real Madrid', 'RMA', 912, '#FFFFFF', '#FEBE10', { city: 'Madrid', stadium_name: 'Santiago Bernabéu', stadium_capacity: 83186, coach_name: 'Xabi Alonso', preferred_formation: '4-3-3', total_market_value: '€1.40bn' }],
  [925, 'Inter', 'INT', 913, '#0068A8', '#111111', { city: 'Milan', stadium_name: 'San Siro', stadium_capacity: 75817, coach_name: 'Cristian Chivu', preferred_formation: '3-5-2', total_market_value: '€650m' }],
  [926, 'Juventus', 'JUV', 913, '#F2F2F2', '#111111', { city: 'Turin', stadium_name: 'Allianz Stadium', stadium_capacity: 41507, coach_name: 'Igor Tudor', preferred_formation: '3-4-2-1', total_market_value: '€570m' }],
  [927, 'Bayern Munich', 'FCB', 914, '#DC052D', '#0066B2', { city: 'Munich', stadium_name: 'Allianz Arena', stadium_capacity: 75024, coach_name: 'Vincent Kompany', preferred_formation: '4-2-3-1', total_market_value: '€900m' }],
  [928, 'Borussia Dortmund', 'BVB', 914, '#FDE100', '#111111', { city: 'Dortmund', stadium_name: 'Signal Iduna Park', stadium_capacity: 81365, coach_name: 'Niko Kovač', preferred_formation: '3-4-2-1', total_market_value: '€450m' }],
  [929, 'Paris Saint-Germain', 'PSG', 915, '#004170', '#DA291C', { city: 'Paris', stadium_name: 'Parc des Princes', stadium_capacity: 47929, coach_name: 'Luis Enrique', preferred_formation: '4-3-3', total_market_value: '€1.10bn' }],
  [930, 'Marseille', 'OM', 915, '#2FAEE0', '#FFFFFF', { city: 'Marseille', stadium_name: 'Orange Vélodrome', stadium_capacity: 67394, coach_name: 'Roberto De Zerbi', preferred_formation: '4-2-3-1', total_market_value: '€300m' }],
]

const FORMS: FormResult[][] = [
  ['W', 'W', 'D', 'W', 'L'],
  ['W', 'D', 'W', 'W', 'W'],
  ['L', 'W', 'D', 'W', 'D'],
  ['D', 'L', 'W', 'L', 'W'],
]

function recordFor(seed: number): TeamRecord {
  const wins = 4 + (seed % 6)
  const draws = 1 + ((seed >> 3) % 3)
  const losses = 13 - wins - draws
  const gf = wins * 2 + draws
  const ga = losses * 2 + draws
  return { played: 13, wins, draws, losses, goals_for: gf, goals_against: ga, clean_sheets: 2 + (seed % 5), points: wins * 3 + draws }
}

const TEAM_SUMMARIES: TeamSummary[] = CLUBS.map(([id, name, code, divisionId, primary, secondary, profile]) => {
  const h = hashOf(code)
  return {
    id,
    name,
    short_code: code,
    division_id: divisionId,
    country_id: divisionOf(divisionId).country_id,
    logo_url: null,
    short_name: name,
    primary_color: primary,
    secondary_color: secondary,
    founded_year: 1890 + (h % 40),
    average_age: 24 + (h % 40) / 10,
    net_spend: `-€${40 + (h % 200)}.00m`,
    annual_wage_bill: `€${60 + (h % 240)}.00m`,
    last_5_form: FORMS[h % FORMS.length],
    trophies: [
      { title: 'League title', count: 2 + (h % 20) },
      { title: 'Domestic cup', count: 1 + (h % 11) },
    ],
    ...profile,
  }
})
const byCode = (code: string) => TEAM_SUMMARIES.find((t) => t.short_code === code)!

export const FALLBACK_TEAMS: Team[] = TEAM_SUMMARIES.map((t) => ({
  ...t,
  division: divisionOf(t.division_id),
  country: countryOf(t.country_id),
  record: recordFor(hashOf(t.short_code)),
  form: t.last_5_form ?? [],
}))

export const FALLBACK_COUNTRIES: CountryTree[] = [...LEAGUE_COUNTRIES]
  .sort((a, b) => a.name.localeCompare(b.name))
  .map((c) => ({
    ...c,
    divisions: DIVISIONS.filter((d) => d.country_id === c.id).map((d) => ({
      ...d,
      teams: TEAM_SUMMARIES.filter((t) => t.division_id === d.id),
    })),
  }))

/* ---------------- Players ---------------- */

type PlayerSeed = [
  first: string,
  last: string,
  position: string,
  role: string,
  dob: string,
  nation: Country,
  team: string,
  shirt: number,
  value: string,
  rating: number,
  /** Hand-written profile facts that replace the generated demo values. */
  facts?: Partial<PlayerProfile>,
]

const PLAYER_SEEDS: PlayerSeed[] = [
  ['Bukayo', 'Saka', 'Forward', 'Right Winger', '2001-09-05', ENG, 'ARS', 7, '€150.00m', 8.6],
  ['Declan', 'Rice', 'Midfielder', 'Defensive Midfield', '1999-01-14', ENG, 'ARS', 41, '€120.00m', 8.1],
  ['William', 'Saliba', 'Defender', 'Centre-Back', '2001-03-24', FRA, 'ARS', 2, '€90.00m', 7.9],
  ['Erling', 'Haaland', 'Forward', 'Centre-Forward', '2000-07-21', NOR, 'MCI', 9, '€180.00m', 8.7],
  ['', 'Rodri', 'Midfielder', 'Defensive Midfield', '1996-06-22', ESP, 'MCI', 16, '€90.00m', 8.3],
  // FC Barcelona — full squad with detailed 360° profiles.
  ['Lamine', 'Yamal', 'Forward', 'Right Winger', '2007-07-13', ESP, 'BAR', 10, '€200.00m', 8.8, {
    preferred_foot: 'Left', height_cm: 180, playing_style: 'Inside Forward', secondary_positions: ['Left Winger', 'Attacking Midfield'],
    contract_expires: '2031-06-30', release_clause: '€1.00bn', agent_name: 'Gestifute',
    trophies: [{ title: 'La Liga', count: 1 }, { title: 'Copa del Rey', count: 1 }, { title: 'UEFA European Championship', count: 1 }],
    transfer_history: [{ date: '2023-07', from: 'Barcelona Atlètic', to: 'Barcelona', fee: 'Academy' }],
    transfer_rumors: [],
  }],
  ['', 'Pedri', 'Midfielder', 'Central Midfield', '2002-11-25', ESP, 'BAR', 8, '€140.00m', 8.2, {
    preferred_foot: 'Right', height_cm: 174, playing_style: 'Deep-Lying Playmaker', secondary_positions: ['Attacking Midfield', 'Left Midfield'],
    contract_expires: '2030-06-30', release_clause: '€1.00bn', agent_name: null,
    trophies: [{ title: 'La Liga', count: 2 }, { title: 'Copa del Rey', count: 2 }, { title: 'UEFA European Championship', count: 1 }],
    transfer_history: [{ date: '2020-09', from: 'UD Las Palmas', to: 'Barcelona', fee: '€5.00m' }],
    transfer_rumors: [],
  }],
  ['', 'Raphinha', 'Forward', 'Left Winger', '1996-12-14', BRA, 'BAR', 11, '€80.00m', 8.5, {
    preferred_foot: 'Left', height_cm: 176, playing_style: 'Wide Playmaker', secondary_positions: ['Right Winger', 'Attacking Midfield'],
    contract_expires: '2028-06-30', release_clause: null, agent_name: null,
    trophies: [{ title: 'La Liga', count: 2 }, { title: 'Copa del Rey', count: 1 }],
    transfer_history: [
      { date: '2020-10', from: 'Stade Rennais', to: 'Leeds United', fee: '€21.00m' },
      { date: '2022-07', from: 'Leeds United', to: 'Barcelona', fee: '€58.00m' },
    ],
    transfer_rumors: [],
  }],
  ['Robert', 'Lewandowski', 'Forward', 'Centre-Forward', '1988-08-21', POL, 'BAR', 9, '€12.00m', 8.1, {
    preferred_foot: 'Right', height_cm: 185, playing_style: 'Complete Forward', secondary_positions: ['Second Striker'],
    contract_expires: '2026-06-30', release_clause: null, agent_name: 'Pini Zahavi', intl_caps: 158, intl_goals: 85,
    trophies: [{ title: 'Bundesliga', count: 10 }, { title: 'UEFA Champions League', count: 1 }, { title: 'La Liga', count: 2 }],
    transfer_history: [
      { date: '2010-07', from: 'Lech Poznań', to: 'Borussia Dortmund', fee: '€4.75m' },
      { date: '2014-07', from: 'Borussia Dortmund', to: 'Bayern Munich', fee: 'Free' },
      { date: '2022-07', from: 'Bayern Munich', to: 'Barcelona', fee: '€45.00m' },
    ],
    transfer_rumors: [],
  }],
  ['', 'Gavi', 'Midfielder', 'Central Midfield', '2004-08-05', ESP, 'BAR', 6, '€80.00m', 7.9, {
    preferred_foot: 'Right', height_cm: 173, playing_style: 'Box-to-Box Midfielder', secondary_positions: ['Left Midfield', 'Attacking Midfield'],
    contract_expires: '2030-06-30', release_clause: '€1.00bn', agent_name: null,
    trophies: [{ title: 'La Liga', count: 2 }, { title: 'UEFA Nations League', count: 1 }],
    transfer_history: [{ date: '2021-08', from: 'Barcelona Atlètic', to: 'Barcelona', fee: 'Academy' }],
    transfer_rumors: [],
  }],
  ['Pau', 'Cubarsí', 'Defender', 'Centre-Back', '2007-01-22', ESP, 'BAR', 5, '€80.00m', 8.0, {
    preferred_foot: 'Right', height_cm: 184, playing_style: 'Ball-Playing Defender', secondary_positions: [],
    contract_expires: '2029-06-30', release_clause: '€500.00m', agent_name: null,
    trophies: [{ title: 'La Liga', count: 1 }, { title: 'Copa del Rey', count: 1 }],
    transfer_history: [{ date: '2024-01', from: 'Barcelona Atlètic', to: 'Barcelona', fee: 'Academy' }],
    transfer_rumors: [],
  }],
  ['Frenkie', 'de Jong', 'Midfielder', 'Central Midfield', '1997-05-12', NED, 'BAR', 21, '€45.00m', 7.9, {
    preferred_foot: 'Right', height_cm: 181, playing_style: 'Deep-Lying Playmaker', secondary_positions: ['Defensive Midfield'],
    contract_expires: '2029-06-30', release_clause: null, agent_name: null,
    trophies: [{ title: 'Eredivisie', count: 1 }, { title: 'La Liga', count: 2 }, { title: 'Copa del Rey', count: 2 }],
    transfer_history: [{ date: '2019-07', from: 'Ajax', to: 'Barcelona', fee: '€75.00m' }],
    transfer_rumors: [],
  }],
  ['Jules', 'Koundé', 'Defender', 'Right-Back', '1998-11-12', FRA, 'BAR', 23, '€65.00m', 7.8, {
    preferred_foot: 'Right', height_cm: 180, playing_style: 'Overlapping Full-Back', secondary_positions: ['Centre-Back'],
    contract_expires: '2030-06-30', release_clause: null, agent_name: null,
    trophies: [{ title: 'UEFA Europa League', count: 1 }, { title: 'La Liga', count: 2 }, { title: 'UEFA Nations League', count: 1 }],
    transfer_history: [
      { date: '2019-07', from: 'Girondins Bordeaux', to: 'Sevilla', fee: '€25.00m' },
      { date: '2022-07', from: 'Sevilla', to: 'Barcelona', fee: '€50.00m' },
    ],
    transfer_rumors: [],
  }],
  ['Kylian', 'Mbappé', 'Forward', 'Centre-Forward', '1998-12-20', FRA, 'RMA', 10, '€180.00m', 8.9],
  ['Thibaut', 'Courtois', 'Goalkeeper', 'Goalkeeper', '1992-05-11', BEL, 'RMA', 1, '€25.00m', 7.6],
  ['Lautaro', 'Martínez', 'Forward', 'Centre-Forward', '1997-08-22', ARG, 'INT', 10, '€95.00m', 8.1],
  ['Kenan', 'Yıldız', 'Forward', 'Left Winger', '2005-05-04', TUR, 'JUV', 10, '€75.00m', 7.6],
  ['Harry', 'Kane', 'Forward', 'Centre-Forward', '1993-07-28', ENG, 'FCB', 9, '€75.00m', 8.6],
  ['Jamal', 'Musiala', 'Midfielder', 'Attacking Midfield', '2003-02-26', GER, 'FCB', 10, '€130.00m', 8.4],
  ['Gregor', 'Kobel', 'Goalkeeper', 'Goalkeeper', '1997-12-06', GER, 'BVB', 1, '€40.00m', 7.4],
  ['Ousmane', 'Dembélé', 'Forward', 'Right Winger', '1997-05-15', FRA, 'PSG', 10, '€100.00m', 8.7],
  ['Khvicha', 'Kvaratskhelia', 'Forward', 'Left Winger', '2001-02-12', GEO, 'PSG', 7, '€90.00m', 8.4],
  ['Pierre-Emerick', 'Aubameyang', 'Forward', 'Centre-Forward', '1989-06-18', FRA, 'OM', 97, '€3.00m', 7.3],
  // Second player for every club that had one, so each demo squad is browsable.
  ['Nicolò', 'Barella', 'Midfielder', 'Central Midfield', '1997-02-07', ITA, 'INT', 23, '€70.00m', 8.0],
  ['Gleison', 'Bremer', 'Defender', 'Centre-Back', '1997-03-18', BRA, 'JUV', 3, '€45.00m', 7.5],
  ['Serhou', 'Guirassy', 'Forward', 'Centre-Forward', '1996-03-12', GIN, 'BVB', 9, '€40.00m', 7.9],
  ['Pierre-Emile', 'Højbjerg', 'Midfielder', 'Defensive Midfield', '1995-08-05', DEN, 'OM', 23, '€15.00m', 7.2],
]

const HEAT: Record<string, PlayerProfile['heatmap_zones']> = {
  Goalkeeper: { def_centre: 90, def_left: 4, def_right: 4, mid_centre: 2 },
  Defender: { def_left: 12, def_centre: 40, def_right: 14, mid_left: 6, mid_centre: 18, mid_right: 6, att_centre: 4 },
  Midfielder: { def_centre: 8, mid_left: 14, mid_centre: 38, mid_right: 14, att_left: 6, att_centre: 14, att_right: 6 },
  Forward: { mid_centre: 8, mid_right: 10, att_left: 14, att_centre: 34, att_right: 30, mid_left: 4 },
}

let nextId = 950
function makePlayer([first, last, position, role, dob, nation, teamCode, shirt, value, rating, facts]: PlayerSeed): Player {
  const id = nextId++
  const h = hashOf(id)
  const team = byCode(teamCode)
  const apps = 11 + (h % 4)
  const attacker = position === 'Forward' ? 1 : position === 'Midfielder' ? 0.5 : position === 'Defender' ? 0.12 : 0
  const goals = Math.round(apps * attacker * (0.6 + (h % 5) / 10))
  const assists = Math.round(apps * attacker * 0.45)
  const minutes = apps * (78 + (h % 12))
  const totals: PlayerTotals = { appearances: apps, goals, assists, minutes_played: minutes, yellow_cards: h % 4, red_cards: 0, avg_rating: rating }
  return {
    id,
    first_name: first,
    last_name: last,
    position,
    date_of_birth: `${dob}T00:00:00.000000Z`,
    country_id: nation.id,
    photo_url: null,
    country: nation,
    shirt_number: shirt,
    primary_position: role,
    secondary_positions: position === 'Goalkeeper' ? [] : [position === 'Forward' ? 'Second Striker' : 'Central Midfield'],
    age: new Date().getFullYear() - Number(dob.slice(0, 4)),
    height_cm: 170 + (h % 22),
    nationality: nation.name,
    preferred_foot: h % 3 === 0 ? 'Left' : 'Right',
    market_value: value,
    highest_market_value: value,
    contract_expires: `${2027 + (h % 4)}-06-30`,
    weekly_salary: `€${80 + (h % 300)}k`,
    annual_salary: `€${(((80 + (h % 300)) * 52) / 1000).toFixed(2)}m`,
    release_clause: null,
    agent_name: 'Demo agency',
    playing_style: position === 'Goalkeeper' ? 'Sweeper Keeper' : position === 'Defender' ? 'Ball-Playing Defender' : position === 'Midfielder' ? 'Deep-Lying Playmaker' : 'Inside Forward',
    appearances: apps,
    goals,
    assists,
    minutes_played: minutes,
    avg_rating: rating,
    xg: Number((goals * 0.9 + (h % 10) / 10).toFixed(2)),
    xa: Number((assists * 0.9 + (h % 7) / 10).toFixed(2)),
    key_passes: 5 + (h % 40),
    pass_accuracy_pct: 74 + (h % 20),
    progressive_carries: 10 + (h % 80),
    dribble_success_pct: 35 + (h % 30),
    tackles_interceptions: 5 + (h % 45),
    aerial_duels_won_pct: 30 + (h % 40),
    heatmap_zones: HEAT[position],
    injury_status: h % 7 === 0 ? 'Minor knock' : 'Fit',
    injury_history: [{ date: '2024-10', injury: 'Muscle strain', days_out: 10 + (h % 40) }],
    national_team: nation.name,
    intl_caps: Math.max(0, 70 - (h % 60)),
    intl_goals: Math.round((70 - (h % 60)) * attacker * 0.3),
    trophies: [{ title: 'League title', count: 1 + (h % 3) }],
    transfer_history: [{ date: '2019-07', from: 'Academy', to: team.name, fee: 'Academy' }],
    transfer_rumors: [
      { club: 'Demo FC', probability_pct: 10 + (h % 30) },
      { club: 'Example United', probability_pct: 5 + (h % 15) },
    ],
    cutout_image_url: null,
    ...facts,
    current_team: { ...team, pivot: { id, team_id: team.id, player_id: id, season: '2026/27', shirt_number: shirt, is_current: true } },
    totals,
  }
}

export const FALLBACK_PLAYERS: Player[] = PLAYER_SEEDS.map(makePlayer)

// Roster counts, mirroring the API's players_count: always the length of the listed squad.
for (const t of [...TEAM_SUMMARIES, ...FALLBACK_TEAMS]) {
  t.players_count = FALLBACK_PLAYERS.filter((p) => p.current_team?.id === t.id).length
}

/* ---------------- Filters mirroring the API ---------------- */

export const fallbackTeams = (divisionId: ID | null) =>
  divisionId === null ? FALLBACK_TEAMS : FALLBACK_TEAMS.filter((t) => t.division_id === divisionId)

export const fallbackPlayers = (teamId: ID | null) =>
  teamId === null ? FALLBACK_PLAYERS : FALLBACK_PLAYERS.filter((p) => p.current_team?.id === teamId)

/** The player without the list-only fields (current_team, totals). */
function summaryOf(p: Player): PlayerSummary & { country: Country } {
  const copy: Partial<Player> = { ...p }
  delete copy.current_team
  delete copy.totals
  return copy as PlayerSummary & { country: Country }
}

export function fallbackTeam(id: ID | null): TeamDetail | null {
  const t = TEAM_SUMMARIES.find((x) => x.id === id)
  if (!t) return null
  return {
    ...t,
    division: divisionOf(t.division_id),
    country: countryOf(t.country_id),
    current_players: fallbackPlayers(t.id).map((p) => ({ ...summaryOf(p), pivot: p.current_team!.pivot })),
    matches: [],
  }
}

export function fallbackPlayerDetail(id: ID | null): PlayerDetail | null {
  const p = FALLBACK_PLAYERS.find((x) => x.id === id)
  if (!p) return null
  const current = p.current_team
  return {
    ...summaryOf(p),
    current_team: current ? { ...current, division: divisionOf(current.division_id) } : null,
    career: current ? [{ ...current.pivot, team: byCode(current.short_code) }] : [],
    match_stats: [],
  }
}
