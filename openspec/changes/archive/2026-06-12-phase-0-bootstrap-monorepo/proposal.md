## Why

The Carbon-ESG repo currently contains only the original raw PHP site (preserved under `legacy/` in commit `f052705`). Before any new feature work — auth, carbon listings, jobs, web3 — can begin, the new monorepo skeleton has to exist. This change creates that skeleton: a Laravel 12 backend, a Next.js 16 frontend, a docker-compose for local dependencies, and the root tooling files (`.nvmrc`, `.env.example`, `.gitignore`) every subsequent phase will rely on. It also commits the high-level architectural decisions to `CLAUDE.md` so the AI tools and future contributors know the rules.

This is the first OpenSpec change of the rewrite. Subsequent phases (`phase-1-auth-sanctum`, `phase-2-carbon-listings`, etc.) each get their own change and build on top of this skeleton.

## What Changes

- Scaffold Laravel 12 at `/backend` via `composer create-project`. Install Sanctum and Pest as dev tooling. Configure CORS for the SPA frontend and set MySQL as the default DB connection.
- Scaffold Next.js 16 at `/frontend` via `pnpm create next-app` with TypeScript, App Router, ESLint, and Tailwind v4. Add a Sanctum-aware axios client at `lib/api.ts`.
- Add `docker-compose.yml` at the repo root with `mysql:8`, `redis:7`, and `axllent/mailpit` services so contributors can boot the full local dependency stack in one command.
- Add root tooling: `.nvmrc` (Node 20.19.0), `.env.example` (docker-compose overrides), `.gitignore` (covers `vendor/`, `node_modules/`, `.env`, etc.).
- Add `CLAUDE.md` at repo root documenting the refactor decisions (Laravel 12 + Next.js 16 + Sanctum + MySQL + backend-mediated Web3) and the OpenSpec discipline that every cross-file change must follow.

## Capabilities

### New Capabilities
- `bootstrap`: repo skeleton, local dependency stack via docker-compose, root tooling, and the rules around them.

### Modified Capabilities
- (none — this is the first change of the rewrite; no existing capabilities to modify.)

## Impact

- **Affected paths**: new `/backend/`, `/frontend/`, `docker-compose.yml`, `.nvmrc`, `.env.example`, `.gitignore`, `CLAUDE.md`. Existing `legacy/` and `README.md` are untouched.
- **Production impact**: none — the original PHP site under `legacy/` is unaffected and continues to run wherever it was deployed (if anywhere). The new stack runs only locally until later phases.
- **Downstream dependency**: every future change (`phase-1-*` onward) depends on this scaffold landing.
- **Required tooling for contributors**: Docker, Node ≥ 20.19.0, PHP 8.5 + Composer, pnpm. Listed in `README.md` (updated in a later commit).
