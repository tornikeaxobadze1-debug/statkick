import { AnimatePresence, motion } from 'framer-motion'
import { Loader2, LogIn, UserPlus, X } from 'lucide-react'
import { useEffect, useId, useRef, useState, type FormEvent, type Ref } from 'react'
import { ApiError, NetworkError, authApi } from '../api'
import type { User } from '../types'

export type AuthMode = 'signin' | 'register'

interface LoginModalProps {
  open: boolean
  /** Which tab to show when the modal opens. */
  initialMode?: AuthMode
  onClose: () => void
  /** Called with the signed-in user; `created` is true for a brand-new account. */
  onSuccess: (user: User, created: boolean) => void
}

type FieldErrors = Partial<Record<'name' | 'email' | 'password' | 'password_confirmation', string[]>>

const TABS: { mode: AuthMode; label: string }[] = [
  { mode: 'signin', label: 'Sign in' },
  { mode: 'register', label: 'Create account' },
]

/** Sign in / Create account, in one modal with a tab switch. */
export default function LoginModal({ open, initialMode = 'signin', onClose, onSuccess }: LoginModalProps) {
  const [mode, setMode] = useState<AuthMode>(initialMode)
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const [busy, setBusy] = useState(false)
  const firstField = useRef<HTMLInputElement>(null)
  const titleId = useId()
  const registering = mode === 'register'

  // The parent remounts the modal (new `key`) for each opening, so it always starts
  // on the requested tab with no stale errors or passwords.
  useEffect(() => {
    if (!open) return
    const id = window.requestAnimationFrame(() => firstField.current?.focus())
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => {
      window.cancelAnimationFrame(id)
      window.removeEventListener('keydown', onKey)
    }
  }, [open, mode, onClose])

  const switchTo = (next: AuthMode) => {
    setMode(next)
    setError(null)
    setFieldErrors({})
  }

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    setFieldErrors({})
    if (registering && password !== confirm) {
      setFieldErrors({ password_confirmation: ['The passwords don’t match.'] })
      return
    }
    setBusy(true)
    try {
      const user = registering
        ? await authApi.register({ name: name.trim(), email: email.trim(), password, password_confirmation: confirm })
        : await authApi.login({ email: email.trim(), password })
      setPassword('')
      setConfirm('')
      onSuccess(user, registering)
      onClose()
    } catch (err) {
      const fields = err instanceof ApiError ? err.validationErrors : null
      if (fields) {
        // Laravel reports a confirmation mismatch on `password`; show it under the confirm field.
        const { password: pw = [], ...rest } = fields
        const mismatch = pw.filter((m) => /confirm/i.test(m))
        setFieldErrors({ ...rest, password: pw.filter((m) => !mismatch.includes(m)), password_confirmation: mismatch })
      } else if (err instanceof ApiError && err.status === 429) {
        setError('Too many attempts. Please wait a minute and try again.')
      } else if (err instanceof NetworkError) {
        setError('Can’t reach StatKick right now. Check your connection and try again.')
      } else {
        setError(err instanceof Error ? err.message : registering ? 'Couldn’t create your account.' : 'Sign in failed.')
      }
    } finally {
      setBusy(false)
    }
  }

  const hasFieldErrors = Object.values(fieldErrors).some((m) => m?.length)

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          className="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          onMouseDown={(e) => e.target === e.currentTarget && onClose()}
        >
          <motion.form
            role="dialog"
            aria-modal="true"
            aria-labelledby={titleId}
            onSubmit={submit}
            noValidate
            initial={{ y: 30, scale: 0.95, opacity: 0 }}
            animate={{ y: 0, scale: 1, opacity: 1 }}
            exit={{ y: 30, scale: 0.95, opacity: 0 }}
            className="w-full max-w-sm rounded-2xl border border-line bg-surface p-7 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.85)] sm:p-8"
          >
            <div className="mb-6 flex items-start justify-between gap-4">
              <div>
                <p className="label">StatKick account</p>
                <h2 id={titleId} className="font-display mt-1 text-2xl font-medium text-ink">
                  {registering ? 'Create your account' : 'Welcome back'}
                </h2>
              </div>
              <button type="button" onClick={onClose} aria-label="Close" className="mt-1 text-ink-mute transition hover:text-ink">
                <X className="h-5 w-5" />
              </button>
            </div>

            {/* Sign in | Create account */}
            <div role="tablist" aria-label="Account" className="relative mb-6 grid grid-cols-2 rounded-full border border-line bg-canvas p-1">
              {TABS.map((t) => {
                const active = t.mode === mode
                return (
                  <button
                    key={t.mode}
                    type="button"
                    role="tab"
                    aria-selected={active}
                    onClick={() => switchTo(t.mode)}
                    className={`relative z-10 rounded-full py-2 text-[13px] font-medium transition-colors ${active ? 'text-ink' : 'text-ink-mute hover:text-ink-soft'}`}
                  >
                    {active && (
                      <motion.span
                        layoutId="auth-tab"
                        className="absolute inset-0 -z-10 rounded-full border border-crimson/40 bg-crimson-deep/40"
                        transition={{ type: 'spring', stiffness: 500, damping: 38 }}
                      />
                    )}
                    {t.label}
                  </button>
                )
              })}
            </div>

            {registering && (
              <Field ref={firstField} label="Name" type="text" value={name} onChange={setName} errors={fieldErrors.name} autoComplete="name" />
            )}
            <Field
              ref={registering ? undefined : firstField}
              label="Email"
              type="email"
              value={email}
              onChange={setEmail}
              errors={fieldErrors.email}
              autoComplete="email"
            />
            <Field
              label="Password"
              type="password"
              value={password}
              onChange={setPassword}
              errors={fieldErrors.password}
              autoComplete={registering ? 'new-password' : 'current-password'}
            />
            {registering && (
              <Field
                label="Confirm password"
                type="password"
                value={confirm}
                onChange={setConfirm}
                errors={fieldErrors.password_confirmation}
                autoComplete="new-password"
              />
            )}

            {error && !hasFieldErrors && (
              <p role="alert" className="mb-3 text-sm text-loss">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={busy}
              className="mt-2 flex w-full items-center justify-center gap-2 rounded-full bg-crimson-deep py-3 text-sm font-medium text-white transition hover:bg-ruby disabled:opacity-60"
            >
              {busy ? (
                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
              ) : registering ? (
                <UserPlus className="h-4 w-4" aria-hidden />
              ) : (
                <LogIn className="h-4 w-4" aria-hidden />
              )}
              {busy ? (registering ? 'Creating account…' : 'Signing in…') : registering ? 'Create account' : 'Sign in'}
            </button>

            <p className="mt-5 text-center text-xs text-ink-mute">
              {registering ? 'Already have an account? ' : 'New to StatKick? '}
              <button type="button" onClick={() => switchTo(registering ? 'signin' : 'register')} className="font-medium text-crimson-light hover:underline">
                {registering ? 'Sign in' : 'Create an account'}
              </button>
            </p>
          </motion.form>
        </motion.div>
      )}
    </AnimatePresence>
  )
}

function Field({
  ref,
  label,
  type,
  value,
  onChange,
  errors,
  autoComplete,
}: {
  ref?: Ref<HTMLInputElement>
  label: string
  type: string
  value: string
  onChange: (v: string) => void
  errors?: string[]
  autoComplete?: string
}) {
  const id = useId()
  const invalid = !!errors?.length
  return (
    <div className="mb-4">
      <label htmlFor={id} className="label mb-1 block">
        {label}
      </label>
      <input
        ref={ref}
        id={id}
        type={type}
        required
        value={value}
        autoComplete={autoComplete}
        onChange={(e) => onChange(e.target.value)}
        aria-invalid={invalid}
        aria-describedby={invalid ? `${id}-error` : undefined}
        className={`w-full border-b bg-transparent py-2 text-sm text-ink outline-none transition-colors focus:border-crimson ${invalid ? 'border-loss' : 'border-line'}`}
      />
      {invalid && (
        <span id={`${id}-error`} className="mt-1 block text-xs text-loss">
          {errors!.join(' ')}
        </span>
      )}
    </div>
  )
}
