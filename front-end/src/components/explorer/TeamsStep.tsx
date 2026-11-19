import { motion } from 'framer-motion'
import { useMemo } from 'react'
import { standingsOrder, valueFont } from '../../lib/stats'
import type { ID, Team } from '../../types'
import TeamCrest from '../TeamCrest'
import { EmptyState, Skeleton } from '../ui'

/** Whether every club's standings record is in hand. */
export type StandingsState = 'ready' | 'loading' | 'unavailable'

interface TeamsStepProps {
  leagueName: string
  /** The league's clubs: always the same clubs as the league card. */
  teams: Team[]
  /** True only while no club of the league is known yet. */
  loading: boolean
  standings: StandingsState
  selectedId: ID | null
  onSelect: (team: Team) => void
}

/** League table on the left, editorial club cards on the right. */
export default function TeamsStep({ leagueName, teams, loading, standings, selectedId, onSelect }: TeamsStepProps) {
  // Without records (still loading or being refreshed), clubs are listed alphabetically.
  const table = useMemo(
    () => [...teams].sort(standings === 'ready' ? standingsOrder : (a, b) => a.name.localeCompare(b.name)),
    [teams, standings],
  )

  if (loading && teams.length === 0) {
    return (
      <div className="grid gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <Skeleton className="h-72" />
        <Skeleton className="h-72" />
      </div>
    )
  }
  if (table.length === 0) return <EmptyState>No clubs in {leagueName} yet.</EmptyState>

  return (
    <div className="grid gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
      {/* Standings */}
      <div className="min-w-0">
        <p className="label mb-3">Table · finished matches</p>
        {standings === 'loading' && <Skeleton className="h-72" />}
        {standings === 'unavailable' && (
          <p className="border-t border-ink py-8 text-sm text-ink-mute">The table is being refreshed. The clubs are listed alongside.</p>
        )}
        {standings === 'ready' && (
          <div className="overflow-x-auto border-t border-ink">
            <table className="w-full text-sm">
              <thead>
                <tr className="label border-b border-line text-left">
                  <th className="py-3 pr-2 font-medium">#</th>
                  <th className="py-3 font-medium">Club</th>
                  <th className="py-3 text-right font-medium">P</th>
                  <th className="py-3 text-right font-medium">W</th>
                  <th className="py-3 text-right font-medium">D</th>
                  <th className="py-3 text-right font-medium">L</th>
                  <th className="py-3 text-right font-medium">GD</th>
                  <th className="py-3 text-right font-medium">Pts</th>
                </tr>
              </thead>
              <tbody>
                {table.map((t, i) => {
                  const gd = t.record.goals_for - t.record.goals_against
                  return (
                    <tr
                      key={t.id}
                      onClick={() => onSelect(t)}
                      className={`cursor-pointer border-b border-line transition-colors hover:bg-surface-hover ${t.id === selectedId ? 'bg-surface-hover font-medium shadow-[inset_2px_0_0_var(--color-crimson)]' : ''}`}
                    >
                      <td className="py-3 pr-2 text-ink-mute font-mono tabular-nums">{i + 1}</td>
                      <td className="py-3">
                        <span className="flex items-center gap-2.5">
                          <TeamCrest team={t} size="sm" />
                          <span className="truncate">{t.name}</span>
                        </span>
                      </td>
                      <td className="py-3 text-right font-mono tabular-nums">{t.record.played}</td>
                      <td className="py-3 text-right font-mono tabular-nums">{t.record.wins}</td>
                      <td className="py-3 text-right font-mono tabular-nums">{t.record.draws}</td>
                      <td className="py-3 text-right font-mono tabular-nums">{t.record.losses}</td>
                      <td className="py-3 text-right font-mono tabular-nums">
                        {gd > 0 ? '+' : ''}
                        {gd}
                      </td>
                      <td className="py-3 text-right text-base font-semibold font-mono tabular-nums">{t.record.points}</td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Club cards */}
      <div className="min-w-0">
        <p className="label mb-3">Clubs</p>
        <ul className="hairline-grid sm:grid-cols-2">
          {table.map((t, i) => {
            const selected = t.id === selectedId
            return (
              <motion.li key={t.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} transition={{ delay: i * 0.04 }}>
                <button
                  type="button"
                  onClick={() => onSelect(t)}
                  aria-pressed={selected}
                  className={`group flex h-full w-full flex-col p-5 text-left transition-colors duration-300 ${selected ? 'bg-[linear-gradient(160deg,rgba(127,16,40,0.42),rgba(17,12,15,0.92)_65%)] text-ink shadow-[0_0_0_1px_rgba(225,29,72,0.35),0_0_28px_rgba(200,30,69,0.25)]' : 'hover:bg-surface-hover hover:shadow-[inset_0_0_0_1px_rgba(225,29,72,0.35),0_0_28px_rgba(200,30,69,0.18)]'}`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <p className="font-display truncate text-xl font-medium tracking-tight">{t.name}</p>
                      <p className="text-xs text-ink-mute">{t.city ?? t.country.name}</p>
                    </div>
                    <TeamCrest team={t} size="md" />
                  </div>
                  <dl className="mt-6 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                    <CardFact label="Market value" value={t.total_market_value} />
                    <CardFact label="Squad size" value={t.players_count} />
                    <CardFact label="Avg. age" value={t.average_age?.toFixed(1)} />
                    <CardFact label="Formation" value={t.preferred_formation} />
                  </dl>
                </button>
              </motion.li>
            )
          })}
        </ul>
      </div>
    </div>
  )
}

function CardFact({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div className="min-w-0">
      <dt className="label">{label}</dt>
      <dd className={`mt-1 truncate text-base ${valueFont(value)}`}>{value ?? '—'}</dd>
    </div>
  )
}
