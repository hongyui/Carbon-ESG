## 1. CLAUDE.md First

- [x] 1.1 Write `CLAUDE.md` at repo root covering: project overview, stack decisions (Laravel 12 / Next.js 16 / Sanctum / MySQL 8 / backend-mediated Web3), directory layout, naming conventions, OpenSpec discipline, local-dev quick start, security red lines
- [x] 1.2 Commit as `chore: add CLAUDE.md with refactor decisions`

## 2. Backend Scaffold

- [x] 2.1 Run `composer create-project laravel/laravel backend "^12.0"`
- [x] 2.2 `composer require laravel/sanctum` inside `backend/`
- [x] 2.3 `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- [x] 2.4 `php artisan install:api --without-migration-prompt` to scaffold `routes/api.php`
- [x] 2.5 Edit `backend/config/database.php` so the default connection env falls back to `mysql` instead of `sqlite`
- [x] 2.6 Write `backend/config/cors.php` with `FRONTEND_URL` allowed origin, `supports_credentials: true`, and `paths` covering `api/*` and `sanctum/csrf-cookie`
- [x] 2.7 Update `backend/.env.example`: APP_URL=:8000, FRONTEND_URL=:3000, SANCTUM_STATEFUL_DOMAINS=localhost:3000, DB_CONNECTION=mysql + DB defaults matching docker-compose
- [x] 2.8 Empty `routes/web.php` (SPA owns browser routes) and add `GET /api/health` to `routes/api.php`
- [x] 2.9 `composer require pestphp/pest --dev --with-all-dependencies` and `./vendor/bin/pest --init`
- [x] 2.10 Edit `database/seeders/DatabaseSeeder.php` to use `User::factory()->createOrFirst([...])` so re-runs are idempotent
- [x] 2.11 Commit as `feat(backend): scaffold Laravel 12 with Sanctum, CORS, Pest`

## 3. Frontend Scaffold

- [x] 3.1 Run `pnpm create next-app@latest frontend --typescript --eslint --tailwind --app --no-src-dir --import-alias="@/*" --use-pnpm --yes`
- [x] 3.2 Replace default `app/page.tsx` with a minimal "Hello, Carbon-ESG" homepage surfacing `NEXT_PUBLIC_API_URL`
- [x] 3.3 `pnpm add axios` and create `frontend/lib/api.ts` with `withCredentials: true`, `withXSRFToken: true`, `xsrfCookieName: "XSRF-TOKEN"`, `xsrfHeaderName: "X-XSRF-TOKEN"`, plus an `ensureCsrfCookie()` helper
- [x] 3.4 Add `frontend/.env.example` with `NEXT_PUBLIC_API_URL=http://localhost:8000`
- [x] 3.5 Add `"engines": { "node": ">=20.19.0" }` to `frontend/package.json`
- [x] 3.6 Commit as `feat(frontend): scaffold Next.js 16 with axios client`

## 4. Root Tooling

- [x] 4.1 Write `docker-compose.yml` at repo root with `mysql:8`, `redis:7`, `axllent/mailpit`; port mappings read from env (`${DB_PORT:-3306}` etc.); health checks on mysql and redis
- [x] 4.2 Add `.nvmrc` containing `20.19.0`
- [x] 4.3 Add root `.env.example` documenting docker-compose overrides (DB_*, REDIS_PORT, MAILPIT_*)
- [x] 4.4 Add root `.gitignore` covering `vendor/`, `node_modules/`, `.next/`, `.env`, `.env.local`, `backend/storage/*.key`, `backend/storage/framework/cache/data/*`, `.DS_Store`, etc.
- [x] 4.5 Commit as `chore: add docker-compose, .nvmrc, .gitignore, .env.example`

## 5. Verification

- [x] 5.1 `docker compose config --quiet` exits 0 (compose syntax valid)
- [x] 5.2 `cd backend && composer install` exits 0 on a clean clone
- [x] 5.3 `cd backend && php artisan route:list --path=api` shows `/api/health`
- [x] 5.4 `cd frontend && pnpm install --frozen-lockfile` exits 0
- [x] 5.5 `openspec validate --all` passes (4 capability specs + 1 change)
