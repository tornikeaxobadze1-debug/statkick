import { motion } from 'framer-motion'
import { Check } from 'lucide-react'
import { useMemo, type CSSProperties } from 'react'
import { FEATURED_NATIONS, NATION_GLOW } from '../../lib/stats'
import type { CountryTree, ID } from '../../types'
import Flag from '../Flag'

type Entry = { name: string; code: string; country: CountryTree | null }

interface CountryBarProps {
  countries: CountryTree[]
  /** True while the tree is loading or the backend is re-syncing: cards stay vivid, the pill shows "Syncing". */
  syncing: boolean
  selectedId: ID | null
  onSelect: (country: CountryTree) => void
}

export default function CountryBar({ countries, syncing, selectedId, onSelect }: CountryBarProps) {
  const entries = useMemo<Entry[]>(() => {
    const byName = new Map(countries.map((c) => [c.name.toLowerCase(), c]))
    // Only the five featured nations render, whatever else the API lists.
    return FEATURED_NATIONS.map((n) => ({ ...n, country: byName.get(n.name.toLowerCase()) ?? null }))
  }, [countries])

  return (
    <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      {entries.map((e, i) => (
        <motion.li key={e.code} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05, duration: 0.5, ease: [0.22, 1, 0.36, 1] }}>
          <NationCard entry={e} syncing={syncing} selected={!!e.country && e.country.id === selectedId} onSelect={onSelect} />
        </motion.li>
      ))}
    </ul>
  )
}

function NationCard({ entry, syncing, selected, onSelect }: { entry: Entry; syncing: boolean; selected: boolean; onSelect: (c: CountryTree) => void }) {
  const c = entry.country
  const leagues = c?.divisions ?? []
  const available = leagues.length > 0
  const [tint] = NATION_GLOW[entry.code] ?? ['#e11d48']

  const pill = available ? leagues.map((d) => `${d.name} · ${d.teams.length} ${d.teams.length === 1 ? 'club' : 'clubs'}`).join(' / ') : syncing ? 'Syncing…' : 'No league yet'

  return (
    <button
      type="button"
      onClick={() => available && c && onSelect(c)}
      aria-disabled={!available}
      aria-pressed={selected}
      aria-label={`${entry.name}${available ? `: ${pill}` : ''}`}
      style={{ '--tint': tint } as CSSProperties}
      className={`group relative flex w-full flex-col overflow-hidden rounded-2xl border bg-surface p-6 text-left transition duration-500 ease-out ${
        selected
          ? '-translate-y-1 border-crimson/60 bg-[linear-gradient(160deg,rgba(127,16,40,0.42),rgba(17,12,15,0.92)_65%)] text-ink shadow-[0_0_0_1px_rgba(225,29,72,0.35),0_0_28px_rgba(200,30,69,0.25)]'
          : 'border-line hover:-translate-y-1 hover:border-crimson/45 hover:shadow-[0_0_28px_rgba(200,30,69,0.25)]'
      }`}
    >
      {/* Faint nation-tinted light, stronger on hover / when selected */}
      <span
        aria-hidden
        className={`pointer-events-none absolute inset-0 transition-opacity duration-700 ${selected ? 'opacity-100' : 'opacity-40 group-hover:opacity-100'}`}
        style={{ background: 'radial-gradient(110% 70% at 0% 0%, color-mix(in srgb, var(--tint) 16%, transparent), transparent 65%)' }}
      />
      {!selected && (
        <span
          aria-hidden
          className="pointer-events-none absolute inset-0 bg-[linear-gradient(160deg,rgba(127,16,40,0.28),transparent_60%)] opacity-0 transition-opacity duration-500 group-hover:opacity-100"
        />
      )}
      <span
        aria-hidden
        className={`pointer-events-none absolute inset-x-6 bottom-0 h-px bg-gradient-to-r from-transparent via-crimson to-transparent transition-opacity duration-500 ${selected ? 'opacity-100' : 'opacity-0 group-hover:opacity-70'}`}
      />

      {/* Flag — the card's single focal point */}
      <span className="relative flex items-start justify-between">
        <span className="transition-transform duration-700 ease-out group-hover:-translate-y-0.5 group-hover:scale-[1.04]">
          <Flag code={entry.code} name={entry.name} className="h-9 w-[54px] rounded-[4px] ring-1 ring-white/10" />
        </span>
        {selected && (
          <span className="flex h-5 w-5 items-center justify-center rounded-full bg-crimson text-white">
            <Check className="h-3 w-3" strokeWidth={3} aria-hidden />
          </span>
        )}
      </span>

      <span className="relative mt-6 block">
        <span className="font-display block text-xl leading-tight font-medium tracking-tight text-ink">{entry.name}</span>
        <span
          className={`mt-3 inline-flex max-w-full truncate rounded-full border px-2.5 py-1 text-[11.5px] leading-none ${
            available ? 'border-crimson/25 bg-crimson/[0.06] text-crimson-light' : 'border-line text-ink-mute'
          }`}
        >
          <span className="truncate">{pill}</span>
        </span>
      </span>

    </button>
  )
}
