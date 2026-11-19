import { useCallback, useEffect, useState } from 'react'
import type { ID } from '../types'

/** Where the user is: Country → League → Club (→ Player stage). */
export interface ExplorerRoute {
  country: ID | null
  league: ID | null
  team: ID | null
  /** When set, the full-viewport 360° player stage is open. */
  player: ID | null
}

export type ExplorerStep = 'countries' | 'leagues' | 'teams' | 'squad'

export const EMPTY_ROUTE: ExplorerRoute = { country: null, league: null, team: null, player: null }

const KEYS = ['country', 'league', 'team', 'player'] as const

/** History entries we pushed for the stage, so "Back to squad" can use real browser back. */
const STAGE_STATE = 'statkick-stage'

function readRoute(): ExplorerRoute {
  const q = new URLSearchParams(window.location.search)
  const num = (key: string) => {
    const n = Number(q.get(key))
    return Number.isInteger(n) && n > 0 ? n : null
  }
  const country = num('country')
  const league = country ? num('league') : null
  const team = league ? num('team') : null
  // A player link is valid on its own (shared links), with or without its club context.
  return { country, league, team, player: num('player') }
}

function toUrl(route: ExplorerRoute) {
  const q = new URLSearchParams()
  for (const key of KEYS) if (route[key]) q.set(key, String(route[key]))
  const search = q.toString()
  return `${window.location.pathname}${search ? `?${search}` : ''}`
}

/** Each level only makes sense under its parent. */
const normalise = (next: ExplorerRoute): ExplorerRoute => ({
  country: next.country,
  league: next.country ? next.league : null,
  team: next.country && next.league ? next.team : null,
  player: next.player,
})

export const stepOf = (r: ExplorerRoute): ExplorerStep =>
  r.team ? 'squad' : r.league ? 'teams' : r.country ? 'leagues' : 'countries'

/**
 * Navigation state mirrored into the URL (?country=&league=&team=&player=), so
 * browser back/forward and shared links work without a router dependency.
 */
export function useExplorerRoute() {
  const [route, setRoute] = useState<ExplorerRoute>(readRoute)

  useEffect(() => {
    const onPop = () => setRoute(readRoute())
    window.addEventListener('popstate', onPop)
    return () => window.removeEventListener('popstate', onPop)
  }, [])

  const go = useCallback((next: ExplorerRoute) => {
    const clean = normalise(next)
    window.history.pushState(clean.player ? STAGE_STATE : null, '', toUrl(clean))
    setRoute(clean)
  }, [])

  /** Same place, corrected ids (e.g. after a re-seed renumbered them): rewrites the URL without a new history entry. */
  const replace = useCallback((next: ExplorerRoute) => {
    const clean = normalise(next)
    window.history.replaceState(window.history.state, '', toUrl(clean))
    setRoute(clean)
  }, [])

  /** Close the stage: pop our own history entry when we pushed it, else replace the URL. */
  const closePlayer = useCallback(() => {
    if (window.history.state === STAGE_STATE) {
      window.history.back()
      return
    }
    setRoute((r) => {
      const next = { ...r, player: null }
      window.history.replaceState(null, '', toUrl(next))
      return next
    })
  }, [])

  return { route, go, replace, closePlayer }
}
