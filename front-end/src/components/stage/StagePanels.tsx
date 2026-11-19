import type { ReactNode } from 'react'
import { estimatedFields } from '../../lib/estimates'
import { accentOnDark, ageFrom, formatMonthYear, isFigure, valueFont } from '../../lib/stats'
import { EstPill, Undisclosed } from '../ui'
import type { HeatmapZone, StagePlayer } from '../../types'
import ValueChart from './ValueChart'

/** Optional third item: the estimate note when the value is a model estimate (null = no note). */
type Row = [label: string, value: ReactNode, estimate?: string | null]

/** Rows of label/value pairs in the stage's foreground colour. */
function Rows({ rows }: { rows: Row[] }) {
  return (
    <dl>
      {rows.map(([label, value, estimate]) => (
        <div key={label} className="flex items-baseline justify-between gap-6 border-b border-current/15 py-2.5">
          <dt className="text-[12.5px] font-medium opacity-65">{label}</dt>
          <dd className={`text-right text-base ${valueFont(value)}`}>
            {value ?? <Undisclosed />}
            {value != null && estimate !== undefined && <EstPill note={estimate} />}
          </dd>
        </div>
      ))}
    </dl>
  )
}

function Big({ label, value, sub, estimate }: { label: string; value: ReactNode; sub?: ReactNode; estimate?: string | null }) {
  return (
    <div className="min-w-0">
      <p className="flex items-center text-[12.5px] font-medium">
        <span className="opacity-65">{label}</span>
        {value != null && estimate !== undefined && <EstPill note={estimate} />}
      </p>
      {/* Figures stay on one line; words (a playing style) wrap within their column. */}
      <p
        className={`mt-1 text-[clamp(1.35rem,1.9vw,2rem)] font-medium ${valueFont(value)} ${
          isFigure(value) ? 'leading-none whitespace-nowrap' : 'leading-[1.1] break-words [text-wrap:balance]'
        }`}
      >
        {value ?? <Undisclosed />}
      </p>
      {sub && <p className={`mt-1 text-xs opacity-60 ${typeof sub === 'string' && isFigure(sub) ? 'font-mono tabular-nums' : ''}`}>{sub}</p>}
    </div>
  )
}

const Heading = ({ children }: { children: ReactNode }) => (
  <h4 className="mt-7 mb-2 text-[12.5px] font-medium opacity-65">{children}</h4>
)

const fixed = (n: number | null | undefined, digits = 2) => (n == null ? null : n.toFixed(digits))
const pct = (n: number | null | undefined) => (n == null ? null : `${n.toFixed(1)}%`)

/* ---------------- 0° · Front ---------------- */

export function MarketPanel({ player: p }: { player: StagePlayer }) {
  const est = estimatedFields(p.profile_estimates)
  return (
    <>
      <div className="grid grid-cols-2 gap-6">
        <Big label="Market value" value={p.market_value} estimate={est.get('market_value')} />
        <Big
          label="Highest value"
          value={p.highest_market_value}
          sub={formatMonthYear(p.highest_market_value_date)}
          estimate={est.get('highest_market_value')}
        />
      </div>
      <ValueChart player={p} accent={accentOnDark(p.current_team)} />
      <Heading>Contract</Heading>
      <Rows
        rows={[
          ['Weekly salary', p.weekly_salary, est.get('weekly_salary')],
          ['Annual salary', p.annual_salary, est.get('annual_salary')],
          ['Release clause', p.release_clause ?? 'None', est.get('release_clause')],
          ['Contract until', formatMonthYear(p.contract_expires), est.get('contract_expires')],
          ['Agent', p.agent_name, est.get('agent_name')],
        ]}
      />
      <Heading>Profile</Heading>
      <Rows
        rows={[
          ['Age', p.age ?? ageFrom(p.date_of_birth)],
          ['Height', p.height_cm ? `${p.height_cm} cm` : null, est.get('height_cm')],
          ['Foot', p.preferred_foot, est.get('preferred_foot')],
        ]}
      />
    </>
  )
}

/* ---------------- 90° · Right profile ---------------- */

export function PerformancePanel({ player: p }: { player: StagePlayer }) {
  return (
    <>
      <div className="grid grid-cols-3 gap-6">
        <Big label="Goals" value={p.goals ?? 0} sub={p.xg != null ? `${fixed(p.xg)} xG` : undefined} />
        <Big label="Assists" value={p.assists ?? 0} sub={p.xa != null ? `${fixed(p.xa)} xA` : undefined} />
        <Big label="Rating" value={fixed(p.avg_rating)} sub={p.appearances ? `${p.appearances} apps` : undefined} />
      </div>
      <Heading>Season metrics</Heading>
      <Rows
        rows={[
          ['Pass accuracy', pct(p.pass_accuracy_pct)],
          ['Key passes', p.key_passes],
          ['Progressive carries', p.progressive_carries],
          ['Dribble success', pct(p.dribble_success_pct)],
        ]}
      />
      <Heading>Heatmap</Heading>
      <Heatmap zones={p.heatmap_zones} />
    </>
  )
}

const ROWS: { side: 'left' | 'centre' | 'right'; label: string }[] = [
  { side: 'left', label: 'Left' },
  { side: 'centre', label: 'Centre' },
  { side: 'right', label: 'Right' },
]
const THIRDS = ['def', 'mid', 'att'] as const

/** 3×3 zone heatmap on a mini pitch, attacking left → right. One hue; darker = more time. */
function Heatmap({ zones }: { zones: StagePlayer['heatmap_zones'] }) {
  if (!zones || Object.keys(zones).length === 0) return <p className="text-sm opacity-60">No heatmap data.</p>
  const max = Math.max(1, ...Object.values(zones).map((v) => v ?? 0))
  return (
    <figure>
      <div className="relative aspect-[3/2] w-full border border-current/40">
        {/* Halfway line + centre circle */}
        <span aria-hidden className="absolute inset-y-0 left-1/2 w-px bg-current/40" />
        <span aria-hidden className="absolute top-1/2 left-1/2 h-1/3 w-[22%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-current/40" />
        <div className="absolute inset-0 grid grid-cols-3 grid-rows-3">
          {ROWS.map((row) =>
            THIRDS.map((third) => {
              const key = `${third}_${row.side}` as HeatmapZone
              const v = zones[key] ?? 0
              return (
                <div
                  key={key}
                  className="flex items-center justify-center text-[10px] font-mono tabular-nums"
                  style={{ background: `color-mix(in srgb, currentColor ${Math.round((v / max) * 55)}%, transparent)` }}
                  title={`${third === 'def' ? 'Defensive' : third === 'mid' ? 'Middle' : 'Attacking'} third, ${row.label.toLowerCase()}: ${v}%`}
                >
                  <span className="opacity-70">{v ? `${v}%` : ''}</span>
                </div>
              )
            }),
          )}
        </div>
      </div>
      <figcaption className="mt-1.5 flex justify-between text-[11.5px] opacity-65">
        <span>Own goal</span>
        <span>Attack →</span>
      </figcaption>
    </figure>
  )
}

/* ---------------- 180° · Back ---------------- */

export function HonoursPanel({ player: p }: { player: StagePlayer }) {
  const number = p.shirt_number ?? p.current_team?.pivot.shirt_number
  return (
    <>
      <div className="flex items-end justify-between gap-6">
        <div>
          <p className="text-[12.5px] font-medium opacity-65">Shirt</p>
          {number != null ? <p className="font-display text-8xl leading-[0.85] font-medium">{number}</p> : <p className="mt-3 text-lg"><Undisclosed label="Not assigned" /></p>}
        </div>
        <div className="text-right">
          <p className="text-[12.5px] font-medium opacity-65">{p.national_team ?? 'International'}</p>
          <p className="mt-1 text-2xl font-mono tabular-nums">
            {p.intl_caps ?? 0} <span className="text-sm opacity-60">caps</span> · {p.intl_goals ?? 0} <span className="text-sm opacity-60">goals</span>
          </p>
        </div>
      </div>

      <Heading>Honours</Heading>
      {p.trophies?.length ? (
        <ul className="space-y-1">
          {p.trophies.map((t) => (
            <li key={t.title} className="font-display text-lg">
              {t.count}× {t.title}
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-sm opacity-60">No major honours yet.</p>
      )}

      <Heading>Transfer history</Heading>
      {p.transfer_history?.length ? (
        <ol className="relative ml-1 border-l border-current/30 pl-4">
          {p.transfer_history.map((t, i) => (
            <li key={`${t.date}-${i}`} className="relative pb-3 last:pb-0">
              <span aria-hidden className="absolute top-1.5 -left-[21px] h-2 w-2 rounded-full bg-current" />
              <p className="font-mono text-[12px] opacity-65">{formatMonthYear(t.date)}</p>
              <p className="text-sm">
                {t.from} → <span className="font-medium">{t.to}</span>
                <span className="ml-2 opacity-60">{t.fee}</span>
              </p>
            </li>
          ))}
        </ol>
      ) : (
        <p className="text-sm opacity-60">No transfers recorded.</p>
      )}

      <Heading>Transfer rumours</Heading>
      {p.transfer_rumors?.length ? (
        <ul className="space-y-2.5">
          {p.transfer_rumors.map((r) => (
            <li key={r.club}>
              <div className="flex justify-between text-sm">
                <span>{r.club}</span>
                <span className="font-mono tabular-nums">{r.probability_pct}%</span>
              </div>
              <div className="mt-1 h-1 bg-current/15">
                <div className="h-full bg-current" style={{ width: `${Math.min(100, r.probability_pct)}%` }} />
              </div>
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-sm opacity-60">No rumours.</p>
      )}
    </>
  )
}

/* ---------------- 270° · Left profile ---------------- */

export function TacticalPanel({ player: p }: { player: StagePlayer }) {
  const fit = !p.injury_status || /^fit$/i.test(p.injury_status)
  return (
    <>
      {/* The style takes the room it needs; the status column is only as wide as its value. */}
      <div className="grid grid-cols-[minmax(0,1fr)_minmax(0,auto)] gap-x-8 gap-y-6">
        <Big label="Playing style" value={p.playing_style ? <span className="text-2xl">{p.playing_style}</span> : null} />
        <Big
          label="Status"
          value={
            <span className="flex items-center gap-2 text-2xl">
              <span aria-hidden className={`h-2.5 w-2.5 rounded-full ${fit ? 'bg-current' : 'border border-current'}`} />
              {p.injury_status ?? 'Fit'}
            </span>
          }
        />
      </div>
      <Heading>Positions</Heading>
      <Rows
        rows={[
          ['Primary', p.primary_position ?? p.position],
          ['Secondary', p.secondary_positions?.length ? p.secondary_positions.join(', ') : null],
        ]}
      />
      <Heading>Defensive work</Heading>
      <Rows
        rows={[
          ['Tackles + interceptions', p.tackles_interceptions],
          ['Aerial duels won', pct(p.aerial_duels_won_pct)],
        ]}
      />
      <Heading>Injury history</Heading>
      {p.injury_history?.length ? (
        <ul>
          {p.injury_history.map((inj, i) => (
            <li key={`${inj.date}-${i}`} className="flex items-baseline justify-between gap-4 border-b border-current/15 py-2 text-sm">
              <span>
                <span className="opacity-60">{formatMonthYear(inj.date)}</span> · {inj.injury}
              </span>
              <span className="font-mono tabular-nums opacity-80">{inj.days_out} days</span>
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-sm opacity-60">No injuries recorded.</p>
      )}
    </>
  )
}
