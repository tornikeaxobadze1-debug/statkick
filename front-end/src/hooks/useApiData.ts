import { useCallback, useEffect, useRef, useState } from 'react'

export type DataSource = 'loading' | 'api' | 'fallback'

export interface ApiDataState<T> {
  data: T
  source: DataSource
  error: Error | null
  refresh: () => void
}

interface Options<T> {
  /** Shown only if the API fails before ever answering (offline demo data). */
  fallback: T
  /** Shown while the first request is in flight. Defaults to `fallback`. */
  initial?: T
  /** Re-fetch on an interval (e.g. live matches). */
  pollMs?: number
  /** Re-fetch whenever this value changes (e.g. the selected id). */
  key?: string | number | null
}

/**
 * Loads data through a fetcher. If the API hasn't answered for the current `key`,
 * a failure swaps in `fallback` so the UI never blanks out; once real data has
 * arrived for that key, a failed poll keeps it and only flips `source` to 'fallback'.
 */
export function useApiData<T>(
  fetcher: (signal: AbortSignal) => Promise<T>,
  { fallback, initial = fallback, pollMs, key = null }: Options<T>,
): ApiDataState<T> {
  const [data, setData] = useState<T>(initial)
  const [source, setSource] = useState<DataSource>('loading')
  const [error, setError] = useState<Error | null>(null)
  const [tick, setTick] = useState(0)

  // Keep the latest fetcher/fallback without re-triggering the effect each render.
  const fetcherRef = useRef(fetcher)
  const fallbackRef = useRef(fallback)
  /** The key whose data last came from the API (live data for one key says nothing about another). */
  const liveKey = useRef<{ key: Options<T>['key'] } | null>(null)
  useEffect(() => {
    fetcherRef.current = fetcher
    fallbackRef.current = fallback
  })

  useEffect(() => {
    const controller = new AbortController()
    fetcherRef
      .current(controller.signal)
      .then((result) => {
        liveKey.current = { key }
        setData(result)
        setSource('api')
        setError(null)
      })
      .catch((err: unknown) => {
        if (controller.signal.aborted) return
        if (liveKey.current?.key !== key) setData(fallbackRef.current)
        setError(err instanceof Error ? err : new Error(String(err)))
        setSource('fallback')
      })
    return () => controller.abort()
  }, [tick, key])

  useEffect(() => {
    if (!pollMs) return
    const id = window.setInterval(() => setTick((t) => t + 1), pollMs)
    return () => window.clearInterval(id)
  }, [pollMs])

  const refresh = useCallback(() => setTick((t) => t + 1), [])

  return { data, source, error, refresh }
}
