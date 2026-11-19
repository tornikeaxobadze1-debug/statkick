import type { ProfileEstimate } from '../types'

/**
 * Fields the API marks as model estimates (`profile_estimates`), keyed by field name
 * ("weekly_salary", "highest_market_value"…), each with an optional note on how it was
 * estimated. Accepts a list of field names or of `{ field | key, note | basis | method }`.
 */
export function estimatedFields(estimates: ProfileEstimate[] | null | undefined): Map<string, string | null> {
  const fields = new Map<string, string | null>()
  for (const e of estimates ?? []) {
    if (typeof e === 'string') {
      fields.set(e, null)
    } else if (e && typeof e === 'object') {
      const field = e.field ?? e.key
      if (field) fields.set(field, e.note ?? e.basis ?? e.method ?? null)
    }
  }
  return fields
}
