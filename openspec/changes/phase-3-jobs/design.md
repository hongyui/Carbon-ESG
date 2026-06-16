## Context

[Phase-2](../archive/2026-06-14-phase-2-carbon-listings/proposal.md) shipped the commercial loop — seller / admin / buyer can now transact. Phase-3 closes the **operational loop**: a sold piece of land actually needs a human to go tidy it before the carbon credit attached to it can be considered legitimate. The legacy site recognized this with the `工人` role and a job-recall workflow that mixed seller upload, admin review, and worker assignment into the same `carbontotal` table.

Phase-3 fixes that mess. We add a fourth behavioral role (`isWorker`), three new resources (`WorkerApplication` / `WorkerJob` / `WorkerJobReport`), and the first multipart upload surface in the new stack. We also reach back into the `carbon_listings` capability and add one column (`needs_workers`) plus one state-machine side effect (auto-create `WorkerJob` on `sold` with the flag set) — these are the cleanest seams between phase-2's marketplace and phase-3's maintenance layer.

The risk profile is different from phase-2. Phase-2 was "many endpoints, simple race"; phase-3 is "fewer endpoints but two new races (claim, resubmit) AND the file-upload attack surface AND a cross-table side effect (sold → WorkerJob)." The mitigation is the same playbook — small commit-bounded task groups + Pest tests per scenario — but the design decisions matter more than the volume.

Phase-3 is also the **last DB-only phase**. Phase-4 will graft Web3 on top of both `POST /purchase` and `POST /admin/job-reports/.../approve`. To keep phase-4 a strict superset, phase-3's `transitionTo()` methods take an `$extras` array and the controllers wrap mutations in `DB::transaction()` — both choices are designed to give phase-4 a place to hang the contract call without re-architecting.

## Goals / Non-Goals

**Goals:**

- Any authenticated user can apply to be a worker exactly once. Admin reviews; approved applications flip `User::isWorker()` to true on the next `/api/me`
- A seller can flag `needs_workers` at listing creation. When a buyer purchases such a listing, the `sold` transition atomically creates an open `WorkerJob`, all in one DB transaction
- An approved worker can browse open jobs and atomically claim one — no two workers on the same plot. Claim is race-defended by `lockForUpdate()` + post-lock status check (modeled on phase-2 purchase)
- The claiming worker can submit exactly one report per job with two image uploads (front/back), validated for MIME + size before any disk write. Multipart endpoint is the only file-upload surface in the new stack as of phase-3
- Admin reviews reports. Approval is terminal. Rejection is recoverable — it bounces the parent job back to `claimed` so the same worker can submit a new report; the rejected report row stays in DB for one audit cycle and is then replaced by the worker's next submission
- The frontend `AppHeader` shows worker-aware nav (`工人申請` / `申請狀態` / `工作機會` / `我的工作` / admin's `工人申請審核` / `工作回報審核`) driven by `isWorker` + a lightweight per-navigation application-status fetch
- 100% of new endpoints have at least one happy-path + one authz / state-failure Pest test. The two race conditions (claim, sold→job auto-create) each have a dedicated concurrent test
- The new file upload surface has a dedicated Pest test asserting that an oversized file and a non-image file both 422 BEFORE any disk write

**Non-Goals:**

- **Web3 on-chain settlement on report approval** — `POST /admin/job-reports/{id}/approve` writes only to DB. Phase-4 grafts the contract call onto this approval AND onto the existing `POST /purchase`. Phase-3 picks the `transitionTo()` + `DB::transaction()` shape with phase-4 in mind but does no contract work
- **S3-compatible storage** — phase-3 uses Laravel local disk + `php artisan storage:link`. `Storage::disk('public')->putFile('job-reports', $file)`. The bucket move is deferred — likely a `phase-3.5-storage` if it blocks production deploy, otherwise rolled into phase-4
- **Signed URLs / private disk** — `/storage/job-reports/<random>.jpg` is publicly readable post-`storage:link`. Filenames are 40-char random so unguessable, but we accept the residual risk for v1. Hardening goes with the S3 move
- **Email notifications** — admin / worker / seller don't get email on state changes. The pattern (Mail facade, Mailable classes) is unchanged from phase-2's non-goal
- **Worker rejection → reapply UX** — `WorkerApplication` is one-per-user enforced by UNIQUE. A rejected applicant cannot reapply via the API in v1; admin would have to hard-delete the row
- **Multiple workers per job** — `WorkerJob.worker_id` is single-valued. No squad work, no work splitting, no shift handoffs in v1
- **Job auto-expiry / SLA** — an open `WorkerJob` does not time out. A claimed `WorkerJob` does not get reclaimed if the worker disappears. v2 problem
- **Worker rating / reputation** — no `worker_quality` or `worker_score` column. Approved is approved; admins see report-by-report quality but the system doesn't aggregate
- **Reassigning a job to a different worker after rejection** — when admin rejects a report, the job stays bound to the same worker. The worker submits a new report or the job sits forever. v2 problem

## Decisions

### 1. `needs_workers` lives on `carbon_listings`, NOT on a separate `listing_metadata` table

Two options were considered:

**Option A** — Add `needs_workers` as a column directly on `carbon_listings`. Modifies the phase-2 spec. Simple.

**Option B** — Create `carbon_listing_metadata(carbon_listing_id, needs_workers, ...)` as a side table. Leaves phase-2's spec untouched. Future-flexible.

**Selected: A.** Three reasons:

1. **`needs_workers` is intrinsic to the listing.** It is not metadata the platform adds — it is a seller decision at creation time, immutable post-create, and read on every state-machine transition. Putting it on a side table introduces a JOIN on the hot path (purchase) for no semantic gain
2. **Modifying the phase-2 spec is the honest move.** The phase-2 listing contract really does change — a column is added, the create endpoint accepts a new field, the detail response exposes a new field. OpenSpec's whole point is to record this kind of evolution. The `MODIFIED Requirements` block in the carbon-listings delta makes the change traceable
3. **Future flags fit the same column shape.** If phase-5 adds `needs_certification: bool`, it goes on the listing too. We are not designing a flag system; we are adding one well-bounded flag

The cost is the phase-2 spec is no longer immutable. That is fine — OpenSpec deltas are for exactly this case. Migration is one `add column` migration with a default 0.

### 2. `WorkerJob` is auto-created via a `saving` listener, NOT by the `PurchaseController` calling `WorkerJob::create()` directly

**Option A** — `PurchaseController` writes purchase + transitions listing + (if `needs_workers`) writes `WorkerJob`, all inline in the controller.

**Option B** — `PurchaseController` writes purchase + transitions listing. `CarbonListing`'s `saving` listener detects the transition to `sold` and writes the `WorkerJob` as a side effect of the model save.

**Selected: B.** Two reasons:

1. **The phase-2 `saving` listener already exists for transition validation.** Adding the auto-create branch to the existing listener is a 4-line change. Putting the same logic in the controller duplicates the "am I transitioning to sold?" check across model + controller and lets future state-changing code paths skip the auto-create
2. **It puts the side effect under the state machine's protection.** If a future controller calls `$listing->update(['status' => 'sold'])` directly — bypassing `transitionTo()` — the listener still runs, still sees the dirty status, still creates the job. With Option A, the new controller silently misses the auto-create

The cost is the listener is now load-bearing for a second concern (validation + side effect). We mitigate by keeping the two responsibilities in separate methods inside the listener, with one dedicated Pest test per concern.

### 3. Race-defended claim via `lockForUpdate()` + post-lock re-read, NOT a UNIQUE-only defense

Phase-2's purchase race is defended by `UNIQUE(carbon_listing_id)` on `carbon_purchases` — the second insert just fails. Why not the same for claim?

Because claim does not insert a new row. It UPDATEs `worker_jobs.worker_id` from NULL to the caller's id. There is no UNIQUE we can attach to that — `UNIQUE(carbon_listing_id)` on `worker_jobs` exists but it is satisfied before any claim happens (one job per listing is structural, but two claims target the same row, not different rows).

Options considered:

**Option A** — Wrap claim in `DB::transaction()` + `lockForUpdate()`. After the lock, re-read `status` and bail with 409 if it is not `'open'`. Atomic in the sense that pessimistic locking guarantees the second transaction sees the first's commit.

**Option B** — Use Postgres-style `UPDATE ... WHERE status='open' RETURNING id`. Atomic via the conditional update. But MySQL's emulation of this is awkward and our stack is MySQL 8.

**Option C** — Optimistic locking with a `version` column. Heavier, requires a new column, more failure modes.

**Selected: A.** It is the exact pattern phase-2 used for purchase. We already have the test scaffolding (concurrent request simulation via Pest's `DB::transaction()` + `pcntl_fork()` or — since pcntl is unreliable in tests — via parallel `Bus::dispatchSync()` with explicit transaction handles). Consistency wins.

### 4. Rejection bounces job to `claimed`, not `open`; resubmission deletes the rejected report row

The legacy site had no rejection-recovery flow — once admin rejected, the worker was just stuck. We need to model this. Two options:

**Option A** — Reject the report → job goes back to `open`, anyone (including a different worker) can re-claim and resubmit.

**Option B** — Reject the report → job stays bound to the original worker (`status = 'claimed'`, `worker_id` unchanged). Only the original worker can resubmit; if they disappear, an admin would need to intervene manually.

**Selected: B.** The original worker has context — they went to the land, took photos, wrote the description. Throwing all that away and giving someone else a clean slate is worse for the platform's data quality. The cost is "what if the original worker ghosts" — accepted as a v2 problem (admin can hard-update the job manually).

Resubmission policy: when the worker POSTs a new report and a rejected report row exists for the same job, the new submission MUST delete the old row inside the transaction. The UNIQUE FK on `worker_job_reports.worker_job_id` forces this — we cannot have two reports for one job. The rejected payload is lost from the application DB but preserved in DB binlog (if configured) or via an explicit `audit_log` table (v2). We document this as "one rejection cycle per job" in the spec.

### 5. Multipart upload with framework-level validation BEFORE any disk write

The legacy `registerjobsave.php` was the canonical example of how not to do this — no MIME check, no size check, `move_uploaded_file()` to a path constructed from `pathinfo()` on user input. The new path:

```php
// SubmitReportRequest extends FormRequest
public function rules(): array {
    return [
        'datetime_start' => 'required|date',
        'datetime_end' => 'required|date|after:datetime_start',
        'content' => 'required|string|max:2000',
        'before_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        'after_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
    ];
}
```

Laravel's `image|mimes:` rule reads the file's **actual content** (via `getimagesize()` and `finfo`), not just the extension. A `.jpg`-named PHP webshell fails the rule. The validator runs BEFORE the controller body so an unauthorized upload never touches disk — important because once a file is on disk, even a moment later, you have the option of a TOCTOU-style attack if cleanup logic has bugs.

After validation, we use the framework helper:

```php
$beforePath = $request->file('before_image')->store('job-reports', 'public');
```

`store()` generates a random hash filename, uses the `public` disk (which resolves to `storage/app/public/` per our `filesystems.php` default), and returns the relative path. We persist the relative path (`job-reports/abc123.jpg`) and let the frontend prefix `/storage/` to construct the URL.

### 6. `WorkerJob` does NOT have `status='cancelled'` for unclaimed-forever jobs

The state graph is `open → claimed → reported → approved` with a side branch `reported → rejected → claimed`. There is no terminal "this listing was sold, flagged for workers, but no worker ever came" state. We considered adding one but rejected:

1. **It is meaningless without auto-expiry.** If we don't have a job lifetime, "cancelled" is just a button an admin manually clicks. That is one more endpoint with no clear use case (admins don't browse open jobs in v1 — they only see report-review queues)
2. **The data is already capturable.** A stuck `WorkerJob` shows up in admin SQL as "open, created N days ago, never claimed." A future v2 dashboard can highlight these. We don't need an enum value to find them

Trade-off: if phase-4 adds the on-chain settlement and a stuck job becomes a financial liability (buyer paid, no work was done), we'll need a remediation flow. That is phase-4's problem.

### 7. `isWorker` is computed on EVERY request to `/api/me`, no caching

Same decision as phase-2's `isSeller` / `hasPurchased`. Cost: one `EXISTS` query per `/api/me`. Benefit: never-stale role flag, no cache invalidation logic, no edge case where admin approval doesn't take effect until next session.

The query is indexed (`worker_applications.user_id` is UNIQUE → indexed → `EXISTS` returns in <1ms on any realistic table size). If `/api/me` becomes a hot path we can revisit; current load envelope says no.

### 8. Pending-application detection in `AppHeader` uses a single layout-level fetch, NOT `/api/me`

We could put `applicationStatus` on the `User` payload as a fifth flag. We don't, because:

1. **It is not a role.** It is a per-flow transient state. `/api/me` is for stable identity info; transient state belongs with the surface that needs it
2. **The fetch is server-side in `(protected)/layout.tsx`.** It runs once per navigation, not per request, and the result is passed down via prop. No client-side waterfall
3. **It keeps `/api/me` cheap.** Adding a fourth `EXISTS` query per `/api/me` call to detect "do you have any non-terminal application?" is a hot-path tax we don't need to pay

The layout-level fetch reuses `getSessionFromCookies()`'s pattern with a new `getApplicationStatusFromCookies()` helper. Returns one of `null` | `'pending'` | `'approved'` | `'rejected'` and the header logic branches on that.

## Risks / Trade-offs

**[Sold→Job auto-create silently doesn't fire]** → If a future code path mutates `carbon_listings.status` via raw SQL (`DB::statement('UPDATE ...')`), the Eloquent `saving` listener never runs and the side effect is skipped. **Mitigation**: a Pest test does this exact bypass and asserts the row count for `worker_jobs` differs from the expected count. The test failing is the canary for "someone added a raw SQL update path." We also add a `CHECK` constraint to MySQL 8 if available — `CHECK (status IN ('pending', 'approved', 'rejected', 'recalled', 'sold'))` — which doesn't prevent the bypass but documents the intent at the schema level.

**[Multipart upload exhausts disk]** → Two 5 MB files per report, 10 MB per submission. A single malicious approved-worker user could create N reports across N claimed jobs and consume `10 * N` MB. **Mitigation**: the rate limit is structurally bounded by claim — a user can only have as many claimed jobs as there are `open` `WorkerJob` rows, which is bounded by sold listings flagged `needs_workers`. We don't add an explicit per-user storage quota in v1 because the bound is naturally tight. If phase-4+ relaxes the claim model, we revisit.

**[Image path leaks PII]** → Anyone with the storage URL can view the photo. **Mitigation**: random 40-char filenames make URLs unguessable. Admin reviewers see the URLs; non-admin endpoints never return the path of a non-owned report. Privacy is enforced by the policy (controller never returns paths the caller isn't allowed to see), not by URL secrecy alone. Hardening to signed URLs comes with the S3 phase.

**[Concurrent claim test flakiness]** → MySQL transaction isolation in CI can behave differently than locally; the "two workers claim simultaneously" test can pass by accident in CI even if the locking is broken. **Mitigation**: the test explicitly uses `DB::beginTransaction()` + `lockForUpdate()` + an inner `DB::beginTransaction()` from a second `User::factory()` actor, with a `sleep(0)` between the lock acquisition and the post-lock check. If the second transaction succeeds, the test fails — we don't rely on "well, it works most of the time."

**[State-machine drift on rejection]** → A future controller might transition the report to `rejected` without flipping the job back to `claimed`. The job is stranded in `reported` with a rejected report. **Mitigation**: a `saved` listener on `WorkerJobReport` asserts the invariant — when a report's status is `rejected`, the parent job's status is either `claimed` or `rejected` (the second case happens during cascade scenarios). A Pest test forces a direct status mutation and asserts the listener throws.

**[`isWorker` query missing index]** → If the migration accidentally drops the UNIQUE on `worker_applications.user_id`, the `EXISTS` query on every `/api/me` does a full table scan. **Mitigation**: the migration ASSERTS the UNIQUE constraint exists (not just an index) and the migration test does an `EXPLAIN` on the query to assert it uses the index.

**[Multipart + CSRF interplay]** → Laravel's CSRF middleware reads the token from `XSRF-TOKEN` cookie; the frontend axios client sends it as `X-XSRF-TOKEN` header. Multipart forms in browsers historically need explicit handling. **Mitigation**: we keep using axios with `withCredentials: true` + `withXSRFToken: true`. The phase-1 Sanctum SPA scenario in `specs/auth/spec.md` documents the cookie/header alignment; multipart inherits this. A Pest test asserts that a multipart POST without the CSRF token returns 419 (CSRF mismatch), matching phase-1's behavior.

## Migration Plan

Phase-3 ships in 8 commit-bounded task groups (see `tasks.md`). The order is:

1. Schema migrations (new tables + `add needs_workers` to existing) — runs `php artisan migrate` cleanly, no data backfill needed
2. Backend models + state machines + factories — no API changes yet, all tests inside
3. Backend policies + Worker / Admin controllers + routes — API endpoints come online
4. Backend file-upload validation + image storage wiring — `php artisan storage:link` runs in setup
5. Phase-2 spec MODIFY: add `needs_workers` to listing create + detail; add saving-listener side effect
6. `/api/me` extension to include `isWorker`
7. Frontend role-aware nav update + new surfaces (worker apply, jobs, report, admin review)
8. Verification: full Pest run, `tsc --noEmit`, hands-on end-to-end loop

Deploy is a single `php artisan migrate` + `php artisan storage:link` + redeploy. There is no data backfill — all new tables start empty, the new column defaults to 0 on existing rows (no existing listing is flagged `needs_workers` until a seller updates… actually, there is no update endpoint in v1 so existing pre-phase-3 listings stay as `needs_workers = 0` forever).

**Rollback**: drop the three new tables + the new column. Existing `carbon_listings` / `carbon_purchases` / `worker_applications` data is unaffected. `/api/me` falls back to phase-2's three-flag shape (the fourth flag becomes a 404 from the frontend's perspective, which is gracefully degraded by the optional-type definition).

## Open Questions

- **Should `WorkerApplication` rejection allow re-application after N days?** v1 says no. If product feedback says yes, we add `WorkerApplication::canReapply()` returning `created_at < now()->subDays(30)` and the create endpoint reads it
- **Should admin be able to delete a `WorkerApplication` row?** v1 says no (terminal means terminal). If we add it later, it is a separate endpoint not a state transition
- **Image dimensions / aspect ratio enforcement?** v1 says no (any JPEG/PNG under 5 MB works). If reports are being submitted with low-quality phone photos, we add a `dimensions:min_width=800,min_height=600` rule
- **Worker job description / requirements?** v1's `WorkerJob` has no description — the worker reads the parent `CarbonListing` to understand what land they're claiming. If sellers want to specify "please remove invasive species in the southeast corner," that is a new `CarbonListing.maintenance_notes` field, not a `WorkerJob` field. Deferred to feedback
