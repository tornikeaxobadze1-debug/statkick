import { motion } from 'framer-motion'
import { ArrowRightLeft, RefreshCw, Search, X } from 'lucide-react'
import { useEffect, useMemo, useRef, useState, type KeyboardEvent, type ReactNode } from 'react'
import { estimatedFields } from '../../lib/estimates'
import { ageFrom, cardName, formatMonthYear, parseMoney, playerName } from '../../lib/stats'
import type { ID, Player, PlayerTotals, StagePlayer } from '../../types'
import Flag from '../Flag'
import PlayerCutout from '../PlayerCutout'
import TeamCrest from '../TeamCrest'
import { EstPill, Undisclosed } from '../ui'

/** Anything with a profile: the list payload (with totals) or the stage's detail payload. */
export type ComparePlayer = StagePlayer & { totals?: PlayerTotals }

interface CompareViewProps {
  /** Every player across the 17 clubs (the list payload). */
  players: Player[]
  a: ComparePlayer
  b: ComparePlayer | null
  onPick: (id: ID | null) => void
  onSwap: () => void
  onOpenPlayer: (p: ComparePlayer) => void
  onClose: () => void
}

/** Head-to-head comparison: two players side by side with a structured stat breakdown. */
export default function CompareView({ players, a, b, onPick, onSwap, onOpenPlayer, onClose }: CompareViewProps) {
  const dialog = useRef<HTMLDivElement>(null)

  // Esc closes (the stage underneath pauses its own Esc meanwhile).
  useEffect(() => {
    const onKey = (e: globalThis.KeyboardEvent) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])
  // Scroll is locked underneath; focus returns to the opener afterwards.
  useEffect(() => {
    const opener = document.activeElement as HTMLElement | null
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      document.body.style.overflow = prev
      opener?.focus?.()
    }
  }, [])
  useEffect(() => {
    if (b) dialog.current?.querySelector<HTMLElement>('[data-autofocus]')?.focus()
  }, [b])

  // Keep Tab inside the dialog.
  const onKeyDown = (e: KeyboardEvent<HTMLDivElement>) => {
    if (e.key !== 'Tab' || !dialog.current) return
    const focusable = [...dialog.current.querySelectorAll<HTMLElement>('button, input, [tabindex="0"]')].filter((el) => !el.hasAttribute('disabled'))
    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault()
      last?.focus()
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault()
      first?.focus()
    }
  }

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      transition={{ duration: 0.25 }}
      className="fixed inset-0 z-[60] overflow-y-auto bg-[rgba(5,4,6,0.86)] backdrop-blur-md"
      onMouseDown={(e) => e.target === e.currentTarget && onClose()}
    >
      <motion.div
        ref={dialog}
        role="dialog"
        aria-modal="true"
        aria-labelledby="compare-title"
        onKeyDown={onKeyDown}
        initial={{ opacity: 0, y: 16 }}
        animate={{ opacity: 1, y: 0 }}
        exit={{ opacity: 0, y: 8 }}
        transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
        className="relative mx-auto my-6 w-[calc(100%-2rem)] max-w-[1040px] rounded-2xl border border-line bg-canvas shadow-[0_30px_80px_-20px_rgba(0,0,0,0.8)] sm:my-10"
      >
        <header className="flex items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-7">
          <div>
            <p className="label">Head-to-head</p>
            <h2 id="compare-title" className="font-display mt-1 text-2xl font-medium tracking-tight">
              {b ? (
                <>
                  {cardName(a)} <span className="text-ink-mute">vs</span> {cardName(b)}
                </>
              ) : (
                'Compare players'
              )}
            </h2>
          </div>
          <div className="flex items-center gap-2">
            {b && (
              <button
                type="button"
                onClick={onSwap}
                className="flex h-9 items-center gap-2 rounded-full border border-line px-3.5 text-[13px] text-ink-soft transition hover:border-crimson/50 hover:text-ink"
              >
                <ArrowRightLeft className="h-3.5 w-3.5" aria-hidden /> Swap
              </button>
            )}
            <button
              type="button"
              onClick={onClose}
              aria-label="Close comparison"
              data-autofocus={b ? true : undefined}
              className="flex h-9 w-9 items-center justify-center rounded-full border border-line text-ink-soft transition hover:border-crimson/50 hover:text-ink"
            >
              <X className="h-4 w-4" aria-hidden />
            </button>
          </div>
        </header>

        <div className="grid grid-cols-2 gap-3 p-4 sm:gap-5 sm:p-7">
          <PlayerCard player={a} side="left" onOpen={() => onOpenPlayer(a)} />
          {b ? (
            <PlayerCard player={b} side="right" onOpen={() => onOpenPlayer(b)} onChange={() => onPick(null)} />
          ) : (
            <Picker players={players} a={a} onPick={onPick} />
          )}
        </div>

        {b ? (
          <StatBreakdown a={a} b={b} />
        ) : (
          <p className="border-t border-line px-7 py-5 text-center text-sm text-ink-mute">
            Pick a second player to see the head-to-head breakdown.
          </p>
        )}
      </motion.div>
    </motion.div>
  )
}

/* ---------------- Player cards ---------------- */

function PlayerCard({ player: p, side, onOpen, onChange }: { player: ComparePlayer; side: 'left' | 'right'; onOpen: () => void; onChange?: () => void }) {
  const team = p.current_team
  return (
    <div className="relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-line bg-surface">
      <div className="relative aspect-[5/4] w-full bg-[radial-gradient(90%_75%_at_50%_100%,rgba(225,29,72,0.16),transparent_70%)] sm:aspect-[2/1]">
        <PlayerCutout player={p} team={team} className="absolute inset-x-0 bottom-0 h-[92%] w-full" />
        {team && (
          <span className={`absolute top-2 sm:top-3 ${side === 'left' ? 'left-2 sm:left-3' : 'right-2 sm:right-3'}`}>
            <span className="sm:hidden">
              <TeamCrest team={team} size="sm" />
            </span>
            <span className="hidden sm:block">
              <TeamCrest team={team} size="md" />
            </span>
          </span>
        )}
      </div>
      <div className="border-t border-line px-3 py-3 sm:px-4">
        <p className="flex items-center gap-2 text-xs text-ink-mute">
          <Flag code={p.country?.code} name={p.country?.name ?? p.nationality ?? 'Unknown'} className="h-3 w-4 shrink-0 rounded-[2px] ring-1 ring-white/15" />
          <span className="truncate">
            {p.nationality ?? p.country?.name} · {p.primary_position ?? p.position}
          </span>
        </p>
        <p className="font-display mt-1 line-clamp-2 text-lg leading-tight font-medium break-words sm:truncate sm:text-xl">{playerName(p)}</p>
        <p className="truncate text-xs text-ink-mute">{team?.name ?? 'Free agent'}</p>
        <div className="mt-3 flex flex-wrap gap-2">
          <button type="button" onClick={onOpen} className="rounded-full border border-line px-3 py-1 text-xs text-ink-soft transition hover:border-crimson/50 hover:text-ink">
            360° profile
          </button>
          {onChange && (
            <button
              type="button"
              onClick={onChange}
              className="flex items-center gap-1.5 rounded-full border border-line px-3 py-1 text-xs text-ink-soft transition hover:border-crimson/50 hover:text-ink"
            >
              <RefreshCw className="h-3 w-3" aria-hidden /> Change
            </button>
          )}
        </div>
      </div>
    </div>
  )
}

/* ---------------- Second-player picker ---------------- */

const fold = (s: string) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()

function Picker({ players, a, onPick }: { players: Player[]; a: ComparePlayer; onPick: (id: ID) => void }) {
  const [query, setQuery] = useState('')
  const input = useRef<HTMLInputElement>(null)
  useEffect(() => input.current?.focus(), [])

  const others = useMemo(() => players.filter((p) => p.id !== a.id), [players, a.id])
  const results = useMemo(() => {
    const q = fold(query.trim())
    if (!q) return null
    return others
      .filter((p) => fold(`${p.first_name} ${p.last_name} ${p.current_team?.name ?? ''} ${p.current_team?.short_code ?? ''}`).includes(q))
      .sort((x, y) => (parseMoney(y.market_value) ?? 0) - (parseMoney(x.market_value) ?? 0))
      .slice(0, 8)
  }, [others, query])
  const quick = useMemo(() => {
    const byValue = (x: Player, y: Player) => (parseMoney(y.market_value) ?? 0) - (parseMoney(x.market_value) ?? 0)
    const samePos = others.filter((p) => p.position === a.position)
    const squad = samePos.filter((p) => p.current_team?.id != null && p.current_team.id === a.current_team?.id).sort(byValue).slice(0, 4)
    const league = samePos.filter((p) => !squad.includes(p)).sort(byValue).slice(0, 4)
    return { squad, league }
  }, [others, a])

  return (
    <div className="flex min-w-0 flex-col rounded-xl border border-dashed border-crimson/30 bg-surface/60 p-3 sm:p-4">
      <label className="relative block">
        <span className="sr-only">Search any player across all clubs</span>
        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-mute" aria-hidden />
        <input
          ref={input}
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search any player or club…"
          className="h-10 w-full rounded-full border border-line bg-canvas pr-4 pl-9 text-sm text-ink placeholder:text-ink-faint focus:border-crimson/50 focus:outline-none"
        />
      </label>
      <div className="mt-3 max-h-[19rem] min-h-0 flex-1 overflow-y-auto pr-1">
        {results ? (
          results.length ? (
            <PickList players={results} onPick={onPick} />
          ) : (
            <p className="px-1 py-6 text-center text-sm text-ink-mute">No player matches “{query.trim()}”.</p>
          )
        ) : (
          <>
            {quick.squad.length > 0 && (
              <>
                <p className="label mb-1.5 px-1">Same squad · {a.position}s</p>
                <PickList players={quick.squad} onPick={onPick} />
              </>
            )}
            {quick.league.length > 0 && (
              <>
                <p className="label mt-3 mb-1.5 px-1">Top {a.position.toLowerCase()}s across Europe</p>
                <PickList players={quick.league} onPick={onPick} />
              </>
            )}
          </>
        )}
      </div>
    </div>
  )
}

function PickList({ players, onPick }: { players: Player[]; onPick: (id: ID) => void }) {
  return (
    <ul className="space-y-1">
      {players.map((p) => (
        <li key={p.id}>
          <button
            type="button"
            onClick={() => onPick(p.id)}
            className="flex w-full items-center gap-3 rounded-lg px-1.5 py-1.5 text-left transition hover:bg-surface-hover focus-visible:bg-surface-hover focus-visible:outline-none"
          >
            <span className="relative h-9 w-9 shrink-0 overflow-hidden rounded-md bg-canvas">
              <PlayerCutout player={p} team={p.current_team} className="absolute inset-x-0 -bottom-0.5 h-10 w-full" />
            </span>
            <span className="min-w-0 flex-1">
              <span className="block truncate text-sm font-medium text-ink">{playerName(p)}</span>
              <span className="block truncate text-xs text-ink-mute">
                {p.current_team?.short_name ?? p.current_team?.name ?? 'Free agent'} · {p.primary_position ?? p.position}
              </span>
            </span>
            <span className="shrink-0 font-mono text-xs text-crimson-light tabular-nums">{p.market_value}</span>
          </button>
        </li>
      ))}
    </ul>
  )
}

/* ---------------- Stat breakdown ---------------- */

type Better = 'higher' | 'lower' | null

interface StatRow {
  label: string
  field?: string
  a: ReactNode
  b: ReactNode
  /** Numeric values for the advantage check and the bars. */
  na?: number | null
  nb?: number | null
  better: Better
}

const goalsOf = (p: ComparePlayer) => p.goals ?? p.totals?.goals ?? null
const assistsOf = (p: ComparePlayer) => p.assists ?? p.totals?.assists ?? null
const ratingOf = (p: ComparePlayer) => p.avg_rating ?? p.totals?.avg_rating ?? null
const contractYear = (iso: string | null | undefined) => (iso ? new Date(iso).getTime() : null)

function rowsFor(a: ComparePlayer, b: ComparePlayer): StatRow[] {
  const money = (field: 'market_value' | 'highest_market_value' | 'weekly_salary' | 'annual_salary', label: string, better: Better): StatRow => ({
    label,
    field,
    a: a[field],
    b: b[field],
    na: parseMoney(a[field]),
    nb: parseMoney(b[field]),
    better,
  })
  const ageA = a.age ?? ageFrom(a.date_of_birth)
  const ageB = b.age ?? ageFrom(b.date_of_birth)
  return [
    money('market_value', 'Market value', 'higher'),
    money('highest_market_value', 'Peak value', 'higher'),
    // Pay and physical profile are context, not an edge: shown side by side, unscored.
    money('weekly_salary', 'Weekly salary', null),
    money('annual_salary', 'Annual salary', null),
    { label: 'Age', field: 'age', a: ageA, b: ageB, na: ageA, nb: ageB, better: null },
    { label: 'Height', field: 'height_cm', a: a.height_cm ? `${a.height_cm} cm` : null, b: b.height_cm ? `${b.height_cm} cm` : null, better: null },
    { label: 'Preferred foot', field: 'preferred_foot', a: a.preferred_foot, b: b.preferred_foot, better: null },
    {
      label: 'Contract expiry',
      field: 'contract_expires',
      a: formatMonthYear(a.contract_expires),
      b: formatMonthYear(b.contract_expires),
      na: contractYear(a.contract_expires),
      nb: contractYear(b.contract_expires),
      better: null,
    },
    { label: 'Goals', a: goalsOf(a), b: goalsOf(b), na: goalsOf(a), nb: goalsOf(b), better: 'higher' },
    { label: 'Assists', a: assistsOf(a), b: assistsOf(b), na: assistsOf(a), nb: assistsOf(b), better: 'higher' },
    {
      label: 'Season rating',
      a: ratingOf(a)?.toFixed(2) ?? null,
      b: ratingOf(b)?.toFixed(2) ?? null,
      na: ratingOf(a),
      nb: ratingOf(b),
      better: 'higher',
    },
  ]
}

function StatBreakdown({ a, b }: { a: ComparePlayer; b: ComparePlayer }) {
  const rows = rowsFor(a, b)
  const estA = estimatedFields(a.profile_estimates)
  const estB = estimatedFields(b.profile_estimates)
  const tally = rows.reduce(
    (t, r) => {
      const w = winner(r)
      return w ? { ...t, [w]: t[w] + 1 } : t
    },
    { a: 0, b: 0 },
  )

  return (
    <section aria-label="Stat breakdown" className="border-t border-line px-4 pt-5 pb-6 sm:px-7">
      <div className="mb-3 flex items-center justify-between text-xs text-ink-mute">
        <span className="font-mono tabular-nums">
          <span className={tally.a > tally.b ? 'text-win-light' : ''}>{tally.a}</span> {tally.a === 1 ? 'edge' : 'edges'}
        </span>
        <span className="label">Advantages highlighted</span>
        <span className="font-mono tabular-nums">
          <span className={tally.b > tally.a ? 'text-win-light' : ''}>{tally.b}</span> {tally.b === 1 ? 'edge' : 'edges'}
        </span>
      </div>
      <dl>
        {rows.map((r) => {
          const w = winner(r)
          const max = Math.max(Math.abs(r.na ?? 0), Math.abs(r.nb ?? 0))
          const scored = r.better !== null && r.na != null && r.nb != null && max > 0
          return (
            <div key={r.label} className="grid grid-cols-[1fr_auto_1fr] items-center gap-3 border-b border-line py-2.5 sm:gap-6">
              <Cell value={r.a} wins={w === 'a'} estimate={r.field ? estA.get(r.field) : undefined} align="right" bar={scored ? (r.na ?? 0) / max : null} />
              <dt className="w-24 text-center text-[12px] text-ink-mute sm:w-32">{r.label}</dt>
              <Cell value={r.b} wins={w === 'b'} estimate={r.field ? estB.get(r.field) : undefined} align="left" bar={scored ? (r.nb ?? 0) / max : null} />
            </div>
          )
        })}
      </dl>
      <p className="mt-4 text-center text-[11.5px] text-ink-faint">
        Edges count market value, peak value, goals, assists and rating. Pay, age, height, foot and contract are shown for context.
      </p>
    </section>
  )
}

function winner(r: StatRow): 'a' | 'b' | null {
  if (r.better === null || r.na == null || r.nb == null || r.na === r.nb) return null
  return (r.better === 'higher' ? r.na > r.nb : r.na < r.nb) ? 'a' : 'b'
}

function Cell({ value, wins, estimate, align, bar }: { value: ReactNode; wins: boolean; estimate?: string | null; align: 'left' | 'right'; bar: number | null }) {
  const numeric = typeof value === 'number' || (typeof value === 'string' && /\d/.test(value))
  return (
    <dd className={`min-w-0 ${align === 'right' ? 'text-right' : 'text-left'}`}>
      <span className={`inline-flex items-center gap-1 ${align === 'right' ? 'flex-row-reverse' : ''}`}>
        <span className={`${numeric ? 'font-mono tabular-nums' : ''} text-[15px] ${wins ? 'font-semibold text-win-light' : 'text-ink'}`}>
          {value ?? <Undisclosed />}
        </span>
        {value != null && estimate !== undefined && <EstPill note={estimate} />}
        {wins && <span className="sr-only">(advantage)</span>}
      </span>
      {bar !== null && (
        <span aria-hidden className={`mt-1.5 flex h-1 w-full overflow-hidden rounded-full bg-white/[0.06] ${align === 'right' ? 'justify-end' : ''}`}>
          <span className={`h-full rounded-full ${wins ? 'bg-win-light/80' : 'bg-white/25'}`} style={{ width: `${Math.max(4, bar * 100)}%` }} />
        </span>
      )}
    </dd>
  )
}
