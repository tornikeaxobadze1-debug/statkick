/**
 * Native fetch client for the StatKick Laravel API (Sanctum token auth).
 *
 * Base URL comes from VITE_API_URL (see .env.example). In dev it is the same-origin
 * `/api`, which Vite proxies to Laravel (vite.config.ts); absolute URLs also work.
 */
import type {
  AuthResponse,
  CountryTree,
  Division,
  DivisionDetail,
  Favorites,
  FavoriteToggle,
  ID,
  LoginPayload,
  RegisterPayload,
  Match,
  MatchDetail,
  MatchStatus,
  Paginated,
  Player,
  PlayerDetail,
  Team,
  TeamDetail,
  User,
  ValidationErrorBody,
} from '../types'

export const API_BASE_URL: string = (
  import.meta.env.VITE_API_URL ?? '/api'
).replace(/\/+$/, '')

const TOKEN_KEY = 'statkick_token'
const DEFAULT_TIMEOUT_MS = 10_000

/* ---------------- Token storage ---------------- */

export const tokenStorage = {
  get(): string | null {
    try {
      return localStorage.getItem(TOKEN_KEY)
    } catch {
      return null
    }
  },
  set(token: string): void {
    try {
      localStorage.setItem(TOKEN_KEY, token)
    } catch {
      /* storage unavailable (private mode) — token lives for this request only */
    }
  },
  clear(): void {
    try {
      localStorage.removeItem(TOKEN_KEY)
    } catch {
      /* ignore */
    }
  },
}

/* ---------------- Errors ---------------- */

export class ApiError extends Error {
  readonly status: number
  readonly body: unknown

  constructor(status: number, message: string, body: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }

  get isUnauthorized(): boolean {
    return this.status === 401
  }

  /** Field errors from a Laravel 422 response, if any. */
  get validationErrors(): Record<string, string[]> | null {
    if (this.status !== 422) return null
    const body = this.body as Partial<ValidationErrorBody> | null
    return body?.errors ?? null
  }
}

/** Thrown when the API can't be reached at all (offline, CORS, timeout). */
export class NetworkError extends Error {
  constructor(message: string, options?: { cause?: unknown }) {
    super(message, options)
    this.name = 'NetworkError'
  }
}

/* ---------------- Core request ---------------- */

export type QueryParams = Record<string, string | number | boolean | null | undefined>

export interface RequestOptions extends Omit<RequestInit, 'body' | 'method'> {
  query?: QueryParams
  timeoutMs?: number
}

type Method = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

/** Listeners notified when the API answers 401 (token expired/revoked). */
const unauthorizedListeners = new Set<() => void>()
export function onUnauthorized(listener: () => void): () => void {
  unauthorizedListeners.add(listener)
  return () => unauthorizedListeners.delete(listener)
}

function buildUrl(path: string, query?: QueryParams): string {
  // A relative base ("/api") resolves against the page's own origin.
  const url = new URL(`${API_BASE_URL}/${path.replace(/^\/+/, '')}`, window.location.origin)
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== null) url.searchParams.set(key, String(value))
    }
  }
  return url.toString()
}

async function parseBody(res: Response): Promise<unknown> {
  if (res.status === 204) return null
  const text = await res.text()
  if (!text) return null
  const type = res.headers.get('Content-Type') ?? ''
  if (type.includes('application/json')) {
    try {
      return JSON.parse(text)
    } catch {
      return text
    }
  }
  return text
}

async function request<T>(
  method: Method,
  path: string,
  body?: unknown,
  { query, timeoutMs = DEFAULT_TIMEOUT_MS, headers, signal, ...init }: RequestOptions = {},
): Promise<T> {
  const finalHeaders = new Headers(headers)
  finalHeaders.set('Accept', 'application/json')
  // Laravel needs this to treat the request as XHR (JSON errors, not redirects).
  finalHeaders.set('X-Requested-With', 'XMLHttpRequest')

  const isFormData = body instanceof FormData
  if (body !== undefined && !isFormData) finalHeaders.set('Content-Type', 'application/json')

  const token = tokenStorage.get()
  if (token) finalHeaders.set('Authorization', `Bearer ${token}`)

  const timeoutSignal = AbortSignal.timeout(timeoutMs)
  const finalSignal = signal ? AbortSignal.any([signal, timeoutSignal]) : timeoutSignal

  let res: Response
  try {
    res = await fetch(buildUrl(path, query), {
      ...init,
      method,
      headers: finalHeaders,
      signal: finalSignal,
      body: body === undefined ? undefined : isFormData ? body : JSON.stringify(body),
    })
  } catch (err) {
    // Caller-initiated aborts propagate untouched so they can be ignored.
    if (signal?.aborted) throw err
    const reason = timeoutSignal.aborted ? `timed out after ${timeoutMs}ms` : 'is unreachable'
    throw new NetworkError(`StatKick API ${reason} (${API_BASE_URL})`, { cause: err })
  }

  const data = await parseBody(res)

  if (!res.ok) {
    if (res.status === 401) {
      tokenStorage.clear()
      unauthorizedListeners.forEach((fn) => fn())
    }
    const message =
      (data && typeof data === 'object' && 'message' in data && typeof data.message === 'string'
        ? data.message
        : null) ?? `${method} ${path} failed with ${res.status}`
    throw new ApiError(res.status, message, data)
  }

  return data as T
}

/* ---------------- Typed helpers ---------------- */

export const api = {
  get: <T>(path: string, options?: RequestOptions) => request<T>('GET', path, undefined, options),
  post: <T, B = unknown>(path: string, body?: B, options?: RequestOptions) =>
    request<T>('POST', path, body, options),
  put: <T, B = unknown>(path: string, body?: B, options?: RequestOptions) =>
    request<T>('PUT', path, body, options),
  patch: <T, B = unknown>(path: string, body?: B, options?: RequestOptions) =>
    request<T>('PATCH', path, body, options),
  delete: <T = null>(path: string, options?: RequestOptions) =>
    request<T>('DELETE', path, undefined, options),
}

/* ---------------- Resource endpoints (see back-end/routes/api.php) ---------------- */

export const authApi = {
  /** POST /api/login — stores the Sanctum token on success. */
  async login(payload: LoginPayload): Promise<User> {
    const res = await api.post<AuthResponse, LoginPayload>('login', {
      device_name: 'statkick-web',
      ...payload,
    })
    tokenStorage.set(res.token)
    return res.user
  },
  /** POST /api/register — creates the account and stores its Sanctum token, like login. */
  async register(payload: RegisterPayload): Promise<User> {
    const res = await api.post<AuthResponse, RegisterPayload>('register', {
      device_name: 'statkick-web',
      ...payload,
    })
    tokenStorage.set(res.token)
    return res.user
  },
  /** POST /api/logout — revokes the current token. */
  async logout(): Promise<void> {
    try {
      await api.post<{ message: string }>('logout')
    } finally {
      tokenStorage.clear()
    }
  },
  /** GET /api/me */
  me: (options?: RequestOptions) => api.get<User>('me', options),
}

export const countriesApi = {
  /** GET /api/countries — Country → League → Team tree, alphabetical. */
  list: (options?: RequestOptions) => api.get<CountryTree[]>('countries', options),
}

export const playersApi = {
  /** GET /api/players?team_id= — players with career totals, best average rating first. */
  list: (filters: { team_id?: ID } = {}, options?: RequestOptions) =>
    api.get<Player[]>('players', {
      // The unfiltered list is ~0.9 MB: allow slow connections more than the default.
      timeoutMs: filters.team_id ? undefined : 30_000,
      ...options,
      query: { ...filters },
    }),
  /** GET /api/players/{id} — career history and per-match stats. */
  show: (id: ID, options?: RequestOptions) => api.get<PlayerDetail>(`players/${id}`, options),
}

export const teamsApi = {
  /** GET /api/teams?division_id= — teams with standings record and recent form. */
  list: (filters: { division_id?: ID } = {}, options?: RequestOptions) =>
    api.get<Team[]>('teams', { ...options, query: { ...filters } }),
  /** GET /api/teams/{id} — squad and fixtures. */
  show: (id: ID, options?: RequestOptions) => api.get<TeamDetail>(`teams/${id}`, options),
}

export const divisionsApi = {
  list: (options?: RequestOptions) => api.get<Division[]>('divisions', options),
  /** GET /api/divisions/{id} — teams plus upcoming and past fixtures. */
  show: (id: ID, options?: RequestOptions) => api.get<DivisionDetail>(`divisions/${id}`, options),
}

export interface MatchFilters {
  status?: MatchStatus
  division_id?: ID
  per_page?: number
  page?: number
}

export const matchesApi = {
  /** GET /api/matches?status=&division_id=&per_page= (newest first) */
  list: (filters: MatchFilters = {}, options?: RequestOptions) =>
    api.get<Paginated<Match>>('matches', { ...options, query: { ...filters } }),
  /** GET /api/matches?status=LIVE */
  live: (options?: RequestOptions) =>
    api.get<Paginated<Match>>('matches', { ...options, query: { status: 'LIVE', per_page: 10 } }).then((p) => p.data),
  /** GET /api/matches/{id} — includes per-player stats, best rating first. */
  show: (id: ID, options?: RequestOptions) => api.get<MatchDetail>(`matches/${id}`, options),
}

export const favoritesApi = {
  /** GET /api/user/favorites (auth) */
  list: (options?: RequestOptions) => api.get<Favorites>('user/favorites', options),
  /** POST /api/user/favorites/players/{id} (auth) — toggles. */
  togglePlayer: (playerId: ID) => api.post<FavoriteToggle>(`user/favorites/players/${playerId}`),
  /** POST /api/user/favorites/teams/{id} (auth) — toggles. */
  toggleTeam: (teamId: ID) => api.post<FavoriteToggle>(`user/favorites/teams/${teamId}`),
}
