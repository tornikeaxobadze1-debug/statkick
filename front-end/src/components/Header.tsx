import { AnimatePresence, motion } from 'framer-motion'
import { ArrowUpRight, Heart, Search } from 'lucide-react'
import { useEffect, useMemo, useRef, useState, type ReactNode, type RefObject } from 'react'
import { playerName } from '../lib/stats'
import type { FavoriteTeam, Player, StagePlayer, Team, User } from '../types'
import Avatar from './Avatar'
import PlayerCutout from './PlayerCutout'
import TeamCrest from './TeamCrest'

/** online = live API data · syncing = API up but empty (re-seed), demo shown · offline = API unreachable. */
export type ApiStatus = 'checking' | 'online' | 'syncing' | 'offline'

export type HomeTarget = 'countries' | 'trending'

interface HeaderProps {
  players: Player[]
  teams: Team[]
  favorites: StagePlayer[]
  favoriteTeams: FavoriteTeam[]
  user: User | null
  onHome: () => void
  onNavigate: (target: HomeTarget) => void
  onSelectPlayer: (player: Pick<Player, 'id' | 'current_team'>) => void
  onSelectTeam: (team: Pick<Team, 'id'>) => void
  onLogin: () => void
  /** Opens the signed-in user's My Page. */
  onOpenMyPage: () => void
}

type OpenMenu = 'search' | 'favorites' | null

const IS_MAC = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.userAgent)

function useClickOutside(ref: RefObject<HTMLElement | null>, onOutside: () => void) {
  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) onOutside()
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [ref, onOutside])
}

export default function Header(props: HeaderProps) {
  const { players, teams, favorites, favoriteTeams, user, onHome, onNavigate, onSelectPlayer, onSelectTeam, onLogin, onOpenMyPage } = props
  const savedCount = favorites.length + favoriteTeams.length
  const [open, setOpen] = useState<OpenMenu>(null)
  const [query, setQuery] = useState('')
  const [active, setActive] = useState(0)
  const rootRef = useRef<HTMLElement>(null)
  const inputRef = useRef<HTMLInputElement>(null)

  const close = useMemo(() => () => setOpen(null), [])
  useClickOutside(rootRef, close)

  // ⌘K / Ctrl+K (and "/") focus search from anywhere.
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const el = e.target as HTMLElement
      const typing = el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable
      if ((e.key.toLowerCase() === 'k' && (e.metaKey || e.ctrlKey)) || (e.key === '/' && !typing)) {
        e.preventDefault()
        inputRef.current?.focus()
        inputRef.current?.select()
        setOpen('search')
      }
      if (e.key === 'Escape') setOpen(null)
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [])

  const results = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return [] as ({ kind: 'team'; team: Team } | { kind: 'player'; player: Player })[]
    const t = teams.filter((x) => x.name.toLowerCase().includes(q) || x.short_code.toLowerCase() === q).slice(0, 4)
    const p = players.filter((x) => playerName(x).toLowerCase().includes(q)).slice(0, 6)
    return [...t.map((team) => ({ kind: 'team' as const, team })), ...p.map((player) => ({ kind: 'player' as const, player }))]
  }, [query, players, teams])

  const pick = (i: number) => {
    const r = results[i]
    if (!r) return
    if (r.kind === 'team') onSelectTeam(r.team)
    else onSelectPlayer(r.player)
    setQuery('')
    setOpen(null)
    inputRef.current?.blur()
  }

  const links: { label: string; onClick: () => void }[] = [
    { label: 'Nations', onClick: () => onNavigate('countries') },
    { label: 'Trending', onClick: () => onNavigate('trending') },
  ]

  return (
    <header ref={rootRef} className="sticky top-0 z-40 bg-[rgba(7,6,8,0.78)] backdrop-blur-xl">
      <nav className="mx-auto flex h-16 w-full max-w-[1340px] items-center gap-3 px-6 sm:gap-6 lg:gap-10" aria-label="Main">
        {/* Emblem + wordmark */}
        <button type="button" onClick={onHome} className="group flex shrink-0 items-center gap-2.5" aria-label="StatKick home">
          <Emblem />
          {/* Phones show the emblem alone, leaving room for search */}
          <span className="font-display hidden text-[17px] font-semibold tracking-[0.14em] text-ink sm:inline">
            STAT<span className="text-crimson">KICK</span>
          </span>
        </button>

        <ul className="hidden items-center gap-6 lg:flex">
          {links.map((l) => (
            <li key={l.label}>
              <button type="button" onClick={l.onClick} className="relative text-sm text-ink-mute transition-colors hover:text-ink after:absolute after:-bottom-1.5 after:left-0 after:h-px after:w-0 after:bg-crimson after:transition-all hover:after:w-full">
                {l.label}
              </button>
            </li>
          ))}
        </ul>

        {/* Search pill */}
        <div className="relative mx-auto w-full max-w-md min-w-0 flex-1">
          <label className="group relative block">
            <span className="sr-only">Search players and clubs</span>
            <Search className="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-ink-mute transition-colors group-focus-within:text-crimson" aria-hidden />
            <input
              ref={inputRef}
              type="search"
              role="combobox"
              aria-expanded={open === 'search' && results.length > 0}
              aria-controls="search-results"
              value={query}
              onChange={(e) => {
                setQuery(e.target.value)
                setActive(0)
                setOpen('search')
              }}
              onFocus={() => setOpen('search')}
              onKeyDown={(e) => {
                if (e.key === 'ArrowDown') {
                  e.preventDefault()
                  setActive((a) => Math.min(results.length - 1, a + 1))
                } else if (e.key === 'ArrowUp') {
                  e.preventDefault()
                  setActive((a) => Math.max(0, a - 1))
                } else if (e.key === 'Enter') {
                  e.preventDefault()
                  pick(active)
                }
              }}
              placeholder="Search players, clubs…"
              className="h-10 w-full rounded-full border border-line bg-surface/80 pr-3 pl-10 text-sm sm:pr-16 text-ink caret-crimson outline-none transition placeholder:text-ink-mute hover:border-[rgba(255,255,255,0.16)] focus:border-crimson/60 focus:shadow-[0_0_0_4px_rgba(225,29,72,0.08)]"
            />
            <kbd className={`pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 rounded-md border border-line bg-canvas px-1.5 py-0.5 font-sans text-[10px] text-ink-mute ${query ? 'hidden' : 'hidden sm:block'}`}>
              {IS_MAC ? '⌘K' : 'Ctrl K'}
            </kbd>
          </label>
          <AnimatePresence>
            {open === 'search' && query.trim() && (
              <Dropdown id="search-results" className="right-0 left-0">
                {results.length === 0 && <p className="px-4 py-4 text-sm text-ink-mute">No players or clubs match “{query.trim()}”.</p>}
                {results.map((r, i) => (
                  <Row key={r.kind === 'team' ? `t${r.team.id}` : `p${r.player.id}`} active={i === active} onHover={() => setActive(i)} onClick={() => pick(i)}>
                    {r.kind === 'team' ? (
                      <>
                        <TeamCrest team={r.team} size="sm" />
                        <span className="min-w-0 flex-1 truncate">{r.team.name}</span>
                        <span className="text-xs text-ink-mute">Club · {r.team.division.name}</span>
                      </>
                    ) : (
                      <>
                        <span className="relative h-7 w-7 shrink-0 overflow-hidden rounded-full bg-canvas">
                          <PlayerCutout player={r.player} team={r.player.current_team} className="absolute inset-x-0 -bottom-0.5 h-8 w-full" />
                        </span>
                        <span className="min-w-0 flex-1 truncate">{playerName(r.player)}</span>
                        <span className="text-xs text-ink-mute">{r.player.market_value ?? r.player.current_team?.short_code}</span>
                      </>
                    )}
                  </Row>
                ))}
              </Dropdown>
            )}
          </AnimatePresence>
        </div>

        {/* Right cluster */}
        <div className="flex shrink-0 items-center gap-2 sm:gap-3">
          <div className="relative">
            <button
              type="button"
              onClick={() => setOpen((o) => (o === 'favorites' ? null : 'favorites'))}
              aria-expanded={open === 'favorites'}
              aria-label={`Saved clubs and players (${savedCount})`}
              className="flex h-10 items-center gap-2 rounded-full border border-line px-3.5 text-sm text-ink-mute transition hover:border-crimson/45 hover:text-ink"
            >
              <Heart className={`h-4 w-4 ${savedCount ? 'text-crimson' : ''}`} fill={savedCount ? 'currentColor' : 'none'} aria-hidden />
              <span className="hidden md:inline">Saved</span>
              {savedCount > 0 && (
                <motion.span key={savedCount} initial={{ scale: 0.6 }} animate={{ scale: 1 }} className="font-mono text-xs text-ink tabular-nums">
                  {savedCount}
                </motion.span>
              )}
            </button>
            <AnimatePresence>
              {open === 'favorites' && (
                <Dropdown className="right-0 w-72">
                  {savedCount === 0 ? (
                    <p className="px-4 py-4 text-sm text-ink-mute">Nothing saved yet. Use the heart on a club or player to save it.</p>
                  ) : (
                    <>
                      {favoriteTeams.length > 0 && <p className="label px-3 pt-2 pb-1">Clubs</p>}
                      {favoriteTeams.map((t) => (
                        <Row
                          key={`t${t.id}`}
                          onClick={() => {
                            onSelectTeam(t)
                            setOpen(null)
                          }}
                        >
                          <TeamCrest team={t} size="sm" />
                          <span className="min-w-0 flex-1 truncate">{t.name}</span>
                          <span className="text-xs text-ink-mute">{t.short_code}</span>
                        </Row>
                      ))}
                      {favorites.length > 0 && <p className="label px-3 pt-2 pb-1">Players</p>}
                      {favorites.map((p) => (
                        <Row
                          key={`p${p.id}`}
                          onClick={() => {
                            onSelectPlayer(p)
                            setOpen(null)
                          }}
                        >
                          <span className="min-w-0 flex-1 truncate">{playerName(p)}</span>
                          <span className="text-xs text-ink-mute">{p.current_team?.short_code}</span>
                        </Row>
                      ))}
                    </>
                  )}
                  <div className="mt-1 border-t border-line pt-1">
                    {user ? (
                      <Row
                        onClick={() => {
                          onOpenMyPage()
                          setOpen(null)
                        }}
                      >
                        <span className="flex-1 text-crimson-light">Open My Page</span>
                        <ArrowUpRight className="h-4 w-4 text-crimson-light" aria-hidden />
                      </Row>
                    ) : (
                      <Row
                        onClick={() => {
                          onLogin()
                          setOpen(null)
                        }}
                      >
                        <span className="flex-1 text-ink-mute">
                          <span className="text-crimson-light">Create an account</span> to keep these on every device
                        </span>
                      </Row>
                    )}
                  </div>
                </Dropdown>
              )}
            </AnimatePresence>
          </div>

          {user ? (
            <button
              type="button"
              onClick={onOpenMyPage}
              aria-label={`My Page: ${user.name}`}
              className="flex h-10 items-center gap-2 rounded-full border border-crimson/30 py-1 pr-1.5 pl-1.5 text-sm font-medium text-ink transition hover:border-crimson/70 sm:pr-4"
            >
              <Avatar name={user.name} />
              <span className="hidden max-w-[9rem] truncate sm:inline">{user.name.split(' ')[0]}</span>
            </button>
          ) : (
            <button
              type="button"
              onClick={onLogin}
              className="flex h-10 items-center rounded-full border border-crimson/30 px-4 text-sm font-medium whitespace-nowrap text-ink transition hover:border-crimson/70 hover:text-crimson"
            >
              Sign in
            </button>
          )}
        </div>
      </nav>
      {/* Gradient hairline */}
      <div aria-hidden className="h-px bg-gradient-to-r from-transparent via-crimson/45 to-transparent" />
    </header>
  )
}

/** Geometric emblem: a hexagonal ball panel inside a slanted frame. */
function Emblem() {
  return (
    <svg viewBox="0 0 32 32" className="h-8 w-8 transition-transform duration-500 group-hover:rotate-[30deg]" aria-hidden>
      <defs>
        <linearGradient id="sk-emblem" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor="#fb7185" />
          <stop offset="1" stopColor="#7f1028" />
        </linearGradient>
      </defs>
      <path d="M16 1.5 29 9v14L16 30.5 3 23V9z" fill="none" stroke="url(#sk-emblem)" strokeWidth="1.6" />
      <path d="M16 9.5l5.6 4.1-2.1 6.6h-7l-2.1-6.6z" fill="url(#sk-emblem)" />
      <path d="M16 9.5V4.5M21.6 13.6l4.6-1.6M19.5 20.2l2.9 4M12.5 20.2l-2.9 4M10.4 13.6 5.8 12" stroke="#f4f6f8" strokeOpacity="0.55" strokeWidth="1.1" strokeLinecap="round" />
    </svg>
  )
}

function Dropdown({ id, className = '', children }: { id?: string; className?: string; children: ReactNode }) {
  return (
    <motion.div
      id={id}
      role="listbox"
      initial={{ opacity: 0, y: -6 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -6 }}
      transition={{ duration: 0.15 }}
      className={`absolute top-full mt-3 max-h-96 overflow-y-auto rounded-2xl border border-line bg-surface/95 p-1.5 shadow-[0_30px_60px_-20px_rgba(0,0,0,0.9)] backdrop-blur-xl ${className}`}
    >
      {children}
    </motion.div>
  )
}

function Row({ onClick, onHover, active = false, children }: { onClick: () => void; onHover?: () => void; active?: boolean; children: ReactNode }) {
  return (
    <button
      type="button"
      role="option"
      aria-selected={active}
      onClick={onClick}
      onMouseEnter={onHover}
      className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-colors ${active ? 'bg-surface-hover' : 'hover:bg-surface-hover'}`}
    >
      {children}
    </button>
  )
}
