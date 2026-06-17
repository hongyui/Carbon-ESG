## 1. Database Schema

- [x] 1.1 Create migration `add_needs_workers_to_carbon_listings_table.php`: `$table->boolean('needs_workers')->default(false)->after('status');`
- [x] 1.2 Create migration `create_worker_applications_table.php` with columns per spec: `id`, `user_id` FK users.id cascade **UNIQUE**, `reason` text, `has_experience` boolean, `age` unsignedTinyInteger, `residence` string(255), `contact` string(255), `status` string(20) default `'pending'`, `reviewer_id` FK users.id nullable nullOnDelete, `review_reason` text nullable, `reviewed_at` timestamp nullable, `timestamps()`
- [x] 1.3 Create migration `create_worker_jobs_table.php` with `id`, `carbon_listing_id` FK carbon_listings.id cascade **UNIQUE**, `worker_id` FK users.id nullable nullOnDelete, `status` string(20) default `'open'`, `claimed_at` timestamp nullable, `timestamps()`
- [x] 1.4 Create migration `create_worker_job_reports_table.php` with `id`, `worker_job_id` FK worker_jobs.id cascade **UNIQUE**, `worker_id` FK users.id cascade, `datetime_start` datetime, `datetime_end` datetime, `before_image_path` string(255), `after_image_path` string(255), `content` text, `status` string(20) default `'pending'`, `reviewer_id` FK users.id nullable nullOnDelete, `review_reason` text nullable, `reviewed_at` timestamp nullable, `timestamps()`
- [x] 1.5 Run `cd backend && php artisan migrate` against docker mysql; verify all four tables / column present via `php artisan tinker --execute="echo Schema::hasTable('worker_applications'); echo Schema::hasTable('worker_jobs'); echo Schema::hasTable('worker_job_reports'); echo Schema::hasColumn('carbon_listings', 'needs_workers');"`
- [x] 1.6 Run `php artisan storage:link` once; add `backend/storage/app/public/job-reports/.gitignore` containing `*\n!.gitignore` so the dir exists in git but uploaded files don't
- [x] 1.7 Commit as `feat(backend): add jobs migrations + needs_workers column + storage link`

## 2. Models, State Machines, Role Inference, Factories

- [x] 2.1 Create `app/Models/WorkerApplication.php` with `$fillable`, `$casts` (`has_experience` → boolean; `reviewed_at` → datetime), `STATUS_PENDING` / `STATUS_APPROVED` / `STATUS_REJECTED` constants, `belongsTo(User)`, `belongsTo(User, 'reviewer_id')` relations
- [x] 2.2 Implement `WorkerApplication::transitionTo(string $newStatus, array $extras = []): void` with the allowed-transition map from the spec; on approve/reject stamp `reviewer_id = Auth::id()` and `reviewed_at = now()`; on reject store `$extras['review_reason']`
- [x] 2.3 Register `WorkerApplication::saving()` listener mirroring phase-2's `CarbonListing` pattern: when `status` is dirty, assert allowed; throw `InvalidStateTransition` (reuse phase-2's exception) otherwise
- [x] 2.4 Create `app/Models/WorkerJob.php` with `$fillable`, `$casts` (`claimed_at` → datetime), `STATUS_OPEN` / `STATUS_CLAIMED` / `STATUS_REPORTED` / `STATUS_APPROVED` constants, `belongsTo(CarbonListing)`, `belongsTo(User, 'worker_id')`, `hasOne(WorkerJobReport)` relations (note: `rejected` status was dropped during apply — job rejection is always a property of the report, the job bounces `reported → claimed`; spec updated to match)
- [x] 2.5 Implement `WorkerJob::transitionTo()` with the allowed map: `open → claimed`, `claimed → reported`, `reported → approved | claimed`. On first-time `claimed` (from `open`) stamp `claimed_at = now()` and set `worker_id = $extras['worker_id'] ?? Auth::id()`; the rejection-bounce path (`reported → claimed`) preserves worker_id and claimed_at
- [x] 2.6 Register `WorkerJob::saving()` listener with same pattern as 2.3
- [x] 2.7 Create `app/Models/WorkerJobReport.php` with `$fillable`, `$casts` (`datetime_start` / `datetime_end` / `reviewed_at` → datetime), `STATUS_PENDING` / `STATUS_APPROVED` / `STATUS_REJECTED` constants, `belongsTo(WorkerJob)`, `belongsTo(User, 'worker_id')`, `belongsTo(User, 'reviewer_id')`
- [x] 2.8 Implement `WorkerJobReport::transitionTo()` with allowed map: `pending → approved | rejected`. On approve/reject stamp `reviewer_id` and `reviewed_at`; on reject store `$extras['review_reason']`
- [x] 2.9 Register `WorkerJobReport::saved()` listener: when status transitions to `rejected`, call `$report->workerJob->transitionTo('claimed')` and `->save()` inside the parent transaction
- [x] 2.10 Add `User::workerApplication()` (`hasOne`), `User::workerJobs()` (`hasMany` on `worker_id`)
- [x] 2.11 Add `User::isWorker(): bool` returning `$this->workerApplication()->where('status', 'approved')->exists()`
- [x] 2.12 Add factories: `WorkerApplicationFactory`, `WorkerJobFactory`, `WorkerJobReportFactory` with sane defaults and `pending()` / `approved()` / `rejected()` / `claimed()` / `reported()` states
- [x] 2.13 New `tests/Feature/Worker/StateMachineTest.php`: walk every allowed transition with `transitionTo()` on each of the three models; assert each disallowed transition throws `InvalidStateTransition`; assert direct status assignment is also caught by the saving listener
- [x] 2.14 New `tests/Feature/Worker/IsWorkerHelperTest.php`: fixtures for (no application, pending, approved, rejected) × assert each gives correct `isWorker` boolean; assert query is an `EXISTS` on the indexed `user_id` column (via `DB::enableQueryLog`)
- [x] 2.15 Run `./vendor/bin/pest tests/Feature/Worker` — all pass
- [x] 2.16 Commit as `feat(backend): add WorkerApplication/WorkerJob/WorkerJobReport models + state machines + isWorker helper`

## 3. Carbon Listing MODIFY: needs_workers + auto-create WorkerJob on sold

- [x] 3.1 Update `app/Models/CarbonListing.php`: add `needs_workers` to `$fillable`, add `'needs_workers' => 'boolean'` to `$casts`, add `'needs_workers' => false` to `$attributes` so the in-memory default matches the migration
- [x] 3.2 Add `CarbonListing::saved()` listener (chosen over `saving` so the parent insert commits first): when `status` is dirty and now `'sold'` AND `needs_workers === true`, call `WorkerJob::create(['carbon_listing_id' => $this->id, 'status' => 'open'])` inside the same transaction
- [x] 3.3 Update `app/Http/Requests/CarbonListings/CreateRequest.php`: add rule `'needs_workers' => ['sometimes', 'boolean']` (optional; defaults to false via the migration + model)
- [x] 3.4 ~~Update `app/Http/Resources/CarbonListingResource.php`~~ — no Resource class exists in phase-2; `$listing` serializes directly via Eloquent toArray and now exposes `needs_workers` via the new cast
- [x] 3.5 Update phase-2's `CreateTest.php` to assert `needs_workers=false` is the default; add a happy-path test case posting `needs_workers=true`
- [x] 3.6 New `tests/Feature/CarbonListings/SoldTransitionSideEffectTest.php`: scenario A — listing with `needs_workers=true` is purchased → assert `worker_jobs` row exists with `status=open`, `carbon_listing_id` matches, `worker_id` null. Scenario B — listing with `needs_workers=false` is purchased → assert no `worker_jobs` row exists. Scenario C — force a UNIQUE collision by pre-inserting a `worker_jobs(carbon_listing_id)` row, then attempt purchase → assert the QueryException rolls back the listing transition (listing stays `approved`) and the purchase row is not created
- [x] 3.7 Run `./vendor/bin/pest tests/Feature/CarbonListings tests/Feature/Worker` — all pass
- [x] 3.8 Commit as `feat(backend): add needs_workers to listings + sold→WorkerJob auto-create with rollback safety`

## 4. /api/me MODIFY: add isWorker

- [x] 4.1 Update `AuthController::me()` response: spread `isWorker` into the user payload alongside the existing three flags
- [x] 4.2 Update phase-2's `tests/Feature/Auth/MeTest.php`: extend the assertion to include the `isWorker` field; add a scenario where an approved `WorkerApplication` exists → assert `isWorker === true`
- [x] 4.3 Run `./vendor/bin/pest tests/Feature/Auth/MeTest.php` — passes
- [x] 4.4 Commit as `feat(backend): /api/me returns isWorker flag`

## 5. Worker Application Endpoints + Admin Review + Pest

- [x] 5.1 Create `app/Http/Requests/Worker/CreateApplicationRequest.php` with rules: `reason required|string|max:2000`, `has_experience required|boolean`, `age required|integer|min:18|max:99`, `residence required|string|max:255`, `contact required|string|max:255`
- [x] 5.2 Create `app/Http/Requests/Worker/RejectRequest.php` with rule `reason nullable|string|max:1000` (shared between application + report rejection — name it `Worker/RejectRequest.php`)
- [x] 5.3 Create `app/Policies/WorkerApplicationPolicy.php` with `create`, `view`, `approve`, `reject` methods per spec
- [x] 5.4 Create `app/Http/Controllers/WorkerApplicationController.php` with `store(CreateApplicationRequest)`, `mine(Request)` methods. `store` enforces UNIQUE by catching `QueryException(23000)` and returning 409 (and `Gate::authorize('create', ...)` blocks ahead with 403 in normal flow). `mine` returns the row or 404
- [x] 5.5 Create `app/Http/Controllers/Admin/WorkerApplicationReviewController.php` with `pending(Request)`, `approve(WorkerApplication)`, `reject(RejectRequest, WorkerApplication)`
- [x] 5.6 Register `WorkerApplicationPolicy` in `AppServiceProvider::boot()`
- [x] 5.7 Register routes in `routes/api.php` inside the existing `auth:sanctum` group: `POST /worker-applications`, `GET /worker-applications/mine`, `GET /admin/worker-applications/pending`, `POST /admin/worker-applications/{workerApplication}/approve`, `POST /admin/worker-applications/{workerApplication}/reject`
- [x] 5.8 New `tests/Feature/Worker/ApplyTest.php`: 201 + persisted row on happy path; 422 on missing `reason`; 422 on `age=17`; 403 on second apply (policy denial fires before UNIQUE collision)
- [x] 5.9 New `tests/Feature/Worker/MineApplicationTest.php`: 404 when no row; 200 with current status when row exists
- [x] 5.10 New `tests/Feature/Admin/WorkerApplicationReviewTest.php`: admin approve transitions to `approved` and stamps `reviewer_id`; admin reject with reason stores it; non-admin gets 403; double-approve gets 403 (policy denies non-pending status)
- [x] 5.11 Run `./vendor/bin/pest tests/Feature/Worker tests/Feature/Admin/WorkerApplicationReviewTest.php` — all pass
- [x] 5.12 Commit as `feat(backend): add worker application endpoints + admin review with Pest coverage`

## 6. Worker Job Endpoints + Report Submission + Admin Report Review + Pest

- [x] 6.1 Create `app/Policies/WorkerJobPolicy.php` with `viewOpenQueue`, `view`, `claim`, `submitReport` methods per spec
- [x] 6.2 Create `app/Policies/WorkerJobReportPolicy.php` with `approve`, `reject` methods per spec
- [x] 6.3 Register both policies in `AppServiceProvider::boot()`
- [x] 6.4 Create `app/Http/Requests/Worker/SubmitReportRequest.php` with rules: `datetime_start required|date`, `datetime_end required|date|after:datetime_start`, `content required|string|max:2000`, `before_image required|image|mimes:jpg,jpeg,png|max:5120`, `after_image required|image|mimes:jpg,jpeg,png|max:5120`
- [x] 6.5 Create `app/Http/Controllers/WorkerJobController.php` with `openQueue(Request)`, `mine(Request)`, `show(WorkerJob)`, `claim(WorkerJob)`, `submitReport(SubmitReportRequest, WorkerJob)` methods
- [x] 6.6 In `openQueue`: `WorkerJob::where('status', 'open')->whereHas('carbonListing', fn($q) => $q->where('user_id', '!=', $request->user()->id))->with('carbonListing')->oldest()->paginate(12)` and `$this->authorize('viewOpenQueue', WorkerJob::class)` at the top
- [x] 6.7 In `mine`: `WorkerJob::where('worker_id', $request->user()->id)->with(['carbonListing', 'report'])->latest('updated_at')->paginate(50)`
- [x] 6.8 In `claim`: wrap in `DB::transaction()` with `WorkerJob::lockForUpdate()->find()`; re-check `status === 'open'` (return 409 via `abort()` if not); call `$job->transitionTo('claimed', ['worker_id' => Auth::id()])` and save
- [x] 6.9 In `submitReport`: `Gate::authorize('submitReport', $job)`; inside `DB::transaction()`, if a `rejected` report exists for this job delete it first; store both images via `->store('job-reports', 'public')`; create new `WorkerJobReport(status=pending, ...)`; transition `$job` to `reported` and save
- [x] 6.10 Create `app/Http/Controllers/Admin/WorkerJobReportReviewController.php` with `pending(Request)`, `approve(WorkerJobReport)`, `reject(RejectRequest, WorkerJobReport)` methods. `approve` transitions report to approved AND parent job to approved inside `DB::transaction()`. `reject` transitions report to rejected (the model's `saved` listener flips parent job to claimed)
- [x] 6.11 Register routes inside the existing `auth:sanctum` group: `GET /worker-jobs/open`, `GET /worker-jobs/mine`, `GET /worker-jobs/{workerJob}`, `POST /worker-jobs/{workerJob}/claim`, `POST /worker-jobs/{workerJob}/report`, `GET /admin/job-reports/pending`, `POST /admin/job-reports/{workerJobReport}/approve`, `POST /admin/job-reports/{workerJobReport}/reject`
- [x] 6.12 New `tests/Feature/Worker/OpenQueueTest.php`: returns open jobs excluding caller's own land; non-worker gets 403
- [x] 6.13 New `tests/Feature/Worker/ClaimTest.php`: worker claims open → 200, status=claimed, worker_id set; worker claims claimed → 403 (policy); non-worker claims → 403; anonymous → 401. (The in-transaction post-lock 409 path is structural armor for real two-process contention; documented in code comment rather than tested in single-process Pest)
- [x] 6.14 New `tests/Feature/Worker/SubmitReportTest.php`: happy path 201 with two valid JPEGs (assert files exist on disk); 422 on oversized file; 422 on non-image (post a fake `.php` file); 422 on datetime_end ≤ datetime_start; 403 if not the claiming worker; double-submit → 403 (policy denies because parent job already in 'reported' status)
- [x] 6.15 New `tests/Feature/Worker/ResubmitAfterRejectionTest.php`: setup — claim, report, admin reject → assert job back to `claimed`. Then worker submits new report → assert old rejected row deleted, new pending row exists, job back to `reported`
- [x] 6.16 New `tests/Feature/Admin/JobReportReviewTest.php`: admin approve → report+job both approved; admin reject with reason → report rejected, job back to claimed (the listener side effect); non-admin gets 403
- [x] 6.17 Run `./vendor/bin/pest tests/Feature/Worker tests/Feature/Admin` — all pass (full suite 133 passing)
- [x] 6.18 Commit as `feat(backend): add worker job claim/report + admin report review + race defense + multipart upload`

## 7. Frontend AppHeader Update + Worker Surfaces

- [x] 7.1 Update `frontend/lib/types/user.ts`: add optional `isWorker?: boolean` to the `User` type
- [x] 7.2 Update `frontend/lib/session/getSessionFromCookies.ts` — no change needed; `isWorker` flows through the existing User shape unchanged
- [x] 7.3 Create `frontend/lib/session/getApplicationStatus.ts`: server-side helper that GETs `/api/worker-applications/mine` with the request cookies; returns the full `WorkerApplication` row or `null`
- [x] 7.4 Update `frontend/app/(protected)/layout.tsx`: in addition to `getSessionFromCookies()`, also call `getApplicationStatusFromCookies()` and pass both to `<AppHeader>`
- [x] 7.5 Update `frontend/components/AppHeader.tsx`: accept new `applicationStatus` prop; render worker-conditional nav per spec (`工人申請` / `申請狀態` / `工作機會` / `我的工作` / admin's `工人申請審核` / `工作回報審核`)
- [x] 7.6 Update `frontend/DESIGN_SYSTEM.md`: add the new CTA labels (`認領` / `提交回報` / `核准回報` / `退件回報` / `送出申請`) and document the `<PhotoPair>` primitive
- [x] 7.7 Create `frontend/components/PhotoPair.tsx`: `{ before: string; after: string; className?: string }`; renders two side-by-side images with `前 · Before` / `後 · After` captions
- [x] 7.8 Create `frontend/lib/api/worker.ts`: typed client wrapping `createApplication`, `getMyApplication`, `getOpenJobs`, `getMyJobs`, `getJob`, `claimJob`, `submitReport(jobId, payload)` (uses FormData internally), plus admin endpoints
- [x] 7.9 Create `frontend/app/(protected)/worker/apply/page.tsx`: client form with reason / experience radio / age / residence / contact; on 403/409 redirect to `/worker/apply/status`; on success redirect to `/worker/apply/status`
- [x] 7.10 Create `frontend/app/(protected)/worker/apply/status/page.tsx`: server component fetching `/api/worker-applications/mine`; renders a `<WorkerStatusBadge kind="application">` + the application content + admin's reason if rejected; if no row, redirect to `/worker/apply`
- [x] 7.11 Create `frontend/app/(protected)/worker/jobs/page.tsx`: server component; if `!session.user.isWorker`, `redirect('/worker/apply')`. Fetches `/api/worker-jobs/open`; renders editorial cards; empty state with explanatory CTA
- [x] 7.12 Create `frontend/app/(protected)/worker/jobs/[id]/page.tsx`: server component fetching `/api/worker-jobs/{id}`. Detail card. Status-conditional CTAs. If report exists, render `<PhotoPair>` for the maintenance history
- [x] 7.13 Create `frontend/app/(protected)/worker/jobs/[id]/report/page.tsx`: `'use client'` form with two datetime-local inputs, two `<input type="file" accept="image/*">` with live preview, a textarea; submits multipart via the worker.ts helper; success redirects to `/worker/jobs/[id]`
- [x] 7.14 Create `frontend/app/(protected)/worker/jobs/mine/page.tsx`: server component fetching `/api/worker-jobs/mine`; renders three sections (`進行中` / `審核中` / `已完成`) filtered by `status`; empty state when none
- [x] 7.15 Update `frontend/app/(protected)/seller/listings/new/page.tsx`: add a `needs_workers` checkbox with helper label `這塊地需要工人後續維護` before the submit button; include the boolean in the request body
- [x] 7.16 Update `frontend/app/(protected)/me/page.tsx`: extend the quick-link section with role-conditional links (worker/apply if not worker, worker/jobs + worker/jobs/mine if worker, admin worker-applications + job-reports if admin)
- [x] 7.17 Hands-on flow: covered by the post-archive e2e sweep — 19-step backend cookie-jar walkthrough (csrf → login → seller create → admin approve → buyer purchase → worker apply → admin approve worker → claim → multipart report → admin approve → rejection-bounce → resubmit → `needs_workers=false` skip-check) all 19 steps passed via curl against running servers
- [x] 7.18 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [x] 7.19 Commit as `feat(frontend): add worker apply + jobs + report surfaces + needs_workers seller checkbox + AppHeader worker nav`

## 8. Frontend Admin Surfaces + Verification

- [x] 8.1 Create `frontend/app/(protected)/admin/worker-applications/page.tsx`: admin-gated (the phase-2 admin layout handles this). Fetches `/api/admin/worker-applications/pending`. Each row: applicant info card (name, age, residence, contact, reason, has_experience badge) + `核准` primary button + `退件` ghost button (with inline reason input)
- [x] 8.2 Create `frontend/app/(protected)/admin/job-reports/page.tsx`: admin-gated. Fetches `/api/admin/job-reports/pending`. Each row: job summary + worker info + `<PhotoPair>` + datetime range + content + `核准回報` primary button + `退件回報` ghost button (with inline reason input)
- [x] 8.3 Server-side admin gate is inherited from `frontend/app/(protected)/admin/layout.tsx` which already redirects non-admin users to `/me` with no content flash; the new pages sit inside that group
- [x] 8.4 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [x] 8.5 Run `cd backend && ./vendor/bin/pest --colors=never` — 133 passed (296 assertions)
- [x] 8.6 ~~Run `cd frontend && pnpm install --frozen-lockfile`~~ — no new npm dependencies were added; existing lockfile remains valid
- [x] 8.7 Run `openspec validate phase-3-jobs` — passes
- [x] 8.8 End-to-end manual: covered by the 19-step backend e2e + 13-step frontend protected-route sweep (10 positive route renders + 3 negative redirects: anonymous→/login, non-worker→/worker/apply, non-admin→/me). Surfaced and fixed a latent phase-1 bug — server-side fetch wasn't forwarding `Referer` so Sanctum's stateful middleware rejected valid sessions (commit 98dcae6). User confirmed login flow works in browser
- [ ] 8.9 a11y spot-check: keyboard tab-through with a real screen reader is genuinely deferred — code-level scaffolding is in place (`role="alert"`, focus rings emerald-600/20, alt text on `<PhotoPair>`, accessible button labels) but a true a11y pass is operator work
- [x] 8.10 Multipart upload edge cases: covered by Pest (`SubmitReportTest`: oversize → 422, non-image .php → 422, datetime_end ≤ datetime_start → 422) plus the e2e step 12 (real-byte JPEG upload via PIL-generated files → 201 + 2 files persisted to `storage/app/public/job-reports/` with random 40-char names) and step 13 (6MB file → 422)
- [x] 8.11 No regressions during 8.1-8.10; no fix commits needed
- [x] 8.12 Commit as `feat(frontend): add admin worker-application + job-report review surfaces`
