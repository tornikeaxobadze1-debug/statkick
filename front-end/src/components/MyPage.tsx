import { AnimatePresence, motion } from 'framer-motion'
import { ArrowLeft, ArrowRightLeft, ArrowUpRight, Heart, LogOut, X } from 'lucide-react'
import type { ReactNode } from 'react'
import { formatMonthYear, playerName } from '../lib/stats'
import type { FavoriteTeam, StagePlayer, User } from '../types'
import Avatar from './Avatar'
import Flag from './Flag'
import PlayerCutout from './PlayerCutout'
import TeamCrest from './TeamCrest'
import { Skeleton } from './ui'

interface MyPageProps {
  user: User
  teams: FavoriteTeam[]
  players: StagePlayer[]
  /** True while the club/player lists the favourites are drawn from are still loading. */
  loading: boolean
  onBack: () => void
  onOpenTeam: (team: FavoriteTeam) => void
  onOpenPlayer: (player: StagePlayer) => void
  onComparePlayer: (player: StagePlayer) => void
  onRemoveTeam: (team: FavoriteTeam) => void
  onRemovePlayer: (player: StagePlayer) => void
  onSignOut: () => void
}

/** The signed-in user's page: profile, favourite clubs and favourite players. */
export default function MyPage({
  user,
  teams,
  players,
  loading,
  onBack,
  onOpenTeam,
  onOpenPlayer,
  onComparePlayer,
  onRemoveTeam,
  onRemovePlayer,
  onSignOut,
}: MyPageProps) {
  const since = formatMonthYear(user.created_at)

  return (
    <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }} className="pt-8 pb-4">
      <button type="button" onClick={onBack} className="group mb-8 flex items-center gap-2 text-sm text-ink-mute transition hover:text-ink">
        <ArrowLeft className="h-4 w-4 transition-transform group-hover:-translate-x-0.5" aria-hidden /> Back to explorer
      </button>

      {/* Profile */}
      <header className="flex flex-wrap items-center gap-6 border-b border-line pb-8">
        <Avatar name={user.name} size="lg" />
        <div className="min-w-0 flex-1">
          <p className="label">My Page</p>
          <h1 className="font-display mt-1 truncate text-4xl font-medium tracking-tight sm:text-5xl">{user.name}</h1>
          <p className="mt-1.5 truncate text-sm text-ink-mute">
            {user.email}
            {since && <span className="text-ink-faint"> · Member since {since}</span>}
          </p>
        </div>
        <div className="flex items-center gap-6">
          <Count value={teams.length} label={teams.length === 1 ? 'Club' : 'Clubs'} />
          <Count value={players.length} label={players.length === 1 ? 'Player' : 'Players'} />
          <button
            type="button"
            onClick={onSignOut}
            className="flex h-10 items-center gap-2 rounded-full border border-line px-4 text-sm text-ink-soft transition hover:border-crimson/50 hover:text-ink"
          >
            <LogOut className="h-4 w-4" aria-hidden /> Sign out
          </button>
        </div>
      </header>

      {/* Favourite clubs */}
      <Section title="Favorite Clubs" count={teams.length}>
        {loading && !teams.length ? (
          <Skeletons className="h-36" n={3} cols="sm:grid-cols-2 lg:grid-cols-3" />
        ) : teams.length === 0 ? (
          <Empty>No favorite clubs yet. Open a club and tap the heart next to its name to save it here.</Empty>
        ) : (
          <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <AnimatePresence initial={false}>
              {teams.map((t) => (
                <Card key={t.id}>
                  <div className="flex items-start gap-4 p-5">
                    <TeamCrest team={t} size="lg" />
                    <div className="min-w-0 flex-1">
                      <p className="font-display truncate text-xl font-medium">{t.name}</p>
                      <p className="mt-0.5 flex items-center gap-2 truncate text-xs text-ink-mute">
                        <Flag code={t.country?.code} name={t.country?.name ?? ''} className="h-3 w-4 shrink-0 rounded-[2px] ring-1 ring-white/15" />
                        {t.division?.name} · {t.country?.name}
                      </p>
                      {t.players_count != null && <p className="mt-2 font-mono text-xs text-ink-faint tabular-nums">{t.players_count} players</p>}
                    </div>
                    <RemoveButton label={`Remove ${t.name} from favorites`} onClick={() => onRemoveTeam(t)} />
                  </div>
                  <div className="border-t border-line px-5 py-3">
                    <Action onClick={() => onOpenTeam(t)} icon={<ArrowUpRight className="h-3.5 w-3.5" aria-hidden />}>
                      Open squad
                    </Action>
                  </div>
                </Card>
              ))}
            </AnimatePresence>
          </ul>
        )}
      </Section>

      {/* Favourite players */}
      <Section title="Favorite Players" count={players.length}>
        {loading && !players.length ? (
          <Skeletons className="h-80" n={4} cols="sm:grid-cols-2 lg:grid-cols-4" />
        ) : players.length === 0 ? (
          <Empty>No favorite players yet. Tap the heart on a squad row or a 360° profile to save players here.</Empty>
        ) : (
          <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <AnimatePresence initial={false}>
              {players.map((p) => (
                <Card key={p.id}>
                  <div className="relative aspect-[4/3] bg-[radial-gradient(90%_70%_at_50%_100%,rgba(225,29,72,0.14),transparent_70%)]">
                    <PlayerCutout player={p} team={p.current_team} className="absolute inset-x-0 bottom-0 h-[90%] w-full" />
                    {p.current_team && (
                      <span className="absolute top-3 left-3">
                        <TeamCrest team={p.current_team} size="sm" />
                      </span>
                    )}
                    <span className="absolute top-2 right-2">
                      <RemoveButton label={`Remove ${playerName(p)} from favorites`} onClick={() => onRemovePlayer(p)} />
                    </span>
                  </div>
                  <div className="border-t border-line px-4 pt-3 pb-4">
                    <p className="font-display truncate text-lg font-medium">{playerName(p)}</p>
                    <p className="truncate text-xs text-ink-mute">
                      {p.primary_position ?? p.position} · {p.current_team?.name ?? 'Free agent'}
                    </p>
                    <p className="mt-2 font-mono text-sm text-crimson-light tabular-nums">
                      {p.market_value ?? <span className="font-sans text-xs text-ink-faint">Value undisclosed</span>}
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      <Action onClick={() => onOpenPlayer(p)} icon={<ArrowUpRight className="h-3.5 w-3.5" aria-hidden />}>
                        360° profile
                      </Action>
                      <Action onClick={() => onComparePlayer(p)} icon={<ArrowRightLeft className="h-3.5 w-3.5" aria-hidden />}>
                        Compare
                      </Action>
                    </div>
                  </div>
                </Card>
              ))}
            </AnimatePresence>
          </ul>
        )}
      </Section>
    </motion.div>
  )
}

function Count({ value, label }: { value: number; label: string }) {
  return (
    <div className="text-right">
      <p className="font-mono text-2xl leading-none tabular-nums">{value}</p>
      <p className="mt-1 text-xs text-ink-mute">{label}</p>
    </div>
  )
}

function Section({ title, count, children }: { title: string; count: number; children: ReactNode }) {
  return (
    <section className="mt-12" aria-label={title}>
      <h2 className="font-display mb-5 flex items-baseline gap-3 text-2xl font-medium tracking-tight sm:text-3xl">
        {title}
        <span className="font-mono text-sm text-ink-mute tabular-nums">{count}</span>
      </h2>
      {children}
    </section>
  )
}

function Card({ children }: { children: ReactNode }) {
  return (
    <motion.li
      layout
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, scale: 0.96, transition: { duration: 0.2 } }}
      transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
      className="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-colors hover:border-crimson/40"
    >
      {children}
    </motion.li>
  )
}

function Action({ onClick, icon, children }: { onClick: () => void; icon: ReactNode; children: ReactNode }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className="flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-xs text-ink-soft transition hover:border-crimson/50 hover:text-ink"
    >
      {icon}
      {children}
    </button>
  )
}

function RemoveButton({ label, onClick }: { label: string; onClick: () => void }) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-label={label}
      title="Remove from favorites"
      className="group/remove flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-line bg-canvas/70 text-crimson backdrop-blur transition hover:border-crimson/60"
    >
      <Heart className="h-3.5 w-3.5 group-hover/remove:hidden" fill="currentColor" aria-hidden />
      <X className="hidden h-3.5 w-3.5 text-ink group-hover/remove:block" aria-hidden />
    </button>
  )
}

function Empty({ children }: { children: ReactNode }) {
  return <p className="rounded-2xl border border-dashed border-line px-6 py-10 text-center text-sm text-ink-mute">{children}</p>
}

function Skeletons({ n, className, cols }: { n: number; className: string; cols: string }) {
  return (
    <div className={`grid gap-3 ${cols}`}>
      {Array.from({ length: n }, (_, i) => (
        <Skeleton key={i} className={`rounded-2xl ${className}`} />
      ))}
    </div>
  )
}
