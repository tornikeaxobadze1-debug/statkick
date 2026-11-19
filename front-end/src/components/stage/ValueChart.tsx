import { useEffect, useId, useMemo, useRef, useState, type KeyboardEvent, type PointerEvent } from 'react'
import { formatMoney } from '../../lib/stats'
import { valuationPoints, type ValuationPoint } from '../../lib/valuation'
import type { PlayerProfile } from '../../types'

const HEIGHT = 112
const PAD = { top: 16, right: 8, bottom: 22, left: 8 }

const monthYear = (t: number) => new Date(t).toLocaleDateString('en-GB', { month: 'short', year: 'numeric', timeZone: 'UTC' })
const describe = (p: ValuationPoint) => `${p.kind === 'current' ? 'Now' : monthYear(p.t)} · ${formatMoney(p.value)} · ${p.label}`

/**
 * Minimal career-value sparkline: transfer fees (hollow dots), the highest market
 * value and today's value (solid dots), drawn in the club accent. Hover or use ← →.
 */
export default function ValueChart({
  player,
  accent,
}: {
  player: Pick<PlayerProfile, 'market_value' | 'highest_market_value' | 'highest_market_value_date' | 'transfer_history'>
  accent: string
}) {
  const points = useMemo(() => valuationPoints(player), [player])
  const box = useRef<HTMLDivElement>(null)
  const [width, setWidth] = useState(320)
  const [active, setActive] = useState<number | null>(null)
  const gradientId = `value-fill-${useId().replace(/[^a-zA-Z0-9_-]/g, '')}`
  const hasPoints = points.length > 0

  // Draw in real pixels so dots stay round at any panel width.
  useEffect(() => {
    const el = box.current
    if (!el) return
    const ro = new ResizeObserver(([entry]) => setWidth(Math.max(160, Math.round(entry.contentRect.width))))
    ro.observe(el)
    return () => ro.disconnect()
  }, [hasPoints])

  if (!points.length) return null

  const t0 = points[0].t
  const t1 = points[points.length - 1].t
  const vMax = Math.max(...points.map((p) => p.value)) * 1.12
  const x = (t: number) => PAD.left + ((t - t0) / Math.max(1, t1 - t0)) * (width - PAD.left - PAD.right)
  const y = (v: number) => PAD.top + (1 - v / vMax) * (HEIGHT - PAD.top - PAD.bottom)
  const baseline = HEIGHT - PAD.bottom
  const coords = points.map((p) => [x(p.t), y(p.value)] as const)
  const line = coords.map(([cx, cy], i) => `${i ? 'L' : 'M'}${cx.toFixed(1)},${cy.toFixed(1)}`).join(' ')
  const area = `${line} L${coords[coords.length - 1][0].toFixed(1)},${baseline} L${coords[0][0].toFixed(1)},${baseline} Z`
  const hasFees = points.some((p) => p.kind === 'fee')

  const nearest = (clientX: number) => {
    const left = box.current?.getBoundingClientRect().left ?? 0
    let best = 0
    coords.forEach(([cx], i) => {
      if (Math.abs(cx - (clientX - left)) < Math.abs(coords[best][0] - (clientX - left))) best = i
    })
    return best
  }
  const onMove = (e: PointerEvent<HTMLDivElement>) => setActive(nearest(e.clientX))
  const onKey = (e: KeyboardEvent<HTMLDivElement>) => {
    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return
    e.preventDefault()
    e.stopPropagation()
    setActive((i) => {
      const from = i ?? points.length - 1
      return Math.min(points.length - 1, Math.max(0, from + (e.key === 'ArrowRight' ? 1 : -1)))
    })
  }

  const shown = active ?? null
  const tip = shown === null ? null : points[shown]
  // Keep the tooltip inside the panel: anchored left near the left edge, right near the right.
  const tipX = shown === null ? 0 : coords[shown][0]
  const tipAnchor = tipX < width * 0.3 ? '' : tipX > width * 0.7 ? '-translate-x-full' : '-translate-x-1/2'

  return (
    <figure className="mt-5">
      <figcaption className="mb-1.5 flex items-baseline justify-between text-[12.5px] font-medium">
        <span className="opacity-65">Value trajectory</span>
        <span className="flex items-center gap-3 text-[11px] font-normal opacity-55">
          <span className="flex items-center gap-1">
            <span aria-hidden className="h-1.5 w-1.5 rounded-full" style={{ background: accent }} /> Market value
          </span>
          {hasFees && (
            <span className="flex items-center gap-1">
              <span aria-hidden className="h-1.5 w-1.5 rounded-full border" style={{ borderColor: accent }} /> Transfer fee
            </span>
          )}
        </span>
      </figcaption>
      <div
        ref={box}
        tabIndex={0}
        role="group"
        aria-label={`Value trajectory, ${points.length} figures from ${monthYear(t0)} to now. Use the left and right arrow keys to step through them.`}
        onPointerMove={onMove}
        onPointerLeave={() => setActive(null)}
        onFocus={() => setActive((i) => i ?? points.length - 1)}
        onBlur={() => setActive(null)}
        onKeyDown={onKey}
        className="relative rounded-md outline-none focus-visible:ring-1 focus-visible:ring-current/40"
        style={{ height: HEIGHT }}
      >
        <svg width={width} height={HEIGHT} aria-hidden className="block overflow-visible">
          <defs>
            <linearGradient id={gradientId} x1="0" y1="0" x2="0" y2="1">
              <stop offset="0" stopColor={accent} stopOpacity="0.32" />
              <stop offset="1" stopColor={accent} stopOpacity="0" />
            </linearGradient>
          </defs>
          <line x1={PAD.left} x2={width - PAD.right} y1={baseline} y2={baseline} stroke="currentColor" strokeOpacity="0.15" />
          <path d={area} fill={`url(#${gradientId})`} />
          <path d={line} fill="none" stroke={accent} strokeWidth="1.75" strokeLinejoin="round" strokeLinecap="round" />
          {shown !== null && (
            <line x1={coords[shown][0]} x2={coords[shown][0]} y1={PAD.top - 6} y2={baseline} stroke="currentColor" strokeOpacity="0.25" strokeDasharray="2 3" />
          )}
          {points.map((p, i) => {
            const [cx, cy] = coords[i]
            const on = i === shown
            return p.kind === 'fee' ? (
              <circle key={i} cx={cx} cy={cy} r={on ? 4.5 : 3} fill="#0d0a0c" stroke={accent} strokeWidth="1.5" />
            ) : (
              <circle key={i} cx={cx} cy={cy} r={on ? 5 : p.kind === 'current' ? 4 : 3.25} fill={accent} stroke="#0d0a0c" strokeWidth="1.5" />
            )
          })}
          <text x={PAD.left} y={HEIGHT - 5} fill="currentColor" fillOpacity="0.5" fontSize="10.5" className="font-mono">
            {new Date(t0).getUTCFullYear()}
          </text>
          <text x={width - PAD.right} y={HEIGHT - 5} textAnchor="end" fill="currentColor" fillOpacity="0.5" fontSize="10.5" className="font-mono">
            Now
          </text>
        </svg>

        {tip && shown !== null && (
          <div
            className={`pointer-events-none absolute z-10 -translate-y-full rounded-md border border-white/10 bg-[rgba(10,8,9,0.94)] px-2.5 py-1.5 whitespace-nowrap shadow-lg ${tipAnchor}`}
            style={{ left: tipAnchor === '' ? Math.max(0, tipX - 8) : tipAnchor === '-translate-x-full' ? Math.min(width, tipX + 8) : tipX, top: coords[shown][1] - 10 }}
          >
            <p className="font-mono text-[13px] tabular-nums">
              {formatMoney(tip.value)} <span className="opacity-60">· {tip.kind === 'current' ? 'Now' : monthYear(tip.t)}</span>
            </p>
            <p className="text-[11px] opacity-65">{tip.label}</p>
          </div>
        )}
        <p className="sr-only" aria-live="polite">
          {tip ? describe(tip) : ''}
        </p>
      </div>
    </figure>
  )
}
