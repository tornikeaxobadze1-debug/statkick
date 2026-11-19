import { motion } from 'framer-motion'
import { useMemo, type ReactNode } from 'react'
import { cardName, parseMoney, topValued } from '../../lib/stats'
import type { Player, Team } from '../../types'
import PlayerCutout from '../PlayerCutout'
import TeamCrest from '../TeamCrest'

interface TrendingProps {
  teams: Team[]
  players: Player[]
  onOpenTeam: (team: Team) => void
  onOpenPlayer: (player: Player) => void
}

/** Live "Trending squads" (by squad market value) and "Top market value" (players), as swipeable strips. */
export default function Trending({ teams, players, onOpenTeam, onOpenPlayer }: TrendingProps) {
  const squads = useMemo(
    () =>
      teams
        .map((t) => ({ t, v: parseMoney(t.total_market_value) }))
        .filter((x): x is { t: Team; v: number } => x.v !== null)
        .sort((a, b) => b.v - a.v)
        .slice(0, 10)
        .map((x) => x.t),
    [teams],
  )
  const stars = useMemo(() => topValued(players, 12), [players])
  if (!squads.length && !stars.length) return null

  return (
    <section id="trending" aria-labelledby="trending-title" className="scroll-mt-20 border-t border-line pt-8">
      <header className="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="label">Live market</p>
          <h2 id="trending-title" className="font-display mt-3 text-3xl font-medium tracking-tight sm:text-4xl">
            Trending Squads <span className="text-ink-mute">&amp;</span> Top Market Value
          </h2>
        </div>
        <p className="text-xs text-ink-mute">Ranked by market value · swipe for more</p>
      </header>

      {squads.length > 0 && (
        <Strip label="Squads">
          {squads.map((t, i) => {
            return (
              <Card key={t.id} i={i} className="w-64 lg:w-[calc((100%-3rem)/5)]">
                <button type="button" onClick={() => onOpenTeam(t)} className="group flex h-full w-full flex-col text-left">
                  <span className="flex flex-1 flex-col p-4">
                    <span className="flex items-start justify-between gap-3">
                      <span className="min-w-0">
                        <span className="font-display block truncate text-lg font-medium text-ink">{t.name}</span>
                        <span className="block truncate text-xs text-ink-mute">{t.division.name}</span>
                      </span>
                      <TeamCrest team={t} size="md" />
                    </span>
                    <span className="mt-5 flex items-end justify-between gap-2">
                      <span>
                        <span className="label block">Squad value</span>
                        <span className="mt-1 block text-xl text-crimson-light font-mono tabular-nums">{t.total_market_value}</span>
                      </span>
                    </span>
                    {(t.players_count != null || t.average_age != null) && (
                      <span className="mt-2 block text-[11px] text-ink-mute font-mono tabular-nums">
                        {[t.players_count != null && `${t.players_count} ${t.players_count === 1 ? 'player' : 'players'}`, t.average_age != null && `Ø ${t.average_age.toFixed(1)} yrs`].filter(Boolean).join(' · ')}
                      </span>
                    )}
                  </span>
                </button>
              </Card>
            )
          })}
        </Strip>
      )}

      {stars.length > 0 && (
        <Strip label="Players">
          {stars.map((p, i) => (
            <Card key={p.id} i={i} className="w-44 lg:w-[calc((100%-4.5rem)/7)]">
              <button type="button" onClick={() => onOpenPlayer(p)} className="group flex h-full w-full flex-col text-left">
                <span className="relative block aspect-[4/5] w-full overflow-hidden bg-[radial-gradient(90%_70%_at_50%_100%,rgba(225,29,72,0.14),transparent_70%)]">
                  <PlayerCutout
                    player={p}
                    team={p.current_team}
                    shirtNumber={p.shirt_number ?? p.current_team?.pivot.shirt_number}
                    className="absolute inset-x-0 bottom-0 h-[88%] w-full transition-transform duration-700 ease-out group-hover:scale-105"
                  />
                </span>
                <span className="flex items-center gap-2 border-t border-line px-3 py-2.5">
                  {p.current_team && <TeamCrest team={p.current_team} size="sm" />}
                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-sm font-medium text-ink">{cardName(p)}</span>
                    <span className="block text-sm text-crimson-light font-mono tabular-nums">{p.market_value}</span>
                  </span>
                </span>
              </button>
            </Card>
          ))}
        </Strip>
      )}
    </section>
  )
}

function Strip({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="mb-8">
      <p className="label mb-3">{label}</p>
      <div className="overflow-x-auto pb-2 [scrollbar-color:rgba(255,255,255,0.12)_transparent] [scrollbar-width:thin]">
        <ol className="flex snap-x snap-mandatory gap-3">{children}</ol>
      </div>
    </div>
  )
}

function Card({ i, className, children }: { i: number; className: string; children: ReactNode }) {
  return (
    <motion.li
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: Math.min(i, 8) * 0.04, duration: 0.45 }}
      className={`shrink-0 snap-start overflow-hidden rounded-2xl border border-line bg-surface transition duration-500 hover:-translate-y-1 hover:border-crimson/45 hover:shadow-[0_0_28px_rgba(200,30,69,0.25)] ${className}`}
    >
      {children}
    </motion.li>
  )
}
