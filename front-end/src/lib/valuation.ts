import type { PlayerProfile } from '../types'
import { parseMoney } from './stats'

export type ValuationKind = 'fee' | 'peak' | 'current'

export interface ValuationPoint {
  /** Epoch ms. */
  t: number
  /** Euros. */
  value: number
  kind: ValuationKind
  /** What the figure is, for the tooltip ("Transfer fee · Celtic → Southampton"). */
  label: string
}

/** "2019-12-01" or "2013-06" → epoch ms (first of the month), or null. */
function monthStart(iso: string | null | undefined): number | null {
  const m = iso?.match(/^(\d{4})-(\d{2})/)
  return m ? Date.UTC(Number(m[1]), Number(m[2]) - 1, 1) : null
}

/**
 * The player's career value, from real dated figures only: each transfer fee paid for
 * him, his highest market value (at its date) and his current market value (today).
 * Nothing is interpolated or estimated; fewer than two figures means no chart.
 */
export function valuationPoints(p: Pick<PlayerProfile, 'market_value' | 'highest_market_value' | 'highest_market_value_date' | 'transfer_history'>, now = Date.now()): ValuationPoint[] {
  const points: ValuationPoint[] = []

  for (const move of p.transfer_history ?? []) {
    const t = monthStart(move.date)
    const value = parseMoney(move.fee)
    if (t === null || value === null || value <= 0) continue // free, loan, academy…
    const route = [move.from, move.to].filter(Boolean).join(' → ')
    points.push({ t, value, kind: 'fee', label: route ? `Transfer fee · ${route}` : 'Transfer fee' })
  }

  const peak = parseMoney(p.highest_market_value)
  const peakT = monthStart(p.highest_market_value_date)
  if (peak !== null && peakT !== null && peakT < now) {
    points.push({ t: peakT, value: peak, kind: 'peak', label: 'Highest market value' })
  }

  const current = parseMoney(p.market_value)
  if (current !== null) points.push({ t: now, value: current, kind: 'current', label: 'Current market value' })

  points.sort((a, b) => a.t - b.t)
  return points.length >= 2 ? points : []
}
