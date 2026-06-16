## 1. Database Schema

- [x] 1.1 Create migration `add_needs_workers_to_carbon_listings_table.php`: `$table->boolean('needs_workers')->default(false)->after('status');`
- [x] 1.2 Create migration `create_worker_applications_table.php` with columns per spec: `id`, `user_id` FK users.id cascade **UNIQUE**, `reason` text, `has_experience` boolean, `age` unsignedTinyInteger, `residence` string(255), `contact` string(255), `status` string(20) default `'pending'`, `reviewer_id` FK users.id nullable nullOnDelete, `review_reason` text nullable, `reviewed_at` timestamp nullable, `timestamps()`
- [x] 1.3 Create migration `create_worker_jobs_table.php` with `id`, `carbon_listing_id` FK carbon_listings.id cascade **UNIQUE**, `worker_id` FK users.id nullable nullOnDelete, `status` string(20) default `'open'`, `claimed_at` timestamp nullable, `timestamps()`
- [x] 1.4 Create migration `create_worker_job_reports_table.php` with `id`, `worker_job_id` FK worker_jobs.id cascade **UNIQUE**, `worker_id` FK users.id cascade, `datetime_start` datetime, `datetime_end` datetime, `before_image_path` string(255), `after_image_path` string(255), `content` text, `status` string(20) default `'pending'`, `reviewer_id` FK users.id nullable nullOnDelete, `review_reason` text nullable, `reviewed_at` timestamp nullable, `timestamps()`
- [x] 1.5 Run `cd backend && php artisan migrate` against docker mysql; verify all four tables / column present via `php artisan tinker --execute="echo Schema::hasTable('worker_applications'); echo Schema::hasTable('worker_jobs'); echo Schema::hasTable('worker_job_reports'); echo Schema::hasColumn('carbon_listings', 'needs_workers');"`
- [x] 1.6 Run `php artisan storage:link` once; add `backend/storage/app/public/job-reports/.gitignore` containing `*\n!.gitignore` so the dir exists in git but uploaded files don't
- [x] 1.7 Commit as `feat(backend): add jobs migrations + needs_workers column + storage link`

## 2. Models, State Machines, Role Inference, Factories

- [ ] 2.1 Create `app/Models/WorkerApplication.php` with `$fillable`, `$casts` (`has_experience` → boolean; `reviewed_at` → datetime), `STATUS_PENDING` / `STATUS_APPROVED` / `STATUS_REJECTED` constants, `belongsTo(User)`, `belongsTo(User, 'reviewer_id')` relations
- [ ] 2.2 Implement `WorkerApplication::transitionTo(string $newStatus, array $extras = []): void` with the allowed-transition map from the spec; on approve/reject stamp `reviewer_id = Auth::id()` and `reviewed_at = now()`; on reject store `$extras['review_reason']`
- [ ] 2.3 Register `WorkerApplication::saving()` listener mirroring phase-2's `CarbonListing` pattern: when `status` is dirty, assert allowed; throw `InvalidStateTransition` (reuse phase-2's exception) otherwise
- [ ] 2.4 Create `app/Models/WorkerJob.php` with `$fillable`, `$casts` (`claimed_at` → datetime), `STATUS_OPEN` / `STATUS_CLAIMED` / `STATUS_REPORTED` / `STATUS_APPROVED` / `STATUS_REJECTED` constants, `belongsTo(CarbonListing)`, `belongsTo(User, 'worker_id')`, `hasOne(WorkerJobReport)` relations
- [ ] 2.5 Implement `WorkerJob::transitionTo()` with the allowed map: `open → claimed`, `claimed → reported`, `reported → approved | rejected`, `rejected → claimed`. On `claimed` stamp `claimed_at = now()` if not set; on `claimed` (transitioning from `open`) also set `worker_id = $extras['worker_id'] ?? Auth::id()`
- [ ] 2.6 Register `WorkerJob::saving()` listener with same pattern as 2.3
- [ ] 2.7 Create `app/Models/WorkerJobReport.php` with `$fillable`, `$casts` (`datetime_start` / `datetime_end` / `reviewed_at` → datetime), `STATUS_PENDING` / `STATUS_APPROVED` / `STATUS_REJECTED` constants, `belongsTo(WorkerJob)`, `belongsTo(User, 'worker_id')`, `belongsTo(User, 'reviewer_id')`
- [ ] 2.8 Implement `WorkerJobReport::transitionTo()` with allowed map: `pending → approved | rejected`. On approve/reject stamp `reviewer_id` and `reviewed_at`; on reject store `$extras['review_reason']`
- [ ] 2.9 Register `WorkerJobReport::saved()` listener: when status transitions to `rejected`, call `$report->workerJob->transitionTo('claimed')` and `->save()` inside the parent transaction
- [ ] 2.10 Add `User::workerApplication()` (`hasOne`), `User::workerJobs()` (`hasMany` on `worker_id`)
- [ ] 2.11 Add `User::isWorker(): bool` returning `$this->workerApplication()->where('status', 'approved')->exists()`
- [ ] 2.12 Add factories: `WorkerApplicationFactory`, `WorkerJobFactory`, `WorkerJobReportFactory` with sane defaults and `pending()` / `approved()` / `rejected()` / `claimed()` / `reported()` states
- [ ] 2.13 New `tests/Feature/Worker/StateMachineTest.php`: walk every allowed transition with `transitionTo()` on each of the three models; assert each disallowed transition throws `InvalidStateTransition`; assert direct status assignment is also caught by the saving listener
- [ ] 2.14 New `tests/Feature/Worker/IsWorkerHelperTest.php`: fixtures for (no application, pending, approved, rejected) × assert each gives correct `isWorker` boolean; assert query is an `EXISTS` on the indexed `user_id` column (via `DB::enableQueryLog`)
- [ ] 2.15 Run `./vendor/bin/pest tests/Feature/Worker` — all pass
- [ ] 2.16 Commit as `feat(backend): add WorkerApplication/WorkerJob/WorkerJobReport models + state machines + isWorker helper`

## 3. Carbon Listing MODIFY: needs_workers + auto-create WorkerJob on sold

- [ ] 3.1 Update `app/Models/CarbonListing.php`: add `needs_workers` to `$fillable`, add `'needs_workers' => 'boolean'` to `$casts`
- [ ] 3.2 Extend the existing `CarbonListing::saving()` listener (or add a `saved` listener — choose `saved` so the parent insert is committed before the child insert tries to FK-reference it): when `status` transitions to `'sold'` AND `needs_workers === true`, call `WorkerJob::create(['carbon_listing_id' => $this->id, 'status' => 'open'])` inside the same transaction
- [ ] 3.3 Update `app/Http/Requests/CarbonListings/CreateRequest.php`: add rule `'needs_workers' => 'boolean'` (optional; defaults to false via the migration)
- [ ] 3.4 Update `app/Http/Resources/CarbonListingResource.php`: expose `needs_workers` in the toArray output
- [ ] 3.5 Update phase-2's `CreateTest.php` to assert `needs_workers=false` is the default; add a happy-path test case posting `needs_workers=true`
- [ ] 3.6 New `tests/Feature/CarbonListings/SoldTransitionSideEffectTest.php`: scenario A — listing with `needs_workers=true` is purchased → assert `worker_jobs` row exists with `status=open`, `carbon_listing_id` matches, `worker_id` null. Scenario B — listing with `needs_workers=false` is purchased → assert no `worker_jobs` row exists. Scenario C — force a UNIQUE collision by pre-inserting a `worker_jobs(carbon_listing_id)` row, then attempt purchase → assert the QueryException rolls back the listing transition (listing stays `approved`) and the purchase row is not created
- [ ] 3.7 Run `./vendor/bin/pest tests/Feature/CarbonListings tests/Feature/Worker` — all pass
- [ ] 3.8 Commit as `feat(backend): add needs_workers to listings + sold→WorkerJob auto-create with rollback safety`

## 4. /api/me MODIFY: add isWorker

- [ ] 4.1 Update `AuthController::me()` response: spread `isWorker` into the user payload alongside the existing three flags
- [ ] 4.2 Update phase-2's `tests/Feature/Auth/MeTest.php`: extend the assertion to include the `isWorker` field; add a scenario where an approved `WorkerApplication` exists → assert `isWorker === true`
- [ ] 4.3 Run `./vendor/bin/pest tests/Feature/Auth/MeTest.php` — passes
- [ ] 4.4 Commit as `feat(backend): /api/me returns isWorker flag`

## 5. Worker Application Endpoints + Admin Review + Pest

- [ ] 5.1 Create `app/Http/Requests/Worker/CreateApplicationRequest.php` with rules: `reason required|string|max:2000`, `has_experience required|boolean`, `age required|integer|min:18|max:99`, `residence required|string|max:255`, `contact required|string|max:255`
- [ ] 5.2 Create `app/Http/Requests/Worker/RejectRequest.php` with rule `reason nullable|string|max:1000` (shared between application + report rejection — name it `Worker/RejectRequest.php`)
- [ ] 5.3 Create `app/Policies/WorkerApplicationPolicy.php` with `create`, `view`, `approve`, `reject` methods per spec
- [ ] 5.4 Create `app/Http/Controllers/WorkerApplicationController.php` with `store(CreateApplicationRequest)`, `mine(Request)` methods. `store` enforces UNIQUE by catching `QueryException(23000)` and returning 409. `mine` returns the row or 404
- [ ] 5.5 Create `app/Http/Controllers/Admin/WorkerApplicationReviewController.php` with `pending(Request)`, `approve(WorkerApplication)`, `reject(RejectRequest, WorkerApplication)`
- [ ] 5.6 Register `WorkerApplicationPolicy` in `AppServiceProvider::boot()`
- [ ] 5.7 Register routes in `routes/api.php` inside the existing `auth:sanctum` group: `POST /worker-applications`, `GET /worker-applications/mine`, `GET /admin/worker-applications/pending`, `POST /admin/worker-applications/{workerApplication}/approve`, `POST /admin/worker-applications/{workerApplication}/reject`
- [ ] 5.8 New `tests/Feature/Worker/ApplyTest.php`: 201 + persisted row on happy path; 422 on missing `reason`; 422 on `age=17`; 409 on second apply
- [ ] 5.9 New `tests/Feature/Worker/MineApplicationTest.php`: 404 when no row; 200 with current status when row exists
- [ ] 5.10 New `tests/Feature/Admin/WorkerApplicationReviewTest.php`: admin approve transitions to `approved` and stamps `reviewer_id`; admin reject with reason stores it; non-admin gets 403; double-approve gets `InvalidStateTransition` (caught and surfaced as 422)
- [ ] 5.11 Run `./vendor/bin/pest tests/Feature/Worker tests/Feature/Admin/WorkerApplicationReviewTest.php` — all pass
- [ ] 5.12 Commit as `feat(backend): add worker application endpoints + admin review with Pest coverage`

## 6. Worker Job Endpoints + Report Submission + Admin Report Review + Pest

- [ ] 6.1 Create `app/Policies/WorkerJobPolicy.php` with `viewOpenQueue`, `view`, `claim`, `submitReport` methods per spec
- [ ] 6.2 Create `app/Policies/WorkerJobReportPolicy.php` with `approve`, `reject` methods per spec
- [ ] 6.3 Register both policies in `AppServiceProvider::boot()`
- [ ] 6.4 Create `app/Http/Requests/Worker/SubmitReportRequest.php` with rules: `datetime_start required|date`, `datetime_end required|date|after:datetime_start`, `content required|string|max:2000`, `before_image required|image|mimes:jpg,jpeg,png|max:5120`, `after_image required|image|mimes:jpg,jpeg,png|max:5120`
- [ ] 6.5 Create `app/Http/Controllers/WorkerJobController.php` with `openQueue(Request)`, `mine(Request)`, `show(WorkerJob)`, `claim(WorkerJob)`, `submitReport(SubmitReportRequest, WorkerJob)` methods
- [ ] 6.6 In `openQueue`: `WorkerJob::where('status', 'open')->whereHas('carbonListing', fn($q) => $q->where('user_id', '!=', $request->user()->id))->with('carbonListing')->oldest()->paginate(12)` and `$this->authorize('viewOpenQueue', WorkerJob::class)` at the top
- [ ] 6.7 In `mine`: `WorkerJob::where('worker_id', $request->user()->id)->with(['carbonListing', 'report'])->latest('updated_at')->paginate(50)`
- [ ] 6.8 In `claim`: wrap in `DB::transaction()` with `WorkerJob::lockForUpdate()->findOrFail($job->id)`; re-check `status === 'open'` (return 409 if not); call `$job->transitionTo('claimed', ['worker_id' => Auth::id()])` and save
- [ ] 6.9 In `submitReport`: `$this->authorize('submitReport', $job)`; inside `DB::transaction()`, if a `rejected` report exists for this job delete it first; store both images via `->store('job-reports', 'public')`; create new `WorkerJobReport(status=pending, ...)`; transition `$job` to `reported` and save
- [ ] 6.10 Create `app/Http/Controllers/Admin/WorkerJobReportReviewController.php` with `pending(Request)`, `approve(WorkerJobReport)`, `reject(RejectRequest, WorkerJobReport)` methods. `approve` transitions report to approved AND parent job to approved inside `DB::transaction()`. `reject` transitions report to rejected (the model's `saved` listener flips parent job to claimed)
- [ ] 6.11 Register routes inside the existing `auth:sanctum` group: `GET /worker-jobs/open`, `GET /worker-jobs/mine`, `GET /worker-jobs/{workerJob}`, `POST /worker-jobs/{workerJob}/claim`, `POST /worker-jobs/{workerJob}/report`, `GET /admin/job-reports/pending`, `POST /admin/job-reports/{workerJobReport}/approve`, `POST /admin/job-reports/{workerJobReport}/reject`
- [ ] 6.12 New `tests/Feature/Worker/OpenQueueTest.php`: returns open jobs excluding caller's own land; non-worker gets 403
- [ ] 6.13 New `tests/Feature/Worker/ClaimTest.php`: worker claims open → 200, status=claimed, worker_id set; worker claims claimed → 409; non-worker claims → 403; concurrent claim — simulate two parallel transactions, assert second gets 409 via the post-lock re-check
- [ ] 6.14 New `tests/Feature/Worker/SubmitReportTest.php`: happy path 201 with two valid JPEGs (assert files exist on disk); 422 on oversized file; 422 on non-image (post a fake `.php` file); 403 if not the claiming worker; double-submit gets 409 (UNIQUE)
- [ ] 6.15 New `tests/Feature/Worker/ResubmitAfterRejectionTest.php`: setup — claim, report, admin reject → assert job back to `claimed`. Then worker submits new report → assert old rejected row deleted, new pending row exists, job back to `reported`
- [ ] 6.16 New `tests/Feature/Admin/JobReportReviewTest.php`: admin approve → report+job both approved; admin reject with reason → report rejected, job back to claimed (the listener side effect); non-admin gets 403
- [ ] 6.17 Run `./vendor/bin/pest tests/Feature/Worker tests/Feature/Admin` — all pass
- [ ] 6.18 Commit as `feat(backend): add worker job claim/report + admin report review + race defense + multipart upload`

## 7. Frontend AppHeader Update + Worker Surfaces

- [ ] 7.1 Update `frontend/lib/types/user.ts`: add optional `isWorker?: boolean` to the `User` type
- [ ] 7.2 Update `frontend/lib/session/getSessionFromCookies.ts` (or equivalent server helper): propagate `isWorker` from the `/api/me` payload to the session object
- [ ] 7.3 Create `frontend/lib/session/getApplicationStatusFromCookies.ts`: server-side helper that GETs `/api/worker-applications/mine` with the request cookies; returns `null` | `'pending'` | `'approved'` | `'rejected'`
- [ ] 7.4 Update `frontend/app/(protected)/layout.tsx`: in addition to `getSessionFromCookies()`, also call `getApplicationStatusFromCookies()` and pass both to `<AppHeader>`
- [ ] 7.5 Update `frontend/components/AppHeader.tsx`: accept new `applicationStatus` prop; render worker-conditional nav per spec (`工人申請` / `申請狀態` / `工作機會` / `我的工作` / admin's `工人申請審核` / `工作回報審核`)
- [ ] 7.6 Update `frontend/DESIGN_SYSTEM.md`: add the new CTA labels (`認領` / `提交回報` / `核准回報` / `退件回報` / `工人申請` / `申請狀態` / `工作機會` / `我的工作` / `工人申請審核` / `工作回報審核`) and document the `<PhotoPair>` primitive
- [ ] 7.7 Create `frontend/components/PhotoPair.tsx`: `{ before: string; after: string; className?: string }`; renders two side-by-side images with labels `前` / `後` using Tailwind; clicking enlarges in a dialog (reuse phase-2's confirm dialog primitive if applicable, or render via `<dialog>` element)
- [ ] 7.8 Create `frontend/lib/api/worker.ts`: typed client wrapping `createApplication`, `getMyApplication`, `getOpenJobs`, `getMyJobs`, `getJob`, `claimJob`, `submitReport(jobId, formData)` (note: submitReport uses FormData, not JSON)
- [ ] 7.9 Create `frontend/app/(protected)/worker/apply/page.tsx`: client form with `<Field>` × 5; on 409 redirect to `/worker/apply/status`; on success redirect to `/worker/apply/status`
- [ ] 7.10 Create `frontend/app/(protected)/worker/apply/status/page.tsx`: server component fetching `/api/worker-applications/mine`; renders `<StatusBadge>` (reuse phase-2's badge with new labels `審核中` / `已核准` / `已退件`) + the application content + admin's reason if rejected; if no row, redirect to `/worker/apply`
- [ ] 7.11 Create `frontend/app/(protected)/worker/jobs/page.tsx`: server component; if `!session.user.isWorker`, `redirect('/worker/apply')`. Fetches `/api/worker-jobs/open`; renders editorial cards (reuse market layout); empty state with explanatory CTA
- [ ] 7.12 Create `frontend/app/(protected)/worker/jobs/[id]/page.tsx`: server component fetching `/api/worker-jobs/{id}`. Detail card. Status-conditional CTAs per spec. If approved report exists, render `<PhotoPair>` for the public history
- [ ] 7.13 Create `frontend/app/(protected)/worker/jobs/[id]/report/page.tsx`: `'use client'` form with two datetime-local inputs, two `<input type="file" accept="image/*">`, a textarea; submits multipart via the worker.ts helper; success redirects to `/worker/jobs/[id]`; error → red error block per design system
- [ ] 7.14 Create `frontend/app/(protected)/worker/jobs/mine/page.tsx`: server component fetching `/api/worker-jobs/mine`; renders three sections (`進行中` / `審核中` / `已完成`) filtered by `status`; empty state per section
- [ ] 7.15 Update `frontend/app/(protected)/seller/listings/new/page.tsx`: add a `needs_workers` checkbox with helper label `這塊地需要工人後續維護` before the submit button; include the boolean in the request body
- [ ] 7.16 Update `frontend/app/(protected)/me/page.tsx`: extend the quick-link section with role-conditional links to `/worker/apply` (or `/worker/apply/status`) / `/worker/jobs` / `/worker/jobs/mine`
- [ ] 7.17 Hands-on flow: register a fresh user, submit application, log in as admin and approve, switch back to user, verify `工作機會` appears in header. As seller, create a listing with `needs_workers=true`, log in as admin to approve, switch to buyer, purchase. Switch back to worker, see the new open job at `/worker/jobs`, claim it, submit report with two photos, switch to admin and approve. Verify worker's `/worker/jobs/mine` shows the job under `已完成`
- [ ] 7.18 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [ ] 7.19 Commit as `feat(frontend): add worker apply + jobs + report surfaces + needs_workers seller checkbox + AppHeader worker nav`

## 8. Frontend Admin Surfaces + Verification

- [ ] 8.1 Create `frontend/app/(protected)/admin/worker-applications/page.tsx`: admin-gated (the phase-2 admin layout handles this). Fetches `/api/admin/worker-applications/pending`. Each row: applicant info card (name, age, residence, contact, reason, has_experience badge) + `核准` primary button + `退件` ghost button (with inline reason input)
- [ ] 8.2 Create `frontend/app/(protected)/admin/job-reports/page.tsx`: admin-gated. Fetches `/api/admin/job-reports/pending`. Each row: job summary + worker info + `<PhotoPair>` + datetime range + content + `核准回報` primary button + `退件回報` ghost button (with inline reason input)
- [ ] 8.3 Hands-on flow: as a non-admin, navigate directly to `/admin/worker-applications` and `/admin/job-reports`. Verify both server-redirect to `/me` with no admin content flashing
- [ ] 8.4 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [ ] 8.5 Run `cd backend && ./vendor/bin/pest --colors=never` — all phase-0 + phase-1 + phase-2 + phase-3 tests pass
- [ ] 8.6 Run `cd frontend && pnpm install --frozen-lockfile` exits 0
- [ ] 8.7 Run `openspec validate phase-3-jobs` — passes; `openspec validate --all` shows all specs and the active change passing
- [ ] 8.8 End-to-end manual: docker compose up; backend serve; frontend dev. Walk the full apply → admin approve → claim → report → admin approve loop on real UI. Verify the seller's `needs_workers=false` listings do NOT auto-create jobs after purchase
- [ ] 8.9 a11y spot-check: tab through the apply form, verify focus rings emerald-600/20 visible; verify error messages have `role="alert"`; verify admin review buttons + the inline reason input have accessible labels; verify `<PhotoPair>` images have descriptive alt text including `前環境照片` / `後環境照片`
- [ ] 8.10 Verify multipart upload edge cases via curl or the UI: post a 6 MB JPEG → 422; post a `.php` file renamed `.jpg` → 422; post a valid pair → 201 and files visible at `/storage/job-reports/<hash>.jpg`
- [ ] 8.11 If anything broke during 8.1-8.10, commit fixes as `fix(...)` and re-run from 8.5. Otherwise no additional commit
- [ ] 8.12 Commit as `feat(frontend): add admin worker-application + job-report review surfaces`
