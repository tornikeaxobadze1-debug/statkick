import { ArrowRightLeft, ArrowUpRight, Heart } from 'lucide-react'
import { useMemo } from 'react'
import { ageFrom, formatMonthYear, playerName, positionRank } from '../../lib/stats'
import Flag from '../Flag'
import type { ID, Player } from '../../types'
import PlayerCutout from '../PlayerCutout'
import { EmptyState, Undisclosed } from '../ui'

const GROUPS = [
  { key: 'Goalkeeper', label: 'Goalkeepers' },
  { key: 'Defender', label: 'Defenders' },
  { key: 'Midfielder', label: 'Midfielders' },
  { key: 'Forward', label: 'Forwards' },
]

/** Squad list grouped by position, with market value, contract and rating columns. */
export default function SquadTable({
  players,
  onOpen,
  onCompare,
  favoriteIds = [],
  onToggleFavorite,
}: {
  players: Player[]
  onOpen: (p: Player) => void
  /** Quick head-to-head trigger on each row. */
  onCompare?: (p: Player) => void
  /** Favourite players (heart on each row). */
  favoriteIds?: ID[]
  onToggleFavorite?: (p: Player) => void
}) {
  const groups = useMemo(() => {
    const shirt = (p: Player) => p.shirt_number ?? p.current_team?.pivot.shirt_number ?? 99
    const sorted = [...players].sort((a, b) => positionRank(a.position) - positionRank(b.position) || shirt(a) - shirt(b))
    const known = GROUPS.map((g) => ({ ...g, players: sorted.filter((p) => p.position === g.key) }))
    const other = sorted.filter((p) => !GROUPS.some((g) => g.key === p.position))
    return [...known, { key: 'Other', label: 'Other', players: other }].filter((g) => g.players.length)
  }, [players])

  if (!players.length) return <EmptyState>No players registered for this squad yet.</EmptyState>

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[760px] text-sm">
        {groups.map((g) => (
          <tbody key={g.key}>
            <tr>
              <th colSpan={10} scope="colgroup" className="pt-8 pb-2 text-left">
                <span className="font-display text-lg font-medium text-ink">{g.label}</span>
                <span className="ml-2 text-xs text-ink-mute">{g.players.length}</span>
              </th>
            </tr>
            {/* Subtle per-group column headers, so every figure is labelled where you read it */}
            <tr className="border-b border-line text-left text-[11.5px] text-ink-faint">
              <th scope="col" className="w-12 pb-2 font-normal">
                <span className="sr-only">Shirt number</span>
              </th>
              <th scope="col" className="pb-2 font-normal">Player</th>
              <th scope="col" className="pb-2 font-normal">Role</th>
              <th scope="col" className="pb-2 text-right font-normal">Age</th>
              <th scope="col" className="pb-2 text-center font-normal">Nat</th>
              <th scope="col" className="pb-2 text-right font-normal">Value</th>
              <th scope="col" className="pb-2 text-right font-normal">Contract</th>
              <th scope="col" className="pb-2 text-right font-normal">G / A</th>
              <th scope="col" className="pb-2 text-right font-normal">Rating</th>
              <th className="w-28 pb-2" aria-hidden />
            </tr>
            {g.players.map((p) => {
              const rating = p.avg_rating ?? p.totals.avg_rating
              const goals = p.goals ?? p.totals.goals
              const assists = p.assists ?? p.totals.assists
              return (
                <tr key={p.id} onClick={() => onOpen(p)} className="group cursor-pointer border-b border-line transition-colors hover:bg-surface-hover">
                  <td className="py-2.5 text-ink-mute font-mono tabular-nums">{p.shirt_number ?? p.current_team?.pivot.shirt_number ?? ''}</td>
                  <td className="py-2.5">
                    <button type="button" onClick={(e) => (e.stopPropagation(), onOpen(p))} className="flex items-center gap-3 text-left">
                      <span className="relative h-11 w-11 shrink-0 overflow-hidden bg-surface">
                        <PlayerCutout player={p} team={p.current_team} className="absolute inset-x-0 -bottom-1 h-12 w-full" />
                      </span>
                      <span className="min-w-0">
                        <span className="block font-medium text-ink group-hover:underline">{playerName(p)}</span>
                        <span className="block text-xs text-ink-mute">{p.playing_style ?? p.position}</span>
                      </span>
                    </button>
                  </td>
                  <td className="py-2.5 text-ink-soft">{p.primary_position ?? p.position}</td>
                  <td className="py-2.5 text-right font-mono tabular-nums">{p.age ?? ageFrom(p.date_of_birth) ?? <Undisclosed label="Unknown" />}</td>
                  <td className="py-2.5 text-center">
                    <Flag
                      code={p.country?.code}
                      name={p.country?.name ?? p.nationality ?? 'Unknown'}
                      className="inline-block h-[13px] w-[18px] rounded-[2px] align-middle ring-1 ring-white/15"
                    />
                  </td>
                  <td className="py-2.5 text-right font-medium font-mono tabular-nums">{p.market_value ?? <Undisclosed />}</td>
                  <td className="py-2.5 text-right text-ink-soft font-mono tabular-nums">{formatMonthYear(p.contract_expires) ?? <Undisclosed />}</td>
                  <td className="py-2.5 text-right font-mono tabular-nums">
                    {goals} / {assists}
                  </td>
                  <td className="py-2.5 text-right font-semibold font-mono tabular-nums">{rating != null ? rating.toFixed(2) : <Undisclosed label="Unrated" />}</td>
                  <td className="py-2.5 text-right">
                    <span className="flex items-center justify-end gap-1.5">
                      {onToggleFavorite && (
                        <RowHeart player={p} saved={favoriteIds.includes(p.id)} onToggle={onToggleFavorite} />
                      )}
                      {onCompare && (
                        <button
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation()
                            onCompare(p)
                          }}
                          aria-label={`Compare ${playerName(p)}`}
                          title="Compare"
                          className="flex h-7 w-7 items-center justify-center rounded-full border border-transparent text-ink-mute opacity-60 transition group-hover:opacity-100 hover:border-crimson/50 hover:text-ink focus-visible:border-crimson/50 focus-visible:opacity-100 focus-visible:outline-none"
                        >
                          <ArrowRightLeft className="h-3.5 w-3.5" aria-hidden />
                        </button>
                      )}
                      <ArrowUpRight className="h-4 w-4 text-ink-mute transition group-hover:text-ink" aria-hidden />
                    </span>
                  </td>
                </tr>
              )
            })}
          </tbody>
        ))}
      </table>
    </div>
  )
}

/** Per-row favourite toggle: always visible once saved, otherwise revealed on row hover/focus. */
function RowHeart({ player, saved, onToggle }: { player: Player; saved: boolean; onToggle: (p: Player) => void }) {
  return (
    <button
      type="button"
      onClick={(e) => {
        e.stopPropagation()
        onToggle(player)
      }}
      aria-pressed={saved}
      aria-label={saved ? `Remove ${playerName(player)} from favorites` : `Save ${playerName(player)} to favorites`}
      title={saved ? 'Saved' : 'Save'}
      className={`flex h-7 w-7 items-center justify-center rounded-full border border-transparent transition hover:border-crimson/50 focus-visible:border-crimson/50 focus-visible:opacity-100 focus-visible:outline-none ${
        saved ? 'text-crimson' : 'text-ink-mute opacity-60 group-hover:opacity-100 hover:text-ink'
      }`}
    >
      <Heart className="h-3.5 w-3.5" fill={saved ? 'currentColor' : 'none'} aria-hidden />
    </button>
  )
}
