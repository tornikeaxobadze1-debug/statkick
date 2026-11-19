/**
 * StatKick domain models — these mirror the Laravel API's JSON exactly
 * (raw Eloquent serialization, snake_case, no `{ data }` wrapper on single resources).
 */

export type ID = number

/* ---------------- Reference data ---------------- */

export interface Country {
  id: ID
  name: string
  /** ISO 3166-1 alpha-3, e.g. "ESP" */
  code: string
  flag_url: string | null
}

export interface Division {
  id: ID
  name: string
  country_id: ID
  logo_url: string | null
  country?: Country
  teams_count?: number
}

/** GET /api/countries item: the navigation tree Country → League → Team. */
export interface CountryTree extends Country {
  divisions: (Division & { teams: TeamSummary[] })[]
}

/** GET /api/divisions/{id} */
export interface DivisionDetail extends Division {
  country: Country
  teams: TeamSummary[]
  upcoming_matches: Match[]
  past_matches: Match[]
}

/* ---------------- Teams ---------------- */

export interface TitleCount {
  title: string
  count: number
}

/**
 * Transfermarkt-style club profile. Money values are display strings ("€1.30bn").
 * Optional so nested/legacy payloads without them still type-check.
 */
export interface TeamProfile {
  short_name?: string | null
  primary_color?: string | null
  secondary_color?: string | null
  founded_year?: number | null
  city?: string | null
  stadium_name?: string | null
  stadium_capacity?: number | null
  coach_name?: string | null
  president?: string | null
  kit_sponsor?: string | null
  total_market_value?: string | null
  /** Static, editorial squad size; not shown, as it can disagree with the listed roster. */
  squad_size?: number | null
  /** Number of players in the current listed roster (= GET /players?team_id=). */
  players_count?: number | null
  average_age?: number | null
  foreigners_count?: number | null
  foreigners_pct?: number | null
  preferred_formation?: string | null
  transfer_income?: string | null
  transfer_expenditure?: string | null
  net_spend?: string | null
  annual_wage_bill?: string | null
  last_5_form?: FormResult[] | null
  clean_sheets?: number | null
  trophies?: TitleCount[] | null
}

export interface TeamSummary extends TeamProfile {
  id: ID
  name: string
  short_code: string
  division_id: ID
  country_id: ID
  logo_url: string | null
}

export type FormResult = 'W' | 'D' | 'L'

/** Standings line computed server-side from FINISHED matches. */
export interface TeamRecord {
  played: number
  wins: number
  draws: number
  losses: number
  goals_for: number
  goals_against: number
  clean_sheets: number
  points: number
}

/** GET /api/teams item */
export interface Team extends TeamSummary {
  division: Division
  country: Country
  record: TeamRecord
  /** Last five results, newest first. */
  form: FormResult[]
}

/** GET /api/teams/{id} */
export interface TeamDetail extends TeamSummary {
  division: Division
  country: Country
  current_players: (PlayerSummary & { pivot: SquadPivot; country: Country })[]
  matches: Match[]
}

/* ---------------- Players ---------------- */

export type PlayerPosition = 'Goalkeeper' | 'Defender' | 'Midfielder' | 'Forward'

export type HeatmapZone =
  | 'def_left' | 'def_centre' | 'def_right'
  | 'mid_left' | 'mid_centre' | 'mid_right'
  | 'att_left' | 'att_centre' | 'att_right'

export interface InjuryRecord {
  date: string
  injury: string
  days_out: number
}

export interface TransferRecord {
  date: string
  from: string
  to: string
  fee: string
}

export interface TransferRumor {
  club: string
  probability_pct: number
}

/** Transfermarkt / Opta-style player profile. Optional for nested/legacy payloads. */
export interface PlayerProfile {
  shirt_number?: number | null
  primary_position?: string | null
  secondary_positions?: string[] | null
  age?: number | null
  height_cm?: number | null
  nationality?: string | null
  preferred_foot?: string | null
  market_value?: string | null
  highest_market_value?: string | null
  highest_market_value_date?: string | null
  contract_expires?: string | null
  weekly_salary?: string | null
  annual_salary?: string | null
  release_clause?: string | null
  agent_name?: string | null
  boot_sponsor?: string | null
  playing_style?: string | null
  appearances?: number
  goals?: number
  assists?: number
  minutes_played?: number
  yellow_cards?: number
  red_cards?: number
  avg_rating?: number | null
  xg?: number | null
  xa?: number | null
  key_passes?: number | null
  pass_accuracy_pct?: number | null
  progressive_carries?: number | null
  dribble_success_pct?: number | null
  tackles_interceptions?: number | null
  aerial_duels_won_pct?: number | null
  heatmap_zones?: Partial<Record<HeatmapZone, number>> | null
  injury_status?: string | null
  injury_history?: InjuryRecord[] | null
  national_team?: string | null
  intl_caps?: number
  intl_goals?: number
  trophies?: TitleCount[] | null
  transfer_history?: TransferRecord[] | null
  transfer_rumors?: TransferRumor[] | null
  cutout_image_url?: string | null
  /** Fields whose values are model estimates rather than verified figures. */
  profile_estimates?: ProfileEstimate[] | null
}

/** A field name, or an object naming the field and how it was estimated. */
export type ProfileEstimate =
  | string
  | { field?: string; key?: string; note?: string | null; basis?: string | null; method?: string | null }

export interface PlayerSummary extends PlayerProfile {
  id: ID
  first_name: string
  last_name: string
  position: PlayerPosition | string
  /** ISO date-time string */
  date_of_birth: string | null
  country_id: ID
  photo_url: string | null
}

export interface SquadPivot {
  id: ID
  team_id: ID
  player_id: ID
  season: string
  shirt_number: number | null
  is_current: boolean
}

/** Career totals aggregated from player_stats. */
export interface PlayerTotals {
  appearances: number
  goals: number
  assists: number
  minutes_played: number
  yellow_cards: number
  red_cards: number
  /** Average match rating out of 10, null when the player has no appearances. */
  avg_rating: number | null
}

/** GET /api/players item */
export interface Player extends PlayerSummary {
  country: Country
  current_team: (TeamSummary & { pivot: SquadPivot }) | null
  totals: PlayerTotals
}

export interface PlayerMatchStat {
  id: ID
  player_id: ID
  match_id: ID
  goals: number
  assists: number
  yellow_cards: number
  red_cards: number
  minutes_played: number
  rating: number | null
}

/** GET /api/players/{id} */
export interface PlayerDetail extends PlayerSummary {
  country: Country
  current_team: (TeamSummary & { pivot: SquadPivot; division: Division }) | null
  career: (SquadPivot & { team: TeamSummary })[]
  match_stats: (PlayerMatchStat & { match: Match })[]
}

/* ---------------- Matches ---------------- */

export type MatchStatus = 'SCHEDULED' | 'LIVE' | 'FINISHED' | 'POSTPONED' | 'CANCELLED'

export interface Match {
  id: ID
  division_id: ID
  home_team_id: ID
  away_team_id: ID
  home_score: number
  away_score: number
  status: MatchStatus
  /** ISO date-time string */
  match_date: string
  division: Division
  home_team: TeamSummary
  away_team: TeamSummary
}

/** GET /api/matches/{id} */
export interface MatchDetail extends Match {
  player_stats: (PlayerMatchStat & { player: PlayerSummary & { country: Country } })[]
}

/* ---------------- Users & auth ---------------- */

export interface User {
  id: ID
  name: string
  email: string
  email_verified_at: string | null
  newsletter_subscribed: boolean
  preferred_language: string
  created_at: string
  updated_at: string
  favorite_players_count?: number
  favorite_teams_count?: number
}

export interface Favorites {
  players: PlayerSummary[]
  teams: TeamSummary[]
}

export interface FavoriteToggle {
  player_id?: ID
  team_id?: ID
  is_favorite: boolean
}

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export interface RegisterPayload {
  name: string
  email: string
  password: string
  /** Laravel's `confirmed` rule: must equal `password`. */
  password_confirmation: string
  device_name?: string
}

export interface AuthResponse {
  user: User
  token: string
  token_type: 'Bearer'
}

/* ---------------- Envelopes ---------------- */

/** Laravel LengthAwarePaginator JSON. */
export interface Paginated<T> {
  current_page: number
  data: T[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

/** Laravel 422 validation error payload. */
export interface ValidationErrorBody {
  message: string
  errors: Record<string, string[]>
}

/** What the 360° stage needs: a profile plus nationality and current club (list or detail payload). */
/** A saved club: from the clubs list or GET /teams/{id} (both carry league and country). */
export type FavoriteTeam = TeamSummary & { division: Division; country: Country }

export type StagePlayer = PlayerSummary & {
  country: Country
  current_team: (TeamSummary & { pivot: SquadPivot }) | null
}
