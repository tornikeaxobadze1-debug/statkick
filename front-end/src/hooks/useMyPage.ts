import { useCallback, useEffect, useState } from 'react'

const PARAM = 'me'
/** History entries pushed for My Page, so closing it can use real browser back. */
const PAGE_STATE = 'statkick-me'

const isOpenInUrl = () => new URLSearchParams(window.location.search).has(PARAM)

function urlWith(open: boolean) {
  const q = new URLSearchParams(window.location.search)
  if (open) q.set(PARAM, '1')
  else q.delete(PARAM)
  const search = q.toString()
  return `${window.location.pathname}${search ? `?${search}` : ''}`
}

/**
 * The personal "My Page" view, mirrored into the URL (?me=1) so back/forward and
 * reloads keep it. Explorer navigation (which rewrites the URL) leaves the page via
 * `leave()`, so browser back returns to it.
 */
export function useMyPage() {
  const [open, setOpen] = useState(isOpenInUrl)

  useEffect(() => {
    const onPop = () => setOpen(isOpenInUrl())
    window.addEventListener('popstate', onPop)
    return () => window.removeEventListener('popstate', onPop)
  }, [])

  const show = useCallback(() => {
    if (!isOpenInUrl()) window.history.pushState(PAGE_STATE, '', urlWith(true))
    setOpen(true)
    window.scrollTo({ top: 0 })
  }, [])

  /** Close: pop our own history entry when we pushed it, else drop the param in place. */
  const close = useCallback(() => {
    if (window.history.state === PAGE_STATE) {
      window.history.back()
      return
    }
    window.history.replaceState(null, '', urlWith(false))
    setOpen(false)
  }, [])

  /** Hide without touching history: the caller is about to push a new URL. */
  const leave = useCallback(() => setOpen(false), [])

  return { open, show, close, leave }
}
