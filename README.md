# StatKick

European football intelligence: the top five leagues (England, Spain, Italy, Germany,
France), 17 elite clubs and 340 players, each with a 360° profile — market value,
contract, salary, position and role, international caps, honours, career transfers with
a market-value trajectory, simulated season stats and a real cut-out photo. Users can
register, sign in and keep favorite players and clubs.

The repository ships ready to run: the populated SQLite database and all 333 player
cut-out images are committed, so no seeding or API keys are needed.

## Tech stack

| Part | Stack |
| --- | --- |
| `front-end/` | React 19 + TypeScript, Vite, Tailwind CSS |
| `back-end/` | Laravel 13 (PHP 8.3+), Sanctum token auth, SQLite |

## Requirements

- PHP **8.3+** with the `pdo_sqlite`, `sqlite3`, `mbstring`, `intl` and `fileinfo` extensions
- [Composer](https://getcomposer.org/)
- Node.js **20+** (24 recommended) and npm

## Setup

Run the API and the web app in two terminals.

### 1. API (`back-end/`)

```bash
cd back-end
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
php artisan serve
```

The API is now at <http://localhost:8000/api> (try <http://localhost:8000/api/countries>).
`storage:link` makes the player photos available at `/storage/cutouts/…`.

### 2. Web app (`front-end/`)

```bash
cd front-end
cp .env.example .env
npm install
npm run dev
```

Open <http://localhost:5173>. In development Vite proxies `/api` and `/storage` to
`http://localhost:8000` (set `API_PROXY_TARGET` in `front-end/.env` to point elsewhere).

### Demo account

`test@example.com` / `password` — or register your own account in the app.

## API overview

| Method | Path | |
| --- | --- | --- |
| GET | `/api/countries`, `/api/leagues`, `/api/leagues/{id}` | Countries and leagues with their clubs and fixtures |
| GET | `/api/teams`, `/api/teams/{id}` | Clubs with standings, squad and matches |
| GET | `/api/players`, `/api/players/{id}` | Players with full profiles (`?team_id=` filters a squad) |
| POST | `/api/register`, `/api/login` | Returns `{ user, token, token_type }` |
| GET | `/api/user` | The signed-in user with favorite players and clubs (Bearer token) |
| GET / POST / PUT / DELETE | `/api/favorites`, `/api/favorites/players/{id}`, `/api/favorites/teams/{id}` | List, toggle, save, remove favorites |

## Useful commands (`back-end/`)

```bash
php artisan test                 # full test suite (uses an in-memory database)
php artisan roster:audit         # data-integrity report: squads, duplicates, profiles, photos
php artisan football:reseed      # rebuild football data, keeping users and their favorites
```

`php artisan migrate:fresh --seed` also works, but it wipes registered users — prefer
`football:reseed`. Rebuilding uses the committed TheSportsDB cache
(`back-end/database/seeders/data/player_cutouts.json`) and needs no network access;
`php artisan football:sync-tsdb` refreshes that cache from TheSportsDB.

## About the data

- **Squads and photos** are verified against [TheSportsDB](https://www.thesportsdb.com/)
  and a reviewed list of corrections (`back-end/database/seeders/data/football_profiles.php`).
  The five Premier League squads are locked to confirmed lists.
- **Profile facts** (market values, wages, contracts, caps, transfer fees) combine
  TheSportsDB data with hand-compiled, approximate figures. Every value filled by an
  estimate is listed in the player's `profile_estimates` field.
- **Match results and season stats are simulated**, so the league tables are realistic
  but not real.
- Player cut-out photos come from TheSportsDB's community uploads and may be subject to
  copyright; check licensing before any public or commercial use.
