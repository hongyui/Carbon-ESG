# bootstrap Specification

## Purpose

Defines how the Carbon-ESG repository is laid out as a monorepo (`legacy/` + `backend/` + `frontend/`), how contributors boot the local dependency stack (Docker Compose with MySQL, Redis, Mailpit), how the Node.js toolchain version is pinned, and where architectural decisions and the OpenSpec discipline are documented. This capability covers Phase 0 scaffolding — everything required for a fresh clone to get to a working dev environment — and the conventions every subsequent capability (auth, carbon-listings, jobs, web3) builds on top of.

## Requirements

### Requirement: Three Top-Level Code Directories

After this change the repository SHALL contain exactly three top-level directories that hold code: `legacy/` (frozen mirror of the original PHP site, preserved as of commit `f052705`), `backend/` (Laravel 12 skeleton), and `frontend/` (Next.js 16 skeleton). All other root entries are tooling, configuration, or documentation.

#### Scenario: Backend skeleton boots

- **WHEN** a developer runs `composer install && php artisan serve` inside `backend/`
- **THEN** the Laravel skeleton serves a 200 response at `http://localhost:8000`

#### Scenario: Frontend skeleton boots

- **WHEN** a developer runs `pnpm install && pnpm dev` inside `frontend/`
- **THEN** the Next.js skeleton serves a 200 response at `http://localhost:3000`

#### Scenario: Legacy directory is untouched

- **WHEN** the change is merged
- **THEN** `legacy/` retains every file it had at commit `f052705` with git history preserved

### Requirement: Local Dependency Stack via Docker Compose

The repo root SHALL contain a `docker-compose.yml` defining `mysql:8`, `redis:7`, and `axllent/mailpit` services (mailpit is the actively-maintained, multi-arch successor to MailHog — native arm64 image avoids qemu emulation on Apple Silicon) so contributors can boot the full local stack with one command. Compose port mappings MUST read from environment variables so contributors can override defaults without editing the compose file.

#### Scenario: Compose up boots required services

- **WHEN** a developer runs `docker compose up -d mysql redis`
- **THEN** both services report healthy within 30 seconds

#### Scenario: Backend connects to compose MySQL out of the box

- **GIVEN** docker compose mysql is running with the defaults from the root `.env.example`
- **WHEN** a developer runs `php artisan migrate` inside `backend/` after copying `.env.example` to `.env`
- **THEN** migrations apply against the docker MySQL instance without further configuration

### Requirement: Node Version Pinned

The repo SHALL pin the Node.js version required by the frontend and by OpenSpec (≥ 20.19.0) via a root `.nvmrc` file and a `package.json` `engines` field in `frontend/`.

#### Scenario: nvm picks the right version

- **WHEN** a developer runs `nvm use` at the repo root
- **THEN** nvm activates a Node version ≥ 20.19.0

### Requirement: Architectural Decisions Captured in CLAUDE.md

The repo root SHALL contain a `CLAUDE.md` file that documents the stack decisions (Laravel 12, Next.js 16, Sanctum SPA, MySQL 8, backend-mediated Web3), the OpenSpec discipline (every cross-file change goes through `openspec/changes/`), and the local-dev quick start commands.

#### Scenario: CLAUDE.md is discoverable

- **WHEN** a new contributor or AI tool opens the repo
- **THEN** `CLAUDE.md` is at the root and references `openspec/specs/` as the source of truth for capability requirements
