## Context

The Carbon-ESG repo holds the original 2023 PHP site under `legacy/` (commit `f052705`) and an initialized OpenSpec workflow (commit `9e6831f`). Beyond that, nothing of the new stack exists. We agreed in conversation that the rewrite targets Next.js 16 + Laravel 12 + Sanctum + MySQL + backend-mediated Web3 — but none of that is written down yet (no `CLAUDE.md`), and there is no domain spec capability beyond `bootstrap` itself.

This change is structural only. It does not touch any business logic, does not migrate any data from the legacy site, and does not implement any of the four planned domain capabilities (`auth`, `carbon-listings`, `jobs`, `web3`). Those each get their own change in the next phases.

## Goals / Non-Goals

**Goals:**
- Repo has exactly three top-level code directories after this change: `legacy/`, `backend/`, `frontend/`.
- `composer install` succeeds inside `backend/` on a clean clone with no security-advisory warnings (which is why we land on Laravel 12, not 11).
- `pnpm install` succeeds inside `frontend/` on a clean clone with Node ≥ 20.19.0.
- `docker compose up -d mysql redis` boots both services with health checks passing.
- `CLAUDE.md` exists at the repo root and captures: stack decisions, OpenSpec discipline, naming conventions, local dev quick start, security red lines.
- After this change merges, a contributor cloning the repo can run four shell commands and reach `http://localhost:8000/api/health` returning JSON.

**Non-Goals:**
- No auth, listings, jobs, or web3 features.
- No domain specs beyond `bootstrap`. The `auth` / `carbon-listings` / `jobs` / `web3` capabilities are created in their respective phase changes.
- No data migration from legacy MySQL into the new schema (Phase 5's job).
- No CI pipeline. GitHub Actions can be added in a later phase.
- No production deployment scripts.

## Decisions

- **Laravel 12, not 11 or 13.** Laravel 11.x is currently blocked on Composer security advisories on every published release. Laravel 13 just shipped and the surrounding ecosystem (Sanctum, Pest, web3.php) is less stable. Laravel 12 is the closest clean, well-documented release.
- **Next.js 16 (whatever `create-next-app@latest` ships).** Conversation originally targeted 15 but `create-next-app@latest` ships 16 now and the surface we use (App Router, server components, Tailwind v4) is fully covered. We pin 16 explicitly.
- **pnpm, not npm/yarn, for the frontend.** Lockfile size, install speed, and strict node_modules layout matter once we add shadcn/ui + viem in later phases.
- **Sanctum SPA mode, not token mode, not Passport, not JWT.** Cookies on the same origin avoid the XSS-on-localStorage problem. The token / OAuth2 alternatives are heavier and only worth it when third-party API exposure is needed — we have no such requirement.
- **Backend-mediated Web3 via `web3p/web3.php`.** Buyers should not need MetaMask. The platform wallet signs every settlement transaction via a queued job. (Implementation is in Phase 4; the decision is locked here.)
- **MySQL 8, not sqlite, even for local dev.** The schema we will design relies on real `decimal`, `enum`, `json` semantics. sqlite would silently accept and behave differently. docker-compose makes MySQL one command away.
- **`docker-compose.yml` lives at the repo root, not under `backend/`.** Both `backend/` and `frontend/` consume the same services; having compose at the root makes ownership obvious.
- **`CLAUDE.md` at the repo root, not under `.claude/`.** Conventional location for AI agent instructions; works with Claude Code and also as a human-readable reference.

## Risks / Trade-offs

- **Risk:** `composer create-project laravel/laravel "^11.0"` will fail loudly because of the Composer advisories. **Mitigation:** the `tasks.md` specifies `^12.0` explicitly. If a contributor copy-pastes the wrong version from memory, the error message points clearly at the advisory and the fix is one digit.
- **Risk:** `pnpm create next-app@latest` is a moving target — what ships today might be 17 tomorrow. **Mitigation:** we pin the resolved version in `frontend/package.json` after the scaffold so a later `pnpm install` reproduces. The proposal already notes "16 or newer".
- **Risk:** `docker-compose.yml` exposes MySQL on `3306` by default; a contributor with another MySQL already on that port will collide. **Mitigation:** the compose file reads `DB_PORT` from env so it can be overridden in the root `.env`.
- **Trade-off:** The new `/backend` and `/frontend` directories are empty of business logic at the end of this change. The repo "looks done" but actually nothing works yet. We accept this — Phase 0 is intentionally just structure.
- **Trade-off:** Choosing Laravel 12 instead of 13 puts us slightly behind the bleeding edge but on much more solid documentation. Worth it for a brownfield rewrite.
