import { AnimatePresence } from 'framer-motion'
import { lazy, Suspense, useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { API_BASE_URL, authApi, countriesApi, favoritesApi, onUnauthorized, playersApi, teamsApi, tokenStorage } from './api'
import CountryBar from './components/explorer/CountryBar'
import LeagueStep from './components/explorer/LeagueStep'
import Trending from './components/explorer/Trending'
import type { ComparePlayer } from './components/compare/CompareView'
import SquadStep from './components/explorer/SquadStep'
import StepSection from './components/explorer/StepSection'
import TeamsStep, { type StandingsState } from './components/explorer/TeamsStep'
import Flag from './components/Flag'
import type { AuthMode } from './components/LoginModal'
import Header, { type ApiStatus, type HomeTarget } from './components/Header'
import PlayerStage from './components/stage/PlayerStage'
import { FALLBACK_COUNTRIES, FALLBACK_PLAYERS, FALLBACK_TEAMS, fallbackTeams } from './data/fallback'
import { useApiData } from './hooks/useApiData'
import { EMPTY_ROUTE, stepOf, useExplorerRoute, type ExplorerRoute } from './hooks/useExplorerRoute'
import { useMyPage } from './hooks/useMyPage'
import { FEATURED_NATIONS, standingsOrder } from './lib/stats'
import type { FavoriteTeam, ID, Player, StagePlayer, Team, TeamRecord, User } from './types'

// Loaded on first use: most visits never open a comparison or My Page.
const CompareView = lazy(() => import('./components/compare/CompareView'))
const MyPage = lazy(() => import('./components/MyPage'))
const LoginModal = lazy(() => import('./components/LoginModal'))

const NO_PLAYERS: Player[] = []
const NO_TEAMS: Team[] = []
/** Placeholder record for a club whose standings haven't arrived (never shown: the table waits). */
const NO_RECORD: TeamRecord = { played: 0, wins: 0, draws: 0, losses: 0, goals_for: 0, goals_against: 0, clean_sheets: 0, points: 0 }

/** Guests' favourites live in the browser; signed-in users' on the server. Newest first. */
const FAVORITES_KEY = 'statkick_favorites'
const FAVORITE_TEAMS_KEY = 'statkick_favorite_teams'

const SECTION_IDS = { countries: 'step-country', leagues: 'step-league', teams: 'step-team', squad: 'step-squad' } as const

function readLocalFavorites(key: string = FAVORITES_KEY): ID[] {
  try {
    const raw = localStorage.getItem(key)
    const parsed: unknown = raw ? JSON.parse(raw) : null
    return Array.isArray(parsed) ? parsed.filter((n): n is number => typeof n === 'number') : []
  } catch {
    return []
  }
}

function writeLocal(key: string, ids: ID[]) {
  try {
    localStorage.setItem(key, JSON.stringify(ids))
  } catch {
    /* storage unavailable */
  }
}

/** Add or remove an id, newest first. */
const flip = (ids: ID[], id: ID) => (ids.includes(id) ? ids.filter((x) => x !== id) : [id, ...ids])
/** Set an id's membership from the server's answer, keeping order. */
const settle = (ids: ID[], id: ID, on: boolean) => (on ? (ids.includes(id) ? ids : [id, ...ids]) : ids.filter((x) => x !== id))

export default function App() {
  const { route, go, replace, closePlayer } = useExplorerRoute()
  const myPage = useMyPage()

  /* ---------- Data: navigation tree, the selected league's clubs, global lists ---------- */
  const countriesQ = useApiData((signal) => countriesApi.list({ signal }), { fallback: FALLBACK_COUNTRIES, initial: [] })
  const leagueTeamsQ = useApiData(
    (signal) => (route.league ? teamsApi.list({ division_id: route.league }, { signal }) : Promise.resolve([] as Team[])),
    { fallback: fallbackTeams(route.league), initial: [], key: route.league ?? 0 },
  )
  // Power search, favorites and the stage's instant first paint.
  const allPlayersQ = useApiData((signal) => playersApi.list({}, { signal }), { fallback: FALLBACK_PLAYERS, initial: [] })
  const allTeamsQ = useApiData((signal) => teamsApi.list({}, { signal }), { fallback: FALLBACK_TEAMS, initial: [] })

  /*
   * An API that answers with an *empty* tree is mid re-seed: keep the homepage alive
   * on demo data rather than showing a blank grid. Same for the global lists.
   */
  const liveEmpty = countriesQ.source === 'api' && countriesQ.data.length === 0
  const countries = liveEmpty ? FALLBACK_COUNTRIES : countriesQ.data
  // Clubs across the five featured leagues (footer figure), from whichever data is active.
  const leagueClubs = countries
    .filter((c) => FEATURED_NATIONS.some((n) => n.name === c.name))
    .reduce((n, c) => n + c.divisions.reduce((m, d) => m + d.teams.length, 0), 0)
  const allPlayers = allPlayersQ.source !== 'loading' && allPlayersQ.data.length === 0 ? FALLBACK_PLAYERS : allPlayersQ.data
  const allTeams = allTeamsQ.source !== 'loading' && allTeamsQ.data.length === 0 ? FALLBACK_TEAMS : allTeamsQ.data
  const country = countries.find((c) => c.id === route.country) ?? null
  const league = country?.divisions.find((d) => d.id === route.league) ?? null

  /*
   * The league's clubs are the tree's clubs, the same ones the league card shows, so
   * the list can never disagree with the card. The ?division_id= request only adds
   * each club's standings record, matched by id. Live tree: only live rows count (the
   * demo rows' ids mean other clubs); offline demo tree: the demo rows match it.
   */
  const liveTree = countriesQ.source === 'api' && !liveEmpty
  // Rows still from the previously selected league mean the new request is in flight.
  const leagueRowsPending =
    leagueTeamsQ.source === 'loading' || (leagueTeamsQ.source === 'api' && leagueTeamsQ.data.some((r) => r.division_id !== route.league))
  const leagueRows = leagueTeamsQ.source === 'api' || !liveTree ? leagueTeamsQ.data : NO_TEAMS
  const allTeamRows = allTeamsQ.source === 'api' ? allTeamsQ.data : NO_TEAMS
  const leagueTeams: Team[] =
    country && league
      ? league.teams.map(
          (t) =>
            leagueRows.find((r) => r.id === t.id) ??
            allTeamRows.find((r) => r.id === t.id) ?? { ...t, division: league, country, record: NO_RECORD, form: [] },
        )
      : NO_TEAMS
  const hasRecords = !!league && league.teams.every((t) => leagueRows.some((r) => r.id === t.id) || allTeamRows.some((r) => r.id === t.id))
  const standings: StandingsState = hasRecords ? 'ready' : leagueRowsPending ? 'loading' : 'unavailable'
  const teamRow = leagueTeams.find((t) => t.id === route.team) ?? null
  const teamInTree = league?.teams.find((t) => t.id === route.team) ?? null
  // League position for the dossier (a handful of clubs: cheap enough to compute each render).
  const rankIndex = hasRecords ? [...leagueTeams].sort(standingsOrder).findIndex((t) => t.id === route.team) : -1
  const teamRank = rankIndex >= 0 ? { position: rankIndex + 1, of: leagueTeams.length } : null

  /*
   * Self-healing after a re-seed: if the league's club request is rejected (a stale
   * league id) or returns clubs the tree doesn't list, the tree itself is stale.
   * Reload it and the club lists (up to three tries per league)...
   */
  const leagueOutOfSync =
    liveTree &&
    !!league &&
    !leagueRowsPending &&
    (leagueTeamsQ.source === 'fallback' ||
      leagueTeamsQ.data.length !== league.teams.length ||
      leagueTeamsQ.data.some((r) => !league.teams.some((t) => t.id === r.id)))
  const healAttempts = useRef(new Map<ID, number>())
  // The hook objects are new each render; their refresh functions are stable.
  const { refresh: refreshCountries } = countriesQ
  const { refresh: refreshLeagueTeams } = leagueTeamsQ
  const { refresh: refreshAllTeams } = allTeamsQ
  useEffect(() => {
    if (!leagueOutOfSync || route.league === null) return
    const attempt = healAttempts.current.get(route.league) ?? 0
    if (attempt >= 3) return
    healAttempts.current.set(route.league, attempt + 1)
    const id = window.setTimeout(() => {
      refreshCountries()
      refreshLeagueTeams()
      refreshAllTeams()
    }, attempt * 1500)
    return () => window.clearTimeout(id)
  }, [leagueOutOfSync, route.league, leagueTeamsQ.data, leagueTeamsQ.error, refreshCountries, refreshLeagueTeams, refreshAllTeams])

  // ...then move the URL to the same country / league / club by name under their new ids.
  const lastPlace = useRef<{ country: string; league: string | null; team: string | null } | null>(null)
  useEffect(() => {
    if (country) lastPlace.current = { country: country.name, league: league?.name ?? null, team: teamInTree?.short_code ?? null }
  }, [country, league, teamInTree])
  useEffect(() => {
    if (!liveTree || route.country === null) return
    const resolved = !!country && (route.league === null || !!league) && (route.team === null || !!teamInTree)
    if (resolved) return
    const place = lastPlace.current
    const c = place ? countries.find((x) => x.name === place.country) : undefined
    const d = c && place?.league ? c.divisions.find((x) => x.name === place.league) : undefined
    const t = d && place?.team ? d.teams.find((x) => x.short_code === place.team) : undefined
    // Unknown ids with nothing to map them to (e.g. an old shared link): back to the start, keeping any open player.
    replace(c ? { country: c.id, league: d?.id ?? null, team: t?.id ?? null, player: route.player } : { ...EMPTY_ROUTE, player: route.player })
  }, [liveTree, route, country, league, teamInTree, countries, replace])

  const apiStatus: ApiStatus =
    countriesQ.source === 'api' ? (liveEmpty ? 'syncing' : 'online') : countriesQ.source === 'fallback' ? 'offline' : 'checking'

  const retryAll = () => {
    countriesQ.refresh()
    leagueTeamsQ.refresh()
    allPlayersQ.refresh()
    allTeamsQ.refresh()
  }

  /* ---------- Navigation ---------- */
  const [scrollTarget, setScrollTarget] = useState<string | null>(null)
  const navigate = useCallback(
    (next: ExplorerRoute) => {
      go(next)
      // Explorer navigation leaves My Page; a player stage opens over it instead.
      if (!next.player) {
        myPage.leave()
        setScrollTarget(SECTION_IDS[stepOf(next)])
      }
    },
    [go, myPage],
  )

  // Bring a newly revealed step into view once it has rendered.
  useEffect(() => {
    if (!scrollTarget) return
    const id = window.requestAnimationFrame(() => {
      document.getElementById(scrollTarget)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      setScrollTarget(null)
    })
    return () => window.cancelAnimationFrame(id)
  }, [scrollTarget, country, league, teamInTree])

  /** Country → league → team path for a club, from the navigation tree. */
  const pathToTeam = useCallback(
    (teamId: ID): Pick<ExplorerRoute, 'country' | 'league' | 'team'> | null => {
      for (const c of countries) {
        for (const d of c.divisions) {
          if (d.teams.some((t) => t.id === teamId)) return { country: c.id, league: d.id, team: teamId }
        }
      }
      return null
    },
    [countries],
  )

  const openTeam = (teamId: ID) => {
    const path = pathToTeam(teamId)
    if (path) navigate({ ...path, player: null })
  }

  /** Open the 360° stage, carrying the player's club as context for "Back to squad". */
  const openPlayer = (p: Pick<Player, 'id' | 'current_team'>) => {
    const inThisSquad = p.current_team?.id != null && p.current_team.id === route.team
    const path = !inThisSquad && p.current_team ? pathToTeam(p.current_team.id) : null
    navigate({ ...(path ?? route), player: p.id })
  }

  /* ---------- Auth & favorites ---------- */
  const [user, setUser] = useState<User | null>(null)
  const [auth, setAuth] = useState<{ open: boolean; mode: AuthMode; n: number }>({ open: false, mode: 'signin', n: 0 })
  const openAuth = (mode: AuthMode = 'signin') => setAuth((a) => ({ open: true, mode, n: a.n + 1 }))
  const closeAuth = useCallback(() => setAuth((a) => ({ ...a, open: false })), [setAuth])
  const [favoriteIds, setFavoriteIds] = useState<ID[]>(() => readLocalFavorites(FAVORITES_KEY))
  const [favoriteTeamIds, setFavoriteTeamIds] = useState<ID[]>(() => readLocalFavorites(FAVORITE_TEAMS_KEY))

  /**
   * Signed-in users' favourites come from the server. Right after signing in or
   * registering, anything saved as a guest is added to the account first, so
   * nothing saved before creating an account is lost.
   */
  const loadServerFavorites = useCallback(async (mergeGuest: boolean) => {
    const f = await favoritesApi.list()
    let players = f.players.map((p) => p.id)
    let teams = f.teams.map((t) => t.id)
    if (mergeGuest) {
      const addPlayers = readLocalFavorites(FAVORITES_KEY).filter((id) => !players.includes(id))
      const addTeams = readLocalFavorites(FAVORITE_TEAMS_KEY).filter((id) => !teams.includes(id))
      const results = await Promise.allSettled([
        ...addPlayers.map((id) => favoritesApi.togglePlayer(id)),
        ...addTeams.map((id) => favoritesApi.toggleTeam(id)),
      ])
      for (const r of results) {
        if (r.status !== 'fulfilled') continue
        const { player_id, team_id, is_favorite } = r.value
        if (player_id != null) players = settle(players, player_id, is_favorite)
        if (team_id != null) teams = settle(teams, team_id, is_favorite)
      }
      writeLocal(FAVORITES_KEY, [])
      writeLocal(FAVORITE_TEAMS_KEY, [])
    }
    setFavoriteIds(players)
    setFavoriteTeamIds(teams)
  }, [])

  const applyUser = useCallback(
    (u: User | null, mergeGuest = false) => {
      setUser(u)
      if (u) loadServerFavorites(mergeGuest).catch(() => {})
    },
    [loadServerFavorites],
  )

  useEffect(() => {
    const controller = new AbortController()
    if (tokenStorage.get()) {
      authApi
        .me({ signal: controller.signal })
        .then((u) => applyUser(u))
        .catch(() => {
          /* an invalid token is cleared by the client on 401 */
        })
    }
    const off = onUnauthorized(() => applyUser(null))
    return () => {
      controller.abort()
      off()
    }
  }, [applyUser])

  const logout = () => {
    authApi
      .logout()
      .catch(() => {})
      .finally(() => {
        applyUser(null)
        setFavoriteIds(readLocalFavorites(FAVORITES_KEY))
        setFavoriteTeamIds(readLocalFavorites(FAVORITE_TEAMS_KEY))
        if (myPage.open) myPage.close()
      })
  }

  // Guests' lists persist in the browser (the server is the source of truth when signed in).
  useEffect(() => {
    if (!user) writeLocal(FAVORITES_KEY, favoriteIds)
  }, [favoriteIds, user])
  useEffect(() => {
    if (!user) writeLocal(FAVORITE_TEAMS_KEY, favoriteTeamIds)
  }, [favoriteTeamIds, user])

  const toggleFavorite = useCallback(
    (p: Pick<Player, 'id'>) => {
      setFavoriteIds((ids) => flip(ids, p.id))
      if (user) {
        favoritesApi
          .togglePlayer(p.id)
          .then(({ is_favorite }) => setFavoriteIds((ids) => settle(ids, p.id, is_favorite)))
          .catch(() => setFavoriteIds((ids) => flip(ids, p.id))) // undo the optimistic change
      }
    },
    [user],
  )

  const toggleFavoriteTeam = useCallback(
    (teamId: ID) => {
      setFavoriteTeamIds((ids) => flip(ids, teamId))
      if (user) {
        favoritesApi
          .toggleTeam(teamId)
          .then(({ is_favorite }) => setFavoriteTeamIds((ids) => settle(ids, teamId, is_favorite)))
          .catch(() => setFavoriteTeamIds((ids) => flip(ids, teamId)))
      }
    },
    [user],
  )

  /*
   * Saved items are drawn from the live club/player lists (never the demo data, whose
   * ids mean different players). Anything those lists don't hold, e.g. when the large
   * player list failed on a slow connection, is fetched on its own, so a saved
   * player or club always shows. Order is the saved order: newest first.
   */
  const livePlayers = allPlayersQ.source === 'api' ? allPlayers : NO_PLAYERS
  const liveTeams = allTeamsQ.source === 'api' ? allTeams : NO_TEAMS
  const listsSettled = allPlayersQ.source !== 'loading' && allTeamsQ.source !== 'loading'
  // null = looked up and not found / failed (not retried, not shown).
  const [fetchedPlayers, setFetchedPlayers] = useState<ReadonlyMap<ID, StagePlayer | null>>(new Map())
  const [fetchedTeams, setFetchedTeams] = useState<ReadonlyMap<ID, FavoriteTeam | null>>(new Map())
  const missingPlayers = listsSettled ? favoriteIds.filter((id) => !fetchedPlayers.has(id) && !livePlayers.some((p) => p.id === id)).join(',') : ''
  const missingTeams = listsSettled ? favoriteTeamIds.filter((id) => !fetchedTeams.has(id) && !liveTeams.some((t) => t.id === id)).join(',') : ''

  useEffect(() => {
    if (!missingPlayers) return
    const controller = new AbortController()
    Promise.allSettled(missingPlayers.split(',').map((id) => playersApi.show(Number(id), { signal: controller.signal }))).then((results) => {
      if (controller.signal.aborted) return
      setFetchedPlayers((prev) => {
        const next = new Map(prev)
        missingPlayers.split(',').forEach((id, i) => {
          const r = results[i]
          next.set(Number(id), r.status === 'fulfilled' ? r.value : null)
        })
        return next
      })
    })
    return () => controller.abort()
  }, [missingPlayers])

  useEffect(() => {
    if (!missingTeams) return
    const controller = new AbortController()
    Promise.allSettled(missingTeams.split(',').map((id) => teamsApi.show(Number(id), { signal: controller.signal }))).then((results) => {
      if (controller.signal.aborted) return
      setFetchedTeams((prev) => {
        const next = new Map(prev)
        missingTeams.split(',').forEach((id, i) => {
          const r = results[i]
          next.set(Number(id), r.status === 'fulfilled' ? r.value : null)
        })
        return next
      })
    })
    return () => controller.abort()
  }, [missingTeams])

  const favorites = useMemo(
    () =>
      favoriteIds
        .map((id) => livePlayers.find((p) => p.id === id) ?? fetchedPlayers.get(id))
        .filter((p): p is StagePlayer => !!p),
    [livePlayers, fetchedPlayers, favoriteIds],
  )
  const favoriteTeams = useMemo(
    () =>
      favoriteTeamIds
        .map((id) => liveTeams.find((t) => t.id === id) ?? fetchedTeams.get(id))
        .filter((t): t is FavoriteTeam => !!t),
    [liveTeams, fetchedTeams, favoriteTeamIds],
  )

  /* ---------- Head-to-head comparison ---------- */
  // The left player is kept as given (the stage may hold the richer detail payload).
  const [compare, setCompare] = useState<{ a: ComparePlayer; b: ID | null } | null>(null)
  const openCompare = (p: ComparePlayer) => setCompare({ a: allPlayers.find((x) => x.id === p.id) ?? p, b: null })
  const closeCompare = useCallback(() => setCompare(null), [setCompare])
  const compareB = compare?.b != null ? (allPlayers.find((p) => p.id === compare.b) ?? null) : null
  const stageInitial: StagePlayer | null = allPlayers.find((p) => p.id === route.player) ?? null


  const scrollToSection = (target: HomeTarget) => {
    const id = { countries: SECTION_IDS.countries, trending: 'trending' }[target]
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <div className="min-h-screen">
      <Header
        players={allPlayers}
        teams={allTeams}
        favorites={favorites}
        favoriteTeams={favoriteTeams}
        user={user}
        onHome={() => navigate(EMPTY_ROUTE)}
        onNavigate={scrollToSection}
        onSelectPlayer={openPlayer}
        onSelectTeam={(t) => openTeam(t.id)}
        onLogin={() => openAuth('signin')}
        onOpenMyPage={myPage.show}
      />

      <main className="mx-auto w-full max-w-[1340px] px-6 pt-2">
        {(apiStatus === 'offline' || apiStatus === 'syncing') && (
          <div role="status" className="mb-8 flex flex-wrap items-center gap-4 rounded-xl border border-line bg-surface px-5 py-3 text-sm">
            <span className="min-w-0 flex-1">
              {apiStatus === 'offline' ? (
                <>
                  The StatKick API at <code className="break-all">{API_BASE_URL}</code> is unreachable — showing demo data.
                </>
              ) : (
                <>The StatKick API returned no data (the database may be re-seeding) — showing demo data meanwhile.</>
              )}
            </span>
            <button type="button" onClick={retryAll} className="text-sm font-medium underline underline-offset-4">
              Retry
            </button>
          </div>
        )}

        {myPage.open && user ? (
          <Suspense fallback={<div className="h-[60vh]" />}>
            <MyPage
              user={user}
              teams={favoriteTeams}
              players={favorites}
              loading={!listsSettled || missingPlayers !== '' || missingTeams !== ''}
              onBack={myPage.close}
              onOpenTeam={(t) => openTeam(t.id)}
              onOpenPlayer={openPlayer}
              onComparePlayer={openCompare}
              onRemoveTeam={(t) => toggleFavoriteTeam(t.id)}
              onRemovePlayer={toggleFavorite}
              onSignOut={logout}
            />
          </Suspense>
        ) : (
          <>
          {/* 01 — Country (the landing view) */}
          <StepSection id={SECTION_IDS.countries} title="Nations & Leagues" rule={false}>
            <CountryBar
              countries={countries}
              syncing={countriesQ.source === 'loading' || liveEmpty}
              selectedId={route.country}
              onSelect={(c) => navigate({ ...EMPTY_ROUTE, country: c.id })}
            />
          </StepSection>

          <AnimatePresence mode="popLayout">
            {/* 02 — League */}
            {country && (
              <StepSection
                key={`league-${country.id}`}
                id={SECTION_IDS.leagues}
                label="League"
                title={
                  <span className="flex items-center gap-4">
                    <Flag code={country.code} name={country.name} className="h-7 w-10 rounded-[3px] ring-1 ring-white/10" />
                    {country.name}
                  </span>
                }
              >
                <LeagueStep country={country} selectedId={route.league} onSelect={(d) => navigate({ ...EMPTY_ROUTE, country: country.id, league: d.id })} />
              </StepSection>
            )}

            {/* 03 — League table & clubs */}
            {country && league && (
              <StepSection key={`teams-${league.id}`} id={SECTION_IDS.teams} label="League & clubs" title={league.name}>
                <TeamsStep
                  leagueName={league.name}
                  teams={leagueTeams}
                  loading={leagueRowsPending}
                  standings={standings}
                  selectedId={route.team}
                  onSelect={(t) => navigate({ country: country.id, league: league.id, team: t.id, player: null })}
                />
              </StepSection>
            )}

            {/* 04 — Club dossier & squad */}
            {country && league && teamInTree && (
              <StepSection key={`squad-${teamInTree.id}`} id={SECTION_IDS.squad} label="Club dossier & squad" title="Club dossier">
                <SquadStep
                  teamId={teamInTree.id}
                  team={teamRow}
                  rank={teamRank}
                  onOpenPlayer={openPlayer}
                  onComparePlayer={openCompare}
                  favoritePlayerIds={favoriteIds}
                  onTogglePlayerFavorite={toggleFavorite}
                  isTeamFavorite={favoriteTeamIds.includes(teamInTree.id)}
                  onToggleTeamFavorite={() => toggleFavoriteTeam(teamInTree.id)}
                />
              </StepSection>
            )}
          </AnimatePresence>

          {/* Live market strips: always on the homepage, below the drill-down */}
          <Trending teams={allTeams} players={allPlayers} onOpenTeam={(t) => openTeam(t.id)} onOpenPlayer={openPlayer} />
          </>
        )}
      </main>

      <footer className="mx-auto mt-16 w-full max-w-[1340px] px-6">
        <div className="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-t border-line py-8 text-[13px] text-ink-mute">
          <p className="flex items-center gap-3">
            <span className="font-display text-sm font-semibold tracking-[0.14em] text-ink">
              STAT<span className="text-crimson">KICK</span>
            </span>
            <span aria-hidden className="h-3.5 w-px bg-line" />
            <span>European Football Intelligence</span>
          </p>
          <p className="flex flex-wrap items-center gap-x-2">
            <span>Top 5 Leagues</span>
            <span aria-hidden className="text-ink-faint">·</span>
            <span>{leagueClubs ? `${leagueClubs} Elite Clubs` : 'Elite Clubs'}</span>
            <span aria-hidden className="text-ink-faint">·</span>
            <span>© {new Date().getFullYear()} StatKick</span>
          </p>
        </div>
      </footer>

      {/* 360° player stage (URL-driven: ?player=) */}
      <AnimatePresence>
        {route.player && (
          <PlayerStage
            key={route.player}
            playerId={route.player}
            initial={stageInitial}
            backLabel={route.team ? 'Back to squad' : 'Back'}
            isFavorite={favoriteIds.includes(route.player)}
            onToggleFavorite={toggleFavorite}
            onCompare={openCompare}
            keysPaused={compare !== null}
            onBack={closePlayer}
          />
        )}
      </AnimatePresence>

      {/* Head-to-head comparison, over the page or the stage */}
      <Suspense fallback={null}>
        <AnimatePresence>
          {compare && (
            <CompareView
              players={allPlayers}
              a={compare.a}
              b={compareB}
              onPick={(id) => setCompare((c) => (c ? { ...c, b: id } : c))}
              onSwap={() => compareB && setCompare({ a: compareB, b: compare.a.id })}
              onOpenPlayer={(p) => {
                setCompare(null)
                openPlayer(p)
              }}
              onClose={closeCompare}
            />
          )}
        </AnimatePresence>
      </Suspense>

      {/* Fetched the first time someone opens it; stays mounted afterwards for its exit animation. */}
      {auth.n > 0 && (
        <Suspense fallback={null}>
          <LoginModal key={auth.n} open={auth.open} initialMode={auth.mode} onClose={closeAuth} onSuccess={(u) => applyUser(u, true)} />
        </Suspense>
      )}
    </div>
  )
}
