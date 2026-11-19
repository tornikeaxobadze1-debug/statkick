import type { ReactNode } from 'react'
import { valueFont } from '../lib/stats'
import type { FormResult } from '../types'

export function EmptyState({ children }: { children: ReactNode }) {
  return <div className="border border-line px-6 py-10 text-center text-sm text-ink-mute">{children}</div>
}

export function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`animate-pulse bg-line/60 ${className}`} />
}

/** A label/value pair in the editorial style. */
export function Fact({ label, value, className = '' }: { label: string; value: ReactNode; className?: string }) {
  return (
    <div className={className}>
      <dt className="label">{label}</dt>
      <dd className={`mt-1.5 text-lg leading-tight text-ink ${valueFont(value)}`}>{value ?? '—'}</dd>
    </div>
  )
}

const FORM_STYLES: Record<FormResult, string> = {
  W: 'bg-win text-white',
  D: 'bg-draw text-white',
  L: 'bg-loss text-white',
}

export function FormStrip({ form, className = '' }: { form: FormResult[] | null | undefined; className?: string }) {
  if (!form?.length) return <span className={`text-xs text-ink-mute ${className}`}>—</span>
  return (
    <span className={`inline-flex gap-1 ${className}`} aria-label={`Recent form, newest first: ${form.join(' ')}`}>
      {form.map((r, i) => (
        <span key={i} className={`flex h-5 w-5 items-center justify-center text-[10px] font-semibold ${FORM_STYLES[r]}`}>
          {r}
        </span>
      ))}
    </span>
  )
}

/**
 * Marks a value as a model estimate rather than a verified figure. Inherits the text
 * colour, so it reads on the stage and on site surfaces alike.
 */
export function EstPill({ note }: { note?: string | null }) {
  const explanation = `Estimated by StatKick's model, not a verified figure${note ? `: ${note}` : ''}`
  return (
    <span
      title={explanation}
      className="ml-1.5 inline-flex shrink-0 cursor-help items-center rounded-full border border-current/30 px-1.5 py-px align-middle font-sans text-[10px] leading-[14px] font-medium tracking-wide opacity-70"
    >
      <span aria-hidden>Est.</span>
      <span className="sr-only">({explanation})</span>
    </span>
  )
}

/** Consistent, quiet stand-in for a value the data source doesn't provide (instead of a bare dash). */
export function Undisclosed({ label = 'Undisclosed' }: { label?: string }) {
  return <span className="font-sans text-[0.85em] font-normal text-ink-faint">{label}</span>
}
