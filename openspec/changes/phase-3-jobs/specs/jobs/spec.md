## ADDED Requirements

### Requirement: Worker Application Resource

The backend SHALL provide a `worker_applications` table and an Eloquent `WorkerApplication` model that records a user's request to be promoted to the worker behavioral role. A user can have at most one application row.

The model MUST include at minimum the following columns: `id`, `user_id` (FK → users, on delete cascade, **UNIQUE**), `reason` (text), `has_experience` (tinyint 1), `age` (unsigned tinyint), `residence` (string), `contact` (string), `status` (string enum), `reviewer_id` (FK → users.id, nullable), `review_reason` (text nullable), `reviewed_at` (timestamp nullable), `created_at`, `updated_at`.

The `status` column MUST take one of exactly three string values: `pending`, `approved`, `rejected`. New applications are created at `pending`. The values `approved` and `rejected` are terminal — v1 does NOT support re-application after rejection (admin must hard-delete the row to reset).

#### Scenario: Migration creates worker_applications with required columns
- **WHEN** a developer runs `php artisan migrate`
- **THEN** a `worker_applications` table exists in MySQL with the columns and types listed above, a UNIQUE index on `user_id`, `status` defaulting to `'pending'`, and NOT NULL on `user_id` / `reason` / `has_experience` / `age` / `residence` / `contact`

#### Scenario: A user cannot submit two applications
- **GIVEN** a user with an existing `worker_applications` row (regardless of status)
- **WHEN** the same user POSTs `/api/worker-applications` again
- **THEN** the response is 409 Conflict with a message indicating an application already exists; no second row is written and the UNIQUE constraint is the enforcement layer

#### Scenario: A new application defaults to pending
- **WHEN** `WorkerApplication::create(['user_id' => $user->id, 'reason' => '...', ...])` runs without an explicit `status` field
- **THEN** the persisted row has `status = 'pending'`, `reviewer_id = null`, `reviewed_at = null`, `review_reason = null`

### Requirement: Worker Application State Machine

The `WorkerApplication` model SHALL expose a `transitionTo(string $newStatus, array $extras = []): void` method modeled on phase-2's `CarbonListing::transitionTo()`. Controllers MUST call this method instead of assigning to `$application->status` directly.

Allowed transitions:

| From | To |
|---|---|
| `pending` | `approved`, `rejected` |
| `approved` | (terminal) |
| `rejected` | (terminal) |

`transitionTo('approved')` MUST stamp `reviewer_id` (the calling admin's id) and `reviewed_at` (now). `transitionTo('rejected', ['review_reason' => $reason])` MUST do the same plus store the optional reason. A model-level `saving` boot listener SHALL also assert validity when `status` is dirty.

#### Scenario: Allowed transition succeeds
- **GIVEN** a `WorkerApplication` with `status = 'pending'`
- **WHEN** `$application->transitionTo('approved')` is called inside an admin controller
- **THEN** the in-memory `status` is `'approved'`, `reviewer_id` and `reviewed_at` are set, and `$application->save()` persists without error

#### Scenario: Disallowed transition throws InvalidStateTransition
- **GIVEN** a `WorkerApplication` with `status = 'approved'`
- **WHEN** any code calls `$application->transitionTo('pending')` or `$application->transitionTo('rejected')`
- **THEN** an `InvalidStateTransition` exception is thrown, no DB write occurs, and the row's status remains `'approved'`

### Requirement: Worker Job Resource

The backend SHALL provide a `worker_jobs` table and an Eloquent `WorkerJob` model that represents one piece of land-maintenance work bound to exactly one sold-and-flagged `CarbonListing`.

The model MUST include at minimum: `id`, `carbon_listing_id` (FK → carbon_listings, on delete cascade, **UNIQUE**), `worker_id` (FK → users.id, nullable), `status` (string enum), `claimed_at` (timestamp nullable), `created_at`, `updated_at`.

The `status` column MUST take one of exactly five string values: `open`, `claimed`, `reported`, `approved`, `rejected`. New jobs are created at `open`. `approved` is terminal. `rejected` is NOT terminal — a rejected report flips the parent job back to `claimed` so the same worker can resubmit (see the `WorkerJobReport` requirement).

The `UNIQUE(carbon_listing_id)` constraint enforces "one job per listing, ever" and is the structural reason a buyer cannot be charged twice for the same land or have two parallel maintenance crews assigned.

#### Scenario: Migration creates worker_jobs with required columns
- **WHEN** a developer runs `php artisan migrate`
- **THEN** a `worker_jobs` table exists with the columns above, a UNIQUE index on `carbon_listing_id`, `status` defaulting to `'open'`, `worker_id` nullable with FK → users.id, and NOT NULL on `carbon_listing_id` / `status`

#### Scenario: A new job defaults to open with no worker
- **WHEN** `WorkerJob::create(['carbon_listing_id' => $listing->id])` runs without explicit `status` or `worker_id`
- **THEN** the persisted row has `status = 'open'`, `worker_id = null`, `claimed_at = null`

### Requirement: Worker Job Auto-Creation on Sold Transition

The `CarbonListing` model SHALL register a `saving` boot listener that, when the listing transitions to `status = 'sold'` AND `needs_workers = true`, atomically creates the corresponding `WorkerJob(carbon_listing_id, status=open)` inside the same database transaction.

The auto-creation MUST be idempotent in the sense that if a `WorkerJob` already exists for the listing (caught by the UNIQUE constraint), the side-effect listener catches the `QueryException` and bubbles up only if the underlying purchase transaction had reasons to fail. Under normal flow, the listing's sold transition and the worker_job insert are a single atomic unit — either both commit or both roll back.

#### Scenario: Sold transition with needs_workers=true creates the job
- **GIVEN** an approved listing with `needs_workers = true` and a buyer about to POST `/api/carbon-listings/{id}/purchase`
- **WHEN** the purchase controller's `DB::transaction()` runs
- **THEN** the listing transitions to `sold`, a `carbon_purchases` row is inserted (phase-2 behavior), AND a `worker_jobs(carbon_listing_id, status='open', worker_id=null)` row is inserted — all three writes commit together

#### Scenario: Sold transition with needs_workers=false skips job creation
- **GIVEN** an approved listing with `needs_workers = false`
- **WHEN** a buyer purchases it
- **THEN** the listing transitions to `sold`, a `carbon_purchases` row is inserted, and NO `worker_jobs` row is created

#### Scenario: Worker job insert failure rolls back the purchase
- **GIVEN** an approved listing with `needs_workers = true` and a corrupt state where a stale `worker_jobs` row already exists for this `carbon_listing_id`
- **WHEN** a buyer purchases it and the listener attempts the auto-insert
- **THEN** the insert fails on the UNIQUE constraint, the `QueryException` bubbles up, the parent transaction rolls back, the listing stays `approved`, and no purchase row is created. The API returns 409 Conflict

### Requirement: Worker Job Claim Race Defense

The backend SHALL expose `POST /api/worker-jobs/{id}/claim` and MUST defend against simultaneous-claim race conditions using `DB::transaction()` + `lockForUpdate()` on the `worker_jobs` row, modeled on phase-2's purchase race defense.

The transaction body MUST: re-read the locked row, assert `status === 'open'` (return 409 Conflict if not), assert the calling user `isWorker()` (return 403 if not), then update `status = 'claimed'`, `worker_id = Auth::id()`, `claimed_at = now()`.

#### Scenario: First claimer wins
- **GIVEN** a `worker_jobs` row with `status = 'open'`
- **WHEN** an approved worker POSTs `/api/worker-jobs/{id}/claim`
- **THEN** the transaction commits with `status = 'claimed'`, `worker_id = the caller`, `claimed_at = now()`; response is 200 with the updated job resource

#### Scenario: Concurrent claim loses gracefully
- **GIVEN** two approved workers POST `/api/worker-jobs/{id}/claim` for the same open job within milliseconds
- **WHEN** both requests reach the controller
- **THEN** the first transaction commits as above. The second transaction's post-lock re-read finds `status = 'claimed'` and returns 409 Conflict with a message indicating the job is no longer available; no second row mutation occurs

#### Scenario: Non-worker claim is rejected
- **GIVEN** an authenticated user who has no approved `WorkerApplication`
- **WHEN** they POST `/api/worker-jobs/{id}/claim` for any open job
- **THEN** the response is 403 Forbidden via `WorkerJobPolicy::claim()`, regardless of the job's status

### Requirement: Worker Job Report Resource

The backend SHALL provide a `worker_job_reports` table and an Eloquent `WorkerJobReport` model that holds exactly one work report per `WorkerJob`. The 1:1 relationship is enforced by a UNIQUE FK.

The model MUST include at minimum: `id`, `worker_job_id` (FK → worker_jobs, on delete cascade, **UNIQUE**), `worker_id` (FK → users.id), `datetime_start` (datetime), `datetime_end` (datetime), `before_image_path` (string), `after_image_path` (string), `content` (text), `status` (string enum), `reviewer_id` (FK → users.id, nullable), `review_reason` (text nullable), `reviewed_at` (timestamp nullable), `created_at`, `updated_at`.

The `status` column MUST take one of exactly three string values: `pending`, `approved`, `rejected`. New reports are created at `pending`. `approved` is terminal. When a report is rejected, the report row keeps `status = 'rejected'` for audit AND the parent `worker_jobs.status` is reset to `claimed` so the same worker can submit a new report.

`datetime_end` MUST be strictly greater than `datetime_start` (enforced at the `FormRequest` validation layer with `after:datetime_start`).

#### Scenario: Migration creates worker_job_reports with required columns
- **WHEN** a developer runs `php artisan migrate`
- **THEN** a `worker_job_reports` table exists with the columns above, a UNIQUE index on `worker_job_id`, `status` defaulting to `'pending'`, and NOT NULL on `worker_job_id` / `worker_id` / `datetime_start` / `datetime_end` / `before_image_path` / `after_image_path` / `content` / `status`

#### Scenario: A worker can only submit one report per job
- **GIVEN** a `worker_jobs` row with an existing `worker_job_reports` row
- **WHEN** the worker POSTs `/api/worker-jobs/{id}/report` again
- **THEN** the response is 409 Conflict; the UNIQUE constraint is the enforcement layer; no second report row is written

#### Scenario: Rejection flips parent job back to claimed
- **GIVEN** a `WorkerJobReport` with `status = 'pending'` whose parent `WorkerJob` has `status = 'reported'`
- **WHEN** an admin calls `POST /api/admin/job-reports/{id}/reject` with optional reason
- **THEN** inside a single `DB::transaction()`: the report transitions to `rejected`, `reviewer_id` and `reviewed_at` are set, `review_reason` is stored, AND the parent `worker_jobs.status` is set to `claimed`. The worker can then submit a new report (which would replace the old row — see next scenario)

#### Scenario: Resubmission after rejection replaces the row
- **GIVEN** a `worker_jobs` row with `status = 'claimed'` whose previous `worker_job_reports` row has `status = 'rejected'`
- **WHEN** the same worker POSTs a new report
- **THEN** the new submission MUST delete the rejected report row inside a `DB::transaction()` and then insert the new pending report (using a single physical row at the UNIQUE FK position). The audit trail is preserved via DB binlog / application-level audit log if configured; v1 does not retain the rejected payload after replacement

### Requirement: Image Upload Validation and Storage

The backend SHALL accept the report's `before_image` and `after_image` as `multipart/form-data` files and validate them with the Laravel `FormRequest` rule `image|mimes:jpg,jpeg,png|max:5120` (5 MB per file).

On successful validation, each file MUST be stored via `Storage::disk('public')->putFile('job-reports', $file)`. The generated path (relative to the disk root) MUST be persisted to `worker_job_reports.before_image_path` / `after_image_path`. `php artisan storage:link` MUST be part of the migration / setup so files are served at `/storage/job-reports/<filename>`.

Filenames MUST NOT include user-supplied input. The framework's `putFile()` default of a random hash is sufficient; no further obfuscation is applied in v1.

#### Scenario: Valid image upload succeeds
- **GIVEN** an approved worker with a claimed job
- **WHEN** they POST `/api/worker-jobs/{id}/report` with `before_image` and `after_image` both as valid JPEGs under 5 MB plus the required text fields
- **THEN** the response is 201 with the new report resource; two files exist under `storage/app/public/job-reports/` and the persisted paths point to them

#### Scenario: Oversized file rejected before any disk write
- **WHEN** the worker POSTs a `before_image` over 5 MB
- **THEN** the `FormRequest` validation returns 422 with field-level error on `before_image`; no file is written to disk; no `worker_job_reports` row is created

#### Scenario: Non-image MIME rejected
- **WHEN** the worker POSTs a `before_image` that is actually a `.php` or `.exe` file (regardless of extension)
- **THEN** the `mimes:jpg,jpeg,png` rule rejects it with 422; no disk write occurs

### Requirement: Worker Authorization Policies

The backend SHALL implement `WorkerApplicationPolicy`, `WorkerJobPolicy`, and `WorkerJobReportPolicy`, and call `authorize()` on every protected controller method. Inline `if ($user->role === 'admin')` checks in controller bodies are NOT permitted.

Policy methods and their rules:

| Policy::method | Allowed iff |
|---|---|
| `WorkerApplicationPolicy::create($user)` | the user has no existing `WorkerApplication` row |
| `WorkerApplicationPolicy::view($user, $application)` | `$user->id === $application->user_id` OR `$user->isAdmin()` |
| `WorkerApplicationPolicy::approve($user, $application)` | `$user->isAdmin()` AND `$application->status === 'pending'` |
| `WorkerApplicationPolicy::reject($user, $application)` | `$user->isAdmin()` AND `$application->status === 'pending'` |
| `WorkerJobPolicy::viewOpenQueue($user)` | `$user->isWorker()` |
| `WorkerJobPolicy::view($user, $job)` | `$user->isAdmin()` OR `$user->id === $job->worker_id` OR (`$user->isWorker()` AND `$job->status === 'open'`) |
| `WorkerJobPolicy::claim($user, $job)` | `$user->isWorker()` AND `$job->status === 'open'` |
| `WorkerJobPolicy::submitReport($user, $job)` | `$user->id === $job->worker_id` AND `$job->status === 'claimed'` |
| `WorkerJobReportPolicy::approve($user, $report)` | `$user->isAdmin()` AND `$report->status === 'pending'` |
| `WorkerJobReportPolicy::reject($user, $report)` | `$user->isAdmin()` AND `$report->status === 'pending'` |

#### Scenario: Non-admin cannot approve a worker application
- **GIVEN** an authenticated non-admin user
- **WHEN** they POST `/api/admin/worker-applications/{id}/approve`
- **THEN** the response is 403, the application status is unchanged, and the failure is logged via the policy denial (not a generic 500)

#### Scenario: A worker can only submit a report for their own claimed job
- **GIVEN** a `WorkerJob` with `worker_id = userA` and `status = 'claimed'`
- **WHEN** `userB` (also an approved worker) POSTs `/api/worker-jobs/{id}/report`
- **THEN** the response is 403 via `WorkerJobPolicy::submitReport()`; no `worker_job_reports` row is created

### Requirement: Worker Endpoints

The backend SHALL expose the following endpoints under `auth:sanctum`:

| Method | Path | Purpose | Policy gate |
|---|---|---|---|
| POST | `/api/worker-applications` | submit application | `WorkerApplicationPolicy::create` |
| GET | `/api/worker-applications/mine` | view my application (or 404) | none beyond auth |
| GET | `/api/worker-jobs/open` | list open jobs (paginated) | `WorkerJobPolicy::viewOpenQueue` |
| GET | `/api/worker-jobs/mine` | list jobs I have claimed / reported / had reviewed (paginated, grouped by status) | none beyond auth |
| GET | `/api/worker-jobs/{id}` | detail with embedded `WorkerJob` + parent `CarbonListing` summary + my latest `WorkerJobReport` if any | `WorkerJobPolicy::view` |
| POST | `/api/worker-jobs/{id}/claim` | claim an open job | `WorkerJobPolicy::claim` |
| POST | `/api/worker-jobs/{id}/report` | submit multipart report | `WorkerJobPolicy::submitReport` |

`GET /api/worker-jobs/open` MUST filter to `status = 'open'` and MUST NOT expose jobs whose parent listing is owned by the calling user (a seller should not work on their own land). Pagination uses Laravel's standard 15-per-page default unless `?per_page=` overrides it within `[1, 50]`.

`GET /api/worker-jobs/mine` MUST union jobs where `worker_id = Auth::id()` regardless of status, sorted by `updated_at DESC`.

#### Scenario: Open queue excludes seller's own land
- **GIVEN** an approved worker who is also the owner of an approved+sold listing whose `WorkerJob` is open
- **WHEN** they GET `/api/worker-jobs/open`
- **THEN** the response does not include that job; only jobs whose parent `CarbonListing.user_id !== Auth::id()` are returned

#### Scenario: Mine endpoint groups by status
- **GIVEN** an approved worker with three jobs: one `claimed`, one `reported`, one `approved`
- **WHEN** they GET `/api/worker-jobs/mine?per_page=50`
- **THEN** the response returns all three jobs (sorted by `updated_at DESC`) and the frontend renders them grouped under their respective status sections

### Requirement: Admin Worker Review Endpoints

The backend SHALL expose the following admin endpoints under `auth:sanctum` and gated by `WorkerApplicationPolicy` / `WorkerJobReportPolicy`:

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/admin/worker-applications/pending` | paginated queue of pending applications |
| POST | `/api/admin/worker-applications/{id}/approve` | transition to approved |
| POST | `/api/admin/worker-applications/{id}/reject` | transition to rejected with optional reason |
| GET | `/api/admin/job-reports/pending` | paginated queue of pending reports (includes signed image URLs) |
| POST | `/api/admin/job-reports/{id}/approve` | transition to approved |
| POST | `/api/admin/job-reports/{id}/reject` | transition to rejected with optional reason (also flips parent `WorkerJob` to `claimed`) |

All four mutating endpoints MUST wrap their state machine call in a `DB::transaction()` and MUST stamp `reviewer_id = Auth::id()` and `reviewed_at = now()` as part of the same write.

#### Scenario: Admin approves a worker application
- **GIVEN** an admin user and a `WorkerApplication` with `status = 'pending'`
- **WHEN** they POST `/api/admin/worker-applications/{id}/approve`
- **THEN** within a single transaction: the application transitions to `approved`, `reviewer_id` is the admin's id, `reviewed_at` is now. Response is 200 with the updated resource. The applicant's `isWorker()` flag flips to true on their next `GET /api/me`

#### Scenario: Admin rejects a report and the job becomes claimable-by-same-worker again
- **GIVEN** an admin user, a `WorkerJobReport` with `status = 'pending'`, and its parent `WorkerJob` with `status = 'reported'` and `worker_id = $w`
- **WHEN** the admin POSTs `/api/admin/job-reports/{id}/reject` with `reason = "前後照片明顯不是同一塊地"`
- **THEN** within a single transaction: the report transitions to `rejected` with the reason stored, AND `worker_jobs.status` flips to `claimed` (NOT `open`), AND `worker_jobs.worker_id` remains `$w` so the same worker can resubmit

### Requirement: Frontend Role-Aware Navigation (Worker Extensions)

The frontend `AppHeader` SHALL show role-conditional links derived from the session's `isAdmin` / `isSeller` / `hasPurchased` / `isWorker` flags. The worker extensions on top of the phase-2 nav are:

- If `!isWorker` AND the user has no pending `WorkerApplication`: show `工人申請` linking to `/worker/apply`
- If the user has a pending or rejected `WorkerApplication` (but not approved): show `申請狀態` linking to `/worker/apply/status`
- If `isWorker`: show `工作機會` linking to `/worker/jobs` and `我的工作` linking to `/worker/jobs/mine`
- If `isAdmin`: in addition to phase-2's `後台審核`, show `工人申請審核` linking to `/admin/worker-applications` and `工作回報審核` linking to `/admin/job-reports`

The "has pending application" check MUST NOT require a separate API call on every render. The `GET /api/me` response is NOT extended for this; instead the `AppHeader` reads a lightweight `applicationStatus` field that the `(protected)/layout.tsx` server component fetches once per navigation via `GET /api/worker-applications/mine`, passing it down to `<AppHeader applicationStatus={...}>`.

#### Scenario: Approved worker sees worker nav after first session refresh
- **GIVEN** a user whose `WorkerApplication` was just approved by an admin in a different browser
- **WHEN** the user reloads any protected page
- **THEN** their `/api/me` returns `isWorker: true`, `AppHeader` shows `工作機會` and `我的工作`, and the previously visible `工人申請` link is gone

#### Scenario: Pending applicant sees status link, not apply link
- **GIVEN** a user with a `WorkerApplication.status = 'pending'`
- **WHEN** they load any protected page
- **THEN** `AppHeader` shows `申請狀態` (linking to `/worker/apply/status`) and does NOT show `工人申請`

### Requirement: Frontend Worker and Admin Surfaces

The frontend SHALL implement the following route surfaces under `app/(protected)/`:

- `worker/apply/page.tsx`: client form with `<Field>` × 5 (reason / has_experience radio / age / residence / contact). Submit POSTs to `/api/worker-applications`. On success, `router.push('/worker/apply/status')`. On 409 (already applied), redirect to `/worker/apply/status` directly
- `worker/apply/status/page.tsx`: server component fetching `/api/worker-applications/mine`. Renders a `<StatusBadge>` (`審核中` / `已核准` / `已退件`) + the application content + admin's review reason if rejected
- `worker/jobs/page.tsx`: server component gated by `isWorker` (redirect to `/worker/apply` if not). Fetches `/api/worker-jobs/open` and renders the editorial card layout reused from `/market`. Empty state with CTA explaining that new jobs appear after sellers' lands sell and report flagged `needs_workers`
- `worker/jobs/[id]/page.tsx`: server component fetching `/api/worker-jobs/{id}`. Detail card. If `status === 'open'`: show `認領` (primary button) opening a confirm dialog → `POST /api/worker-jobs/{id}/claim` → `router.refresh()`. If `status === 'claimed'` AND `worker_id === currentUserId`: show `提交回報` linking to `[id]/report`. If `status === 'reported'`: show a `<PhotoPair>` and a status note. If `approved` / `rejected`: show the final state with admin review reason
- `worker/jobs/[id]/report/page.tsx`: `'use client'` form with two `datetime-local` inputs + two `<input type="file" accept="image/*">` + a `<textarea>`. On submit, POSTs `multipart/form-data` to `/api/worker-jobs/{id}/report`. Success → `router.push('/worker/jobs/[id]')`. Validation errors render per-field via the design system's error block
- `worker/jobs/mine/page.tsx`: server component fetching `/api/worker-jobs/mine`. Renders three sections (`進行中` / `審核中` / `已完成`) with the corresponding status filter. Empty state per section
- `admin/worker-applications/page.tsx`: admin-gated (layout from phase-2 handles `redirect('/me')`). Fetches `/api/admin/worker-applications/pending`. Each row: applicant info + 核准 primary button + 退件 ghost button (with inline reason form)
- `admin/job-reports/page.tsx`: admin-gated. Fetches `/api/admin/job-reports/pending`. Each row: job summary + `<PhotoPair before={...} after={...}>` + 核准 / 退件 buttons

#### Scenario: Worker submits report with valid images
- **GIVEN** an approved worker with a claimed job
- **WHEN** they navigate to `/worker/jobs/[id]/report`, fill `datetime_start` / `datetime_end` / `content`, pick two valid JPEG files, and click `提交回報`
- **THEN** the client posts multipart with the two files, the API returns 201, the user is redirected to `/worker/jobs/[id]`, and the detail page now shows the submitted report under `審核中` with the photo pair embedded

#### Scenario: Non-worker hitting /worker/jobs is redirected
- **GIVEN** an authenticated user with no approved `WorkerApplication`
- **WHEN** they navigate directly to `/worker/jobs`
- **THEN** the server component reads `isWorker = false` from the session and `redirect('/worker/apply')` runs server-side before any UI render; no flash of job-queue content occurs

#### Scenario: Admin reviews and approves a report
- **GIVEN** an admin and a pending `WorkerJobReport`
- **WHEN** they click 核准 on `/admin/job-reports`
- **THEN** the client POSTs `/api/admin/job-reports/{id}/approve`, the row disappears from the pending list (router.refresh), the worker's `/worker/jobs/[id]` detail now shows status `已核准` with the admin's reviewer info, and the worker's `/worker/jobs/mine` moves the job from `審核中` to `已完成`
