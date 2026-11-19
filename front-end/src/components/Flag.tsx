import type { ReactNode } from 'react'
import { flagKey } from '../lib/flags'

/**
 * Hand-drawn flags for the five featured nations (Spain uses the civil flag, without
 * the coat of arms, which stays crisp at small sizes). Every other country uses the
 * flag-icons vector set; emoji flags aren't used because Windows and most Linux
 * desktops show them as letter pairs, and Northern Ireland has none.
 */
const FLAGS: Record<string, ReactNode> = {
  ENG: (
    <>
      <rect width="30" height="20" fill="#fff" />
      <rect x="12.5" width="5" height="20" fill="#ce1124" />
      <rect y="7.5" width="30" height="5" fill="#ce1124" />
    </>
  ),
  ESP: (
    <>
      <rect width="30" height="20" fill="#aa151b" />
      <rect y="5" width="30" height="10" fill="#f1bf00" />
    </>
  ),
  ITA: (
    <>
      <rect width="10" height="20" fill="#009246" />
      <rect x="10" width="10" height="20" fill="#fff" />
      <rect x="20" width="10" height="20" fill="#ce2b37" />
    </>
  ),
  GER: (
    <>
      <rect width="30" height="6.67" fill="#000" />
      <rect y="6.67" width="30" height="6.67" fill="#dd0000" />
      <rect y="13.33" width="30" height="6.67" fill="#ffce00" />
    </>
  ),
  FRA: (
    <>
      <rect width="10" height="20" fill="#0055a4" />
      <rect x="10" width="10" height="20" fill="#fff" />
      <rect x="20" width="10" height="20" fill="#ef4135" />
    </>
  ),
}

/** flag-icons 4:3 SVGs, keyed by file name ("se", "gb-nir", "xk"…), as asset URLs. */
const VECTOR_FLAGS: Record<string, string> = Object.fromEntries(
  Object.entries(
    import.meta.glob<string>('/node_modules/flag-icons/flags/4x3/*.svg', { eager: true, query: '?url', import: 'default' }),
  ).map(([path, url]) => [path.slice(path.lastIndexOf('/') + 1, -4), url]),
)

/** Featured-nation codes as the API stores them, and their flag-icons keys. */
const FEATURED_KEYS: Record<string, string> = { 'gb-eng': 'ENG', es: 'ESP', it: 'ITA', de: 'GER', fr: 'FRA' }

interface FlagProps {
  /** Country code as stored by the API (FIFA-style or ISO alpha-3, e.g. "GER"); may be missing. */
  code: string | null | undefined
  /** Country name; used to identify the flag when the code is missing. */
  name: string
  className?: string
}

/**
 * Country flag as a crisp vector at any size. If a country can't be identified, a
 * neutral chip with its code (or initials) keeps the space filled, so a flag cell is
 * never blank.
 */
export default function Flag({ code, name, className = 'h-8 w-12' }: FlagProps) {
  const key = flagKey(code, name)
  const drawn = key ? FLAGS[FEATURED_KEYS[key] ?? ''] : undefined
  const label = `Flag of ${name}`

  if (drawn) {
    return (
      <svg viewBox="0 0 30 20" className={className} role="img" aria-label={label}>
        {drawn}
      </svg>
    )
  }
  const url = key ? VECTOR_FLAGS[key] : undefined
  if (url) {
    return <img src={url} alt={label} title={name} draggable={false} decoding="async" className={`object-cover ${className}`} />
  }
  const fallback = (code?.trim() || name.split(/\s+/).map((w) => w[0]).join('')).slice(0, 3).toUpperCase()
  return (
    <span role="img" aria-label={label} title={name} className={`inline-flex items-center justify-center bg-line font-mono text-[0.55rem] leading-none text-ink-soft ${className}`}>
      {fallback}
    </span>
  )
}
