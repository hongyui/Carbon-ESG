## Why

[Phase-2](../archive/2026-06-14-phase-2-carbon-listings/proposal.md) shipped the buyer-side commercial loop: seller 上架 → admin 審核 → buyer 購買 → 系統 `sold`. The platform now has a working marketplace but the carbon credits sold this way are still **promises on paper** — there is no operational mechanism to keep the land that backs them actually maintained. The legacy site (`legacy/registJob.php`, `legacy/jobrecall.php`) had a fourth role for exactly this: **工人 (worker)** picks up an approved-and-sold piece of land, goes out and tidies it, then submits before / after photos so admin can sign off that the carbon credits are still legitimate.

Phase-3 ships that loop. It is the **operational backbone** that turns a `sold` listing from a database row into a real-world maintained plot. Without it the platform would be selling credits with no way to demonstrate the land is being kept in a state that justifies them, and the four-role design from `CLAUDE.md` would never have its fourth role.

Phase-3 also introduces the **first file upload surface** in the new stack — work reports require front / back environment photos. Phase-2 explicitly deferred image upload; phase-3 picks it up because reports are unusable without it. We commit to the conservative end of the storage path (local disk + `storage:link`, validate `image|mimes:jpg,jpeg,png|max:5120` per `CLAUDE.md`安全紅線 #4); the S3-compatible bucket move is a separate later phase.

Web3 settlement is still deferred — `phase-4-web3` will wrap the existing `POST /api/carbon-listings/{id}/purchase` and the new `POST /api/admin/job-reports/{id}/approve` endpoint with on-chain mint / transfer calls. Phase-3 lands as a complete DB-only flow that phase-4 will graft a contract layer onto.

## What Changes

**Backend** (`backend/`):

- New `worker_applications` migration + `WorkerApplication` Eloquent model with status enum (`pending` / `approved` / `rejected`) and a `transitionTo()` state-machine method modeled on phase-2's `CarbonListing`
- New `worker_jobs` migration + `WorkerJob` Eloquent model with status enum (`open` / `claimed` / `reported` / `approved` / `rejected`), a 1:1 relationship to `CarbonListing`, and nullable `worker_id` until claimed
- New `worker_job_reports` migration + `WorkerJobReport` Eloquent model with status enum (`pending` / `approved` / `rejected`), 1:1 to `WorkerJob`, holding `datetime_start` / `datetime_end` / `before_image_path` / `after_image_path` / `content` / review fields
- `carbon_listings` table gains `needs_workers tinyint(1) default 0` column via a new migration. Listing creation form lets the seller flag this at submission
- `User` model gains `isWorker()` helper (row-existence check on `WorkerApplication` with `status=approved`) — same role-inference pattern as phase-2's `isSeller()` / `hasPurchased()`
- `CarbonListing` model gains a `saving` listener that, when status transitions to `sold` and `needs_workers=1`, atomically creates the corresponding `WorkerJob(status=open)` inside the same transaction as the purchase
- `WorkerApplicationPolicy` covering `view` / `approve` / `reject` (admin-only on approve/reject; user can only view their own)
- `WorkerJobPolicy` covering `claim` / `view` / `report` (claim requires `isWorker` + `status=open`; report requires `worker_id === Auth::id()` + `status=claimed`)
- `WorkerJobReportPolicy` covering `approve` / `reject` (admin-only)
- New endpoints (all under `auth:sanctum`):
  - `POST /api/worker-applications` — apply (one row per user, enforced by UNIQUE)
  - `GET /api/worker-applications/mine` — see my application status
  - `GET /api/admin/worker-applications/pending` — admin queue
  - `POST /api/admin/worker-applications/{id}/approve` — admin transition
  - `POST /api/admin/worker-applications/{id}/reject` — admin transition, optional `reason`
  - `GET /api/worker-jobs/open` — open job queue (visible only to approved workers)
  - `GET /api/worker-jobs/mine` — my claimed / reported / reviewed jobs
  - `GET /api/worker-jobs/{id}` — detail (claim button conditional)
  - `POST /api/worker-jobs/{id}/claim` — claim (open → claimed, sets `worker_id`)
  - `POST /api/worker-jobs/{id}/report` — multipart submit report (creates `WorkerJobReport`; report-once enforced by UNIQUE)
  - `GET /api/admin/job-reports/pending` — admin queue for pending reports
  - `POST /api/admin/job-reports/{id}/approve` — admin transition
  - `POST /api/admin/job-reports/{id}/reject` — admin transition with optional `reason`; resets `WorkerJob` to `claimed` so worker can resubmit
- `GET /api/me` response **extended** with the new `isWorker` boolean flag (joining the existing `isAdmin` / `isSeller` / `hasPurchased`)
- Pest tests covering each endpoint × happy path + at least one authorization / state failure case + one image-upload validation failure case

**Frontend** (`frontend/`):

- `lib/types/user.ts` — `User` gains optional `isWorker` boolean flag
- `lib/session/getSessionFromCookies()` — propagates the extended user shape
- New route group `(protected)/worker/`:
  - `apply/page.tsx` — application form using `<Field>` × 5 (reason / has_experience radio / age / residence / contact). Redirects to `/worker/apply/status` if user already has a row
  - `apply/status/page.tsx` — shows current `WorkerApplication` with `<StatusBadge>`
  - `jobs/page.tsx` — open job queue (server-side gated by `isWorker`; non-worker redirected to `/worker/apply`)
  - `jobs/[id]/page.tsx` — detail card. Claim button conditional on `status=open`
  - `jobs/[id]/report/page.tsx` — `'use client'` multipart form with `datetime-local × 2` + two file inputs (front / back) + textarea. Posts via `multipart/form-data`
  - `jobs/mine/page.tsx` — my jobs grouped by status
- New route group `(protected)/admin/`:
  - `worker-applications/page.tsx` — pending applications with 核准 / 退件 inline actions
  - `job-reports/page.tsx` — pending reports with before / after thumbnail preview + 核准 / 退件 inline actions
- `frontend/components/AppHeader.tsx` updated:
  - Adds `工人申請` link if `!isWorker && !hasPendingApplication` (rendered via a small client check)
  - Adds `工作機會` link if `isWorker`
  - Adds `工作回報審核` link in the admin block
- `frontend/app/(protected)/seller/listings/new/page.tsx` updated: add `needs_workers` checkbox before submit
- `frontend/app/(protected)/me/page.tsx` quick-link section updated with the new worker / admin routes

**Out of scope** (留給後續 phase):

- **Web3 settlement on report approval** — phase-3 admin-approve writes only to the DB. Phase-4 wraps it in the on-chain settlement
- **S3-compatible storage** — phase-3 uses Laravel local disk + `storage:link`. Bucket move is a later phase (or a small `phase-3.5-storage` if it's blocking deploy)
- **Email notifications** on application / job / report status changes — Mailpit wiring stays observation-only
- **Worker rating / quality scoring** — no `worker_quality` column; reputation system out of scope
- **Worker rejection → reapply UX** — a rejected `WorkerApplication` cannot be replaced; admin would have to delete the row. v1 keeps it strict (one application per user, period)
- **Multiple workers per job** — `WorkerJob.worker_id` is single-valued; only one worker per piece of land. Squad work patterns are out of scope
- **Job auto-expiry** — `open` jobs do not time out. If a `sold` listing's land is never claimed, the job sits forever
- **Replacing rejected reports without admin intervention** — when admin rejects a report, the `WorkerJob` returns to `claimed` so the same worker can submit a new report; the rejected report stays in DB for audit. Re-assigning the job to a different worker is out of scope

## Capabilities

### New Capabilities

- `jobs`: the operational maintenance loop — worker application + admin review, job availability driven by `sold` + `needs_workers`, claim / report state machine, work-report admin review, file-upload validation, and the corresponding backend endpoints + frontend surfaces

### Modified Capabilities

- `auth`: extends the `GET /api/me` Current-Session Endpoint to include `isWorker` boolean flag so the frontend can do worker-aware rendering without an extra round trip. Existing scenarios for `/api/me` shape (`{ user: { id, name, email, isAdmin, isSeller, hasPurchased } }`) remain valid; the response gains the fourth flag
- `carbon-listings`: adds a `needs_workers` boolean attribute to the listing creation contract and the listing detail response. Adds a side-effect requirement on the `pending → sold` transition: when `needs_workers=true`, the transition MUST atomically create a `WorkerJob(status=open)` inside the same DB transaction. Existing listing scenarios remain valid; the create / detail payloads gain the new attribute

## Impact

**New paths** (backend):
- `backend/app/Models/{WorkerApplication,WorkerJob,WorkerJobReport}.php`
- `backend/app/Policies/{WorkerApplicationPolicy,WorkerJobPolicy,WorkerJobReportPolicy}.php`
- `backend/app/Http/Controllers/WorkerApplicationController.php`
- `backend/app/Http/Controllers/WorkerJobController.php`
- `backend/app/Http/Controllers/Admin/WorkerApplicationReviewController.php`
- `backend/app/Http/Controllers/Admin/WorkerJobReportReviewController.php`
- `backend/app/Http/Requests/Worker/{CreateApplicationRequest,SubmitReportRequest,RejectRequest}.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_worker_applications_table.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_worker_jobs_table.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_worker_job_reports_table.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_add_needs_workers_to_carbon_listings_table.php`
- `backend/database/factories/{WorkerApplicationFactory,WorkerJobFactory,WorkerJobReportFactory}.php`
- `backend/tests/Feature/Worker/` (≥ 4 test files for application / claim / report / admin review)
- `backend/storage/app/public/job-reports/` (created by `php artisan storage:link`, plus `.gitignore` so uploaded files don't enter version control)

**New paths** (frontend):
- `frontend/app/(protected)/worker/{apply/page,apply/status/page,jobs/page,jobs/[id]/page,jobs/[id]/report/page,jobs/mine/page}.tsx`
- `frontend/app/(protected)/admin/{worker-applications/page,job-reports/page}.tsx`
- `frontend/lib/api/worker.ts` (typed client wrapping `/api/worker-applications/*` and `/api/worker-jobs/*`)
- `frontend/components/PhotoPair.tsx` (small primitive rendering front / back images side by side for the admin review surface; reused on `jobs/[id]/page.tsx` after approval)

**Modified paths**:
- `backend/app/Models/{CarbonListing,User}.php` — listing gets `needs_workers` cast + the saving-listener branch that creates `WorkerJob` on `sold`; user gets `isWorker()` helper
- `backend/app/Http/Controllers/Auth/AuthController.php` — `me()` returns extended payload with `isWorker`
- `backend/app/Http/Requests/CarbonListings/CreateRequest.php` — accept `needs_workers` boolean
- `backend/app/Http/Resources/CarbonListingResource.php` — expose `needs_workers`
- `backend/routes/api.php` — new resource + admin routes for worker flows
- `frontend/lib/types/user.ts` — User gains optional `isWorker` flag
- `frontend/lib/session/server.ts` — propagates extended shape
- `frontend/app/(protected)/seller/listings/new/page.tsx` — adds `needs_workers` checkbox
- `frontend/app/(protected)/me/page.tsx` — quick-link section gains worker + admin entries
- `frontend/components/AppHeader.tsx` — role-conditional nav entries
- `frontend/DESIGN_SYSTEM.md` — record the new CTA labels (`認領` / `提交回報` / `核准回報` / `退件回報`) and the `<PhotoPair>` primitive

**Dependencies / 套件**: no new PHP or npm dependencies. File upload uses Laravel's built-in `Storage::disk('public')->putFile()` and `php artisan storage:link` to expose `storage/app/public/` at `/storage/`. Frontend multipart uses the same axios client with `Content-Type: multipart/form-data` set per request.

**Risks**:

- **Claim race condition** — two workers POST `/api/worker-jobs/{id}/claim` simultaneously, both pass the `status === 'open'` check, both succeed. Mitigation: wrap the claim in `DB::transaction()` + `lockForUpdate()` on the `WorkerJob` row; second claimer gets the post-lock status check and returns 409 Conflict. Same pattern as phase-2's purchase race
- **Listing → sold → job auto-creation atomicity** — if `CarbonListing.transitionTo('sold')` succeeds but the side-effect `WorkerJob::create()` fails, the listing is sold but no job exists. Mitigation: the saving listener runs inside the parent `PurchaseController` transaction; any failure rolls the whole purchase back. Pest test asserts this by forcing a `WorkerJob` insert failure mid-purchase and asserting the listing stays `approved`
- **Image upload abuse** — multipart endpoint accepts files; without validation a malicious user could upload PHP webshells or 10 GB videos. Mitigation: `FormRequest` rule `image|mimes:jpg,jpeg,png|max:5120` (5 MB cap per file, two files per report = 10 MB max). The validator runs BEFORE the controller body so unauthorized writes never touch disk
- **Public storage path leaking PII** — `/storage/job-reports/<random>.jpg` is publicly readable once `storage:link` runs. Anyone with the URL can view the photo. Mitigation v1: filenames use `Str::random(40)` so URLs are non-guessable; admin policy still gates listing the filenames. Hardening (signed URLs, private disk with signed-route streaming) is deferred to the S3 phase
- **`isWorker` performance** — naive `User::isWorker()` runs `WorkerApplication::where('user_id', $id)->where('status', 'approved')->exists()` on every request hitting `me()`. Mitigation: compute the four role flags once in `me()` only; controllers that already have `Auth::user()` use the flag from the request instead of re-querying. If hot-path emerges, add `Auth::user()->loadCount(...)` similarly to phase-2's mitigation
- **State machine drift on report rejection** — `WorkerJobReport` rejection has a side effect (`WorkerJob` returns to `claimed`). If a future controller short-circuits the rejection flow, the job could be stranded in `reported` with a rejected report. Mitigation: `WorkerJobReport` boot listener registers a `saved` callback that, when `status` transitions to `rejected`, asserts the parent `WorkerJob` is back to `claimed`. Pest test covers this

**Out of scope reminder**: phase-3 ships the **DB-only worker / report flow with local-disk image upload**. The "approval" of a work report is recorded in `worker_job_reports.status='approved'` and **does NOT settle anything on-chain**. Phase-4 will graft the contract call onto this approval (and onto the existing `POST /api/carbon-listings/{id}/purchase`). Marketing the platform as "blockchain-verified maintenance" before phase-4 lands would be misleading; landing copy stays the same until phase-4 archive.
