import { Heart } from 'lucide-react'
import { cardName, contrasts, monogramOf, formationLines, formatNumber, readableOn, teamColors } from '../../lib/stats'
import type { Player, Team, TeamDetail } from '../../types'
import TeamCrest from '../TeamCrest'
import { Fact } from '../ui'

interface ClubDossierProps {
  /** Full club profile (GET /teams/{id}). */
  detail: TeamDetail | null
  /** Standings row from the league list, when available. */
  row: Team | null
  players: Player[]
  /** Position in the league table (1-based) and league size, when known. */
  rank?: { position: number; of: number } | null
  /** Favourite-club toggle in the header (omitted: no toggle). */
  isFavorite?: boolean
  onToggleFavorite?: () => void
}

/** Editorial club header: identity, key facts, trophy cabinet and formation lineup. */
export default function ClubDossier({ detail, row, players, rank = null, isFavorite = false, onToggleFavorite }: ClubDossierProps) {
  const club = detail ?? row
  if (!club) return null
  const capacity = formatNumber(club.stadium_capacity)
  const trophies = club.trophies ?? []
  const totalTrophies = trophies.reduce((n, t) => n + t.count, 0)

  return (
    <div className="mb-14">
      {/* Identity */}
      <div className="flex flex-wrap items-end gap-6 border-b border-line pb-8">
        <TeamCrest team={club} size="xl" />
        <div className="min-w-0 flex-1">
          <p className="label">
            {[detail?.division.name ?? row?.division.name, club.city, club.founded_year && `Est. ${club.founded_year}`].filter(Boolean).join(' · ')}
          </p>
          <div className="mt-2 flex flex-wrap items-center gap-x-5 gap-y-3">
            <h3 className="font-display text-5xl font-medium tracking-tight sm:text-6xl">{club.name}</h3>
            {onToggleFavorite && (
              <button
                type="button"
                onClick={onToggleFavorite}
                aria-pressed={isFavorite}
                aria-label={isFavorite ? `Remove ${club.name} from favorite clubs` : `Save ${club.name} to favorite clubs`}
                className={`flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition ${
                  isFavorite ? 'border-crimson/60 bg-crimson/10 text-ink' : 'border-line text-ink-soft hover:border-crimson/50 hover:text-ink'
                }`}
              >
                <Heart className={`h-4 w-4 ${isFavorite ? 'text-crimson' : ''}`} fill={isFavorite ? 'currentColor' : 'none'} aria-hidden />
                {isFavorite ? 'Saved' : 'Save club'}
              </button>
            )}
          </div>
        </div>
        {row && (
          <div className="text-right">
            <p className="label">Points</p>
            <p className="text-5xl font-medium font-mono tabular-nums">{row.record.points}</p>
            <p className="text-xs text-ink-mute font-mono tabular-nums">
              {row.record.wins}W {row.record.draws}D {row.record.losses}L
            </p>
          </div>
        )}
      </div>

      <div className="grid gap-10 pt-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
        <div>
          {/* Key facts */}
          <dl className="hairline-grid grid-cols-2 sm:grid-cols-3">
            <Fact className="p-5" label="Total market value" value={club.total_market_value} />
            <Fact
              className="p-5"
              label="Stadium"
              value={
                club.stadium_name ? (
                  <>
                    {club.stadium_name}
                    {capacity && <span className="block text-xs text-ink-mute">{capacity} seats</span>}
                  </>
                ) : null
              }
            />
            <Fact className="p-5" label="Head coach" value={club.coach_name} />
            <Fact className="p-5" label="Net spend" value={club.net_spend} />
            <Fact className="p-5" label="Annual wage bill" value={club.annual_wage_bill} />
            <Fact
              className="p-5"
              label="League rank"
              value={
                rank ? (
                  <>
                    {ordinal(rank.position)} <span className="text-sm text-ink-mute">of {rank.of}</span>
                  </>
                ) : null
              }
            />
            <Fact className="p-5" label="Squad size" value={players.length || club.players_count} />
            <Fact className="p-5" label="Average age" value={club.average_age?.toFixed(1)} />
            <Fact
              className="p-5"
              label="Foreigners"
              value={club.foreigners_count != null ? `${club.foreigners_count}${club.foreigners_pct != null ? ` · ${club.foreigners_pct}%` : ''}` : null}
            />
          </dl>

          {/* Trophy cabinet */}
          <div className="mt-10">
            <p className="label mb-3">
              Trophy cabinet{totalTrophies > 0 && <span className="ml-2 text-ink">{totalTrophies}</span>}
            </p>
            {trophies.length ? (
              <ul className="border-t border-ink">
                {trophies.map((t) => (
                  <li key={t.title} className="flex items-baseline justify-between gap-4 border-b border-line py-3">
                    <span className="text-sm text-ink-soft">{t.title}</span>
                    <span className="text-xl font-mono tabular-nums">{t.count}×</span>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-ink-mute">No honours recorded.</p>
            )}
          </div>
        </div>

        {/* Formation */}
        <div>
          <p className="label mb-3">Preferred formation{club.preferred_formation && <span className="ml-2 text-ink">{club.preferred_formation}</span>}</p>
          <FormationPitch formation={club.preferred_formation ?? null} players={players} color={markerColor(teamColors(club))} />
        </div>
      </div>
    </div>
  )
}

const PITCH = '#0e090b'
const LINE_WHITE = 'rgba(255,255,255,0.22)'
const LINE_CRIMSON = 'rgba(225,29,72,0.55)'
const EMPTY_SLOT = '#2a323c'

/** 1 → "1st", 2 → "2nd", 11 → "11th", 23 → "23rd". */
const ordinal = (n: number) => {
  const s = ['th', 'st', 'nd', 'rd'] as const
  const v = n % 100
  return `${n}${s[(v - 20) % 10] ?? s[v] ?? s[0]}`
}

const shirtOf = (p: Player) => p.shirt_number ?? p.current_team?.pivot.shirt_number ?? null

/** A kit colour that stays visible on the dark pitch (Inter's near-black → its secondary, else off-white). */
function markerColor({ primary, secondary }: { primary: string; secondary: string }) {
  if (contrasts(primary, PITCH)) return primary
  return contrasts(secondary, PITCH) ? secondary : '#f4f6f8'
}

/**
 * Vertical mini-pitch with the formation's lines. Slots are filled from the squad by
 * position — players with a registered shirt number first, then best-rated — so every
 * starter shows a real number; initials appear only if no numbered player is left for
 * that slot. Slots the squad can't fill at all show as neutral empty markers.
 */
function FormationPitch({ formation, players, color }: { formation: string | null; players: Player[]; color: string }) {
  const lines = formationLines(formation) ?? [4, 3, 3]
  const pool = (pos: string) =>
    players
      .filter((p) => p.position === pos)
      .sort(
        (a, b) =>
          Number(shirtOf(b) != null) - Number(shirtOf(a) != null) ||
          (b.avg_rating ?? b.totals.avg_rating ?? 0) - (a.avg_rating ?? a.totals.avg_rating ?? 0),
      )
  const gk = pool('Goalkeeper')
  const defs = pool('Defender')
  const mids = pool('Midfielder')
  const fwds = pool('Forward')

  // Back line ← defenders, front line ← forwards, middle lines ← midfielders (then leftovers).
  const rows: (Player | null)[][] = lines.map((count, i) => {
    const isBack = i === 0
    const isFront = i === lines.length - 1
    // Natural pool first; when a line runs short, borrow from the nearest outfield line
    // (e.g. a forward plays the No. 10 in a 4-2-3-1). Goalkeepers never fill outfield slots.
    const sources = isBack ? [defs, mids, fwds] : isFront ? [fwds, mids, defs] : [mids, fwds, defs]
    const next = () => sources.find((pool) => pool.length > 0)?.shift() ?? null
    // Fill centre-out, so a short squad still looks like a shape rather than a left-aligned list.
    const order = Array.from({ length: count }, (_, k) => k).sort((a, b) => Math.abs(a - (count - 1) / 2) - Math.abs(b - (count - 1) / 2))
    const slots: (Player | null)[] = Array(count).fill(null)
    for (const k of order) slots[k] = next()
    return slots
  })
  const allRows = [[gk[0] ?? null], ...rows] // GK at the bottom

  return (
    <div className="relative aspect-[3/4] w-full rounded-xl border border-line" style={{ background: `radial-gradient(80% 60% at 50% 50%, #140b0f, ${PITCH})` }}>
      {/* Pitch markings */}
      <svg viewBox="0 0 300 400" className="absolute inset-0 h-full w-full" aria-hidden>
        {/* Crisp minimal markings: white touchlines and boxes, crimson halfway line and centre */}
        <g fill="none" strokeWidth="1">
          <rect x="12" y="12" width="276" height="376" stroke={LINE_WHITE} />
          <rect x="80" y="12" width="140" height="56" stroke={LINE_WHITE} />
          <rect x="116" y="12" width="68" height="20" stroke={LINE_WHITE} />
          <rect x="80" y="332" width="140" height="56" stroke={LINE_WHITE} />
          <rect x="116" y="368" width="68" height="20" stroke={LINE_WHITE} />
          <line x1="12" y1="200" x2="288" y2="200" stroke={LINE_CRIMSON} />
          <circle cx="150" cy="200" r="36" stroke={LINE_CRIMSON} />
        </g>
        <circle cx="150" cy="200" r="2" fill={LINE_CRIMSON} />
      </svg>
      <ol className="absolute inset-0 flex flex-col-reverse justify-around px-3 py-6" aria-label={`Formation ${formation ?? '4-3-3'}`}>
        {allRows.map((row, r) => (
          <li key={r} className="flex justify-around">
            {row.map((p, s) => (
              <span key={s} className="flex w-16 flex-col items-center text-center">
                <span
                  className="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold"
                  style={{
                    background: p ? color : EMPTY_SLOT,
                    color: readableOn(p ? color : EMPTY_SLOT),
                    boxShadow: p ? `0 0 0 2px ${PITCH}, 0 0 14px -2px ${color}` : `0 0 0 2px ${PITCH}`,
                  }}
                >
                  {p ? (shirtOf(p) ?? <span className="text-[9px] tracking-tight">{monogramOf(p)}</span>) : ''}
                </span>
                <span className="mt-1 w-full truncate text-[10px] leading-tight text-ink-soft">{p ? cardName(p) : '—'}</span>
              </span>
            ))}
          </li>
        ))}
      </ol>
    </div>
  )
}
