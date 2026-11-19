/**
 * Small derivations from API data used across the UI.
 * Everything here is computed from real fields — nothing is invented.
 */
import type { Player, Team, TeamProfile, TeamRecord, TeamSummary } from '../types'

/** Full display name; players known by one name (e.g. "Pedri") have an empty first name. */
export const playerName = (p: { first_name: string; last_name: string }) => `${p.first_name} ${p.last_name}`.trim()

const POSITION_ABBR: Record<string, string> = { Goalkeeper: 'GK', Defender: 'DEF', Midfielder: 'MID', Forward: 'FWD' }
export const positionAbbr = (position: string) => POSITION_ABBR[position] ?? position.slice(0, 3).toUpperCase()

const POSITION_ORDER: Record<string, number> = { Goalkeeper: 0, Defender: 1, Midfielder: 2, Forward: 3 }
export const positionRank = (position: string) => POSITION_ORDER[position] ?? 4

export function ageFrom(dateOfBirth: string | null | undefined, now = new Date()): number | null {
  if (!dateOfBirth) return null
  const dob = new Date(dateOfBirth)
  if (Number.isNaN(dob.getTime())) return null
  let age = now.getFullYear() - dob.getFullYear()
  const m = now.getMonth() - dob.getMonth()
  if (m < 0 || (m === 0 && now.getDate() < dob.getDate())) age--
  return age
}

export const winRate = (r: TeamRecord) => (r.played ? Math.round((r.wins / r.played) * 100) : 0)

/** Standings order: points, goal difference, goals scored, then name. */
export const standingsOrder = (a: Team, b: Team) => {
  const gd = (t: Team) => t.record.goals_for - t.record.goals_against
  return b.record.points - a.record.points || gd(b) - gd(a) || b.record.goals_for - a.record.goals_for || a.name.localeCompare(b.name)
}

/** "2027-06-30" → "Jun 2027" */
export function formatMonthYear(iso: string | null | undefined): string | null {
  if (!iso) return null
  const d = new Date(iso.length === 7 ? `${iso}-01` : iso)
  if (Number.isNaN(d.getTime())) return iso
  return new Intl.DateTimeFormat('en-GB', { month: 'short', year: 'numeric' }).format(d)
}

export const formatNumber = (n: number | null | undefined) => (n == null ? null : new Intl.NumberFormat('en-GB').format(n))

/* ---------------- Formations ---------------- */

/** "4-3-3 Attack" → [4, 3, 3] (outfield lines, back to front). Null when unparseable. */
export function formationLines(formation: string | null | undefined): number[] | null {
  const m = formation?.match(/\d(?:-\d)+/)
  if (!m) return null
  const lines = m[0].split('-').map(Number)
  return lines.reduce((a, b) => a + b, 0) === 10 ? lines : null
}

/* ---------------- Colours ---------------- */

export interface TeamColors {
  primary: string
  secondary: string
}

/** Fallback kit colours for clubs whose API row has none. */
const KNOWN_COLORS: Record<string, TeamColors> = {
  BAR: { primary: '#004d98', secondary: '#a50044' },
  RMA: { primary: '#f2f2f2', secondary: '#febe10' },
  ARS: { primary: '#ef0107', secondary: '#ffffff' },
  MCI: { primary: '#6cabdd', secondary: '#1c2c5b' },
  INT: { primary: '#0068a8', secondary: '#111111' },
  JUV: { primary: '#f2f2f2', secondary: '#111111' },
}

export function teamColors(team: (Pick<TeamSummary, 'short_code'> & TeamProfile) | null | undefined): TeamColors {
  if (!team) return { primary: '#2b2b2b', secondary: '#9a9a9a' }
  if (team.primary_color) return { primary: team.primary_color, secondary: team.secondary_color ?? readableOn(team.primary_color) }
  const known = KNOWN_COLORS[team.short_code]
  if (known) return known
  const h = hashOf(team.short_code) % 360
  return { primary: `hsl(${h} 55% 40%)`, secondary: `hsl(${(h + 40) % 360} 45% 85%)` }
}

function rgbOf(hex: string): [number, number, number] | null {
  const m = hex.trim().match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i)
  if (!m) return null
  const v = m[1].length === 3 ? [...m[1]].map((c) => c + c).join('') : m[1]
  return [0, 2, 4].map((i) => parseInt(v.slice(i, i + 2), 16)) as [number, number, number]
}

/** WCAG relative luminance (0 = black, 1 = white). */
export function luminance(hex: string): number {
  const rgb = rgbOf(hex)
  if (!rgb) return 0.2
  const [r, g, b] = rgb.map((c) => {
    const s = c / 255
    return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4
  })
  return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

/** Black or white — whichever reads better on `bg`. */
export const readableOn = (bg: string) => (luminance(bg) > 0.35 ? '#111111' : '#ffffff')

/** Mix a hex colour towards black (amount > 0) or white (amount < 0). */
export function shade(hex: string, amount: number): string {
  const rgb = rgbOf(hex)
  if (!rgb) return hex
  const target = amount > 0 ? 0 : 255
  const t = Math.abs(amount)
  return `#${rgb.map((c) => Math.round(c + (target - c) * t).toString(16).padStart(2, '0')).join('')}`
}

/**
 * The club's accent for marks on the dark stage: the primary colour, or the secondary
 * when the primary is near-black, lightened until it reads against obsidian.
 */
export function accentOnDark(team: Parameters<typeof teamColors>[0]): string {
  const { primary, secondary } = teamColors(team)
  const usable = (c: string) => luminance(c) >= 0.12 && luminance(c) <= 0.9
  let base = luminance(primary) < 0.12 && usable(secondary) ? secondary : primary
  for (let t = 0.1; luminance(base) < 0.18 && t <= 0.7; t += 0.1) base = shade(primary, -t)
  return base
}

/** Does `fg` stand out enough against `bg` to carry text? */
export function contrasts(fg: string, bg: string): boolean {
  const [a, b] = [luminance(fg), luminance(bg)].sort((x, y) => y - x)
  return (a + 0.05) / (b + 0.05) >= 2.2
}

/* ---------------- Countries ---------------- */

/** Stable small hash for deterministic per-entity visuals. */
export function hashOf(input: string | number): number {
  let h = 2166136261
  for (const ch of String(input)) h = Math.imul(h ^ ch.charCodeAt(0), 16777619)
  return h >>> 0
}

/* ---------------- Money ---------------- */

/** "€1.30bn" → 1_300_000_000, "€230k" → 230_000, "-€262.00m" → -262_000_000. Null when unparseable. */
export function parseMoney(value: string | null | undefined): number | null {
  const m = value?.replace(/,/g, '').match(/(-)?\D*?(\d+(?:\.\d+)?)\s*(bn|m|k)?/i)
  if (!m) return null
  const unit = { bn: 1e9, m: 1e6, k: 1e3 }[(m[3] ?? '').toLowerCase() as 'bn' | 'm' | 'k'] ?? 1
  return (m[1] ? -1 : 1) * Number(m[2]) * unit
}

/* ---------------- Nation accents ---------------- */

/** Ambient hover glow per featured nation, taken from its flag's dominant colours. */
export const NATION_GLOW: Record<string, [string, string]> = {
  ENG: ['#ce1124', '#ffffff'],
  ESP: ['#aa151b', '#f1bf00'],
  ITA: ['#009246', '#ce2b37'],
  GER: ['#dd0000', '#ffce00'],
  FRA: ['#0055a4', '#ef4135'],
}

/** The most valuable players across every league, by parsed market value. */
export function topValued(players: Player[], count = 8): Player[] {
  return players
    .map((p) => ({ p, v: parseMoney(p.market_value) }))
    .filter((x): x is { p: Player; v: number } => x.v !== null)
    .sort((a, b) => b.v - a.v)
    .slice(0, count)
    .map((x) => x.p)
}

/** The top five European football nations shown on the homepage, in display order. */
export const FEATURED_NATIONS = [
  { name: 'England', code: 'ENG' },
  { name: 'Spain', code: 'ESP' },
  { name: 'Italy', code: 'ITA' },
  { name: 'Germany', code: 'GER' },
  { name: 'France', code: 'FRA' },
] as const

/* ---------------- Typography ---------------- */

/**
 * Is this value a figure (number, money, percentage, date) rather than words?
 * Figures are set in the mono face so they align; words stay in the display face.
 * Matches 42, "8.97", "€1.30bn", "-€262.00m", "81.1%", "186 cm", "Jun 2027".
 */
export function isFigure(value: unknown): boolean {
  if (typeof value === 'number') return true
  if (typeof value !== 'string') return false
  const v = value.trim()
  return /^[-+]?[€$£]?\d[\d.,]*\s*(bn|m|k|%|cm|yrs?)?$/i.test(v) || /^[A-Z][a-z]{2} \d{4}$/.test(v) || /^\d+\s*\/\s*\d+$/.test(v)
}

/** Font classes for a value cell: mono for figures, display for words. */
export const valueFont = (value: unknown) => (isFigure(value) ? 'font-mono tabular-nums' : 'font-display')

/** 5_650_000_000 → "€5.65bn", 950_000_000 → "€950m" (same style as the API's values). */
export function formatMoney(n: number | null | undefined): string | null {
  if (n == null || !Number.isFinite(n)) return null
  const sign = n < 0 ? '-' : ''
  const a = Math.abs(n)
  if (a >= 1e9) return `${sign}€${(a / 1e9).toFixed(2)}bn`
  if (a >= 1e6) return `${sign}€${Math.round(a / 1e6)}m`
  if (a >= 1e3) return `${sign}€${Math.round(a / 1e3)}k`
  return `${sign}€${a}`
}

/** Generational suffixes that can't stand alone as a short name. */
const NAME_SUFFIXES: Record<string, string> = { júnior: 'Jr.', junior: 'Jr.', jr: 'Jr.', 'jr.': 'Jr.', filho: 'Filho', neto: 'Neto', sobrinho: 'Sobrinho' }

/**
 * Short display name for cards: the surname, except when the surname is a suffix
 * ("Vinícius Júnior" → "Vinícius Jr.", not "Júnior"). Mononyms ("Pedri") pass through.
 */
export function cardName(p: { first_name: string; last_name: string }): string {
  const last = p.last_name.trim()
  const suffix = NAME_SUFFIXES[last.toLowerCase()]
  if (suffix && p.first_name.trim()) return `${p.first_name.trim().split(/\s+/)[0]} ${suffix}`
  return last || p.first_name.trim()
}

/** Player monogram: "Lamine Yamal" → "LY", "Pedri" → "PE". */
export function monogramOf(p: { first_name: string; last_name: string }): string {
  const parts = `${p.first_name} ${p.last_name}`.trim().split(/\s+/)
  return (parts.length > 1 ? parts[0][0] + parts[parts.length - 1][0] : parts[0].slice(0, 2)).toUpperCase()
}
