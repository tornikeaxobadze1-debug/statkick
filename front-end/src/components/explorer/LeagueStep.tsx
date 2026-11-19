import { ArrowRight } from 'lucide-react'
import type { CountryTree, ID } from '../../types'
import TeamCrest from '../TeamCrest'
import { EmptyState } from '../ui'

export type League = CountryTree['divisions'][number]

interface LeagueStepProps {
  country: CountryTree
  selectedId: ID | null
  onSelect: (league: League) => void
}

/** Full-width league rows: name and club count on the left, crests and the CTA on the right. */
export default function LeagueStep({ country, selectedId, onSelect }: LeagueStepProps) {
  if (country.divisions.length === 0) return <EmptyState>No leagues in {country.name} yet.</EmptyState>

  return (
    <ul className="flex flex-col gap-3">
      {country.divisions.map((d) => {
        const selected = d.id === selectedId
        return (
          <li key={d.id}>
            <button
              type="button"
              onClick={() => onSelect(d)}
              aria-pressed={selected}
              className={`group flex w-full flex-col gap-5 rounded-2xl border bg-surface px-6 py-5 text-left transition duration-300 sm:flex-row sm:items-center sm:justify-between sm:gap-8 sm:px-8 sm:py-6 ${
                selected
                  ? 'border-crimson/60 bg-[linear-gradient(160deg,rgba(127,16,40,0.42),rgba(17,12,15,0.92)_65%)] shadow-[0_0_0_1px_rgba(225,29,72,0.35),0_0_28px_rgba(200,30,69,0.25)]'
                  : 'border-line hover:border-crimson/45 hover:bg-surface-hover hover:shadow-[0_0_28px_rgba(200,30,69,0.18)]'
              }`}
            >
              <span className="min-w-0">
                <span className="font-display block text-2xl font-medium tracking-tight text-ink">{d.name}</span>
                <span className="mt-1 block text-sm text-ink-mute">
                  {country.name} · {d.teams.length} {d.teams.length === 1 ? 'club' : 'clubs'}
                </span>
              </span>

              <span className="flex flex-wrap items-center gap-6 sm:justify-end">
                <span className="flex items-center gap-2.5" aria-label={d.teams.map((t) => t.name).join(', ')}>
                  {d.teams.map((t) => (
                    <TeamCrest key={t.id} team={t} size="md" />
                  ))}
                </span>
                <span className="inline-flex items-center gap-2 rounded-full border border-crimson/35 px-4 py-2 text-sm font-medium text-ink transition group-hover:border-crimson group-hover:text-crimson-light">
                  Explore League
                  <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" aria-hidden />
                </span>
              </span>
            </button>
          </li>
        )
      })}
    </ul>
  )
}
