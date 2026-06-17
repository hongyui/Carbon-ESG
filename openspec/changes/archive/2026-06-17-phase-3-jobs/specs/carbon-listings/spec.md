## MODIFIED Requirements

### Requirement: Carbon Listing Resource

The backend SHALL provide a `carbon_listings` table and an Eloquent `CarbonListing` model that owns the full lifecycle of a land's carbon credit offering: text fields, area, estimated absorption, asking price, status, maintenance flag, and audit pointers.

The model MUST include at minimum the following columns: `id`, `user_id` (FK → users, on delete cascade), `title` (string), `description` (text), `hectares` (decimal 8,2), `tonnes_co2e` (decimal 10,2), `location` (string), `price_twd` (decimal 12,2), `status` (string enum), `needs_workers` (tinyint 1, default 0), `admin_note` (text nullable), `approved_by` (FK → users.id, nullable), `approved_at` (timestamp nullable), `created_at`, `updated_at`.

The `status` column MUST take one of exactly five string values: `pending`, `approved`, `rejected`, `recalled`, `sold`. New listings are created at `pending`. The values `rejected`, `recalled`, and `sold` are terminal.

The `needs_workers` column is a seller-supplied flag at listing creation time indicating that, after purchase, the land needs a worker to perform maintenance and submit a work report. The flag drives a side effect on the `sold` transition (see "Status State Machine").

#### Scenario: Migration creates carbon_listings with the required columns
- **WHEN** a developer runs `php artisan migrate`
- **THEN** a `carbon_listings` table exists in MySQL with the columns and types listed above, with `status` defaulting to `'pending'`, `needs_workers` defaulting to `0`, and a NOT NULL constraint on `user_id`, `title`, `hectares`, `tonnes_co2e`, `location`, `price_twd`, `needs_workers`

#### Scenario: A new listing defaults to pending and needs_workers=false
- **WHEN** `CarbonListing::create(['user_id' => $user->id, 'title' => '...', ...])` runs without an explicit `status` or `needs_workers` field
- **THEN** the persisted row has `status = 'pending'`, `needs_workers = false`, `approved_by = null`, `approved_at = null`, `admin_note = null`

#### Scenario: Seller can opt-in to needs_workers at creation
- **WHEN** a seller POSTs `/api/carbon-listings` with `needs_workers: true` in the JSON body
- **THEN** the persisted row has `needs_workers = true`, and the `CarbonListingResource` returned to the client includes `needs_workers: true`. The flag is immutable post-create — there is no `PATCH /api/carbon-listings/{id}` endpoint that mutates it in v1

### Requirement: Status State Machine

The `CarbonListing` model SHALL expose a `transitionTo(string $newStatus, array $extras = []): void` method that enforces the allowed transition graph. Controllers MUST call this method instead of assigning to `$listing->status` directly.

Allowed transitions:

| From | To |
|---|---|
| `pending` | `approved`, `rejected`, `recalled` |
| `approved` | `sold`, `recalled` |
| `rejected` | (terminal — no transition allowed) |
| `recalled` | (terminal — no transition allowed) |
| `sold` | (terminal — no transition allowed) |

`transitionTo('approved')` MUST also stamp `approved_by` (the calling admin's id) and `approved_at` (now). `transitionTo('rejected', ['admin_note' => $reason])` MUST also store the optional reason on `admin_note`. Any other key in `$extras` is ignored.

A model-level `saving` boot listener SHALL also assert the transition's validity when `status` is dirty, catching any code path that bypasses `transitionTo()` (e.g. mass assignment via `update()`).

**Side effect on `sold` transition**: when the listener observes a transition to `sold` AND `needs_workers === true`, it MUST atomically create a `WorkerJob(carbon_listing_id = $listing->id, status = 'open', worker_id = null)` row inside the same DB transaction. The side effect is idempotent in the sense that any pre-existing `WorkerJob` for the listing surfaces as a `QueryException` from the UNIQUE constraint, which rolls back the surrounding purchase transaction (see the `jobs` capability for the corresponding requirement).

#### Scenario: Allowed transition succeeds
- **GIVEN** a `CarbonListing` with `status = 'pending'`
- **WHEN** `$listing->transitionTo('approved')` is called
- **THEN** the in-memory `status` is `'approved'`, `approved_by` and `approved_at` are set, and `$listing->save()` persists without error

#### Scenario: Disallowed transition throws InvalidStateTransition
- **GIVEN** a `CarbonListing` with `status = 'sold'`
- **WHEN** any code calls `$listing->transitionTo('recalled')`
- **THEN** an `InvalidStateTransition` exception is thrown, no DB write occurs, and the listing's status remains `'sold'`

#### Scenario: Direct status assignment is caught by the saving listener
- **WHEN** code sets `$listing->status = 'sold'` and calls `$listing->save()` without going through `transitionTo()`
- **THEN** the `saving` listener inspects the dirty value, finds the transition from the original status to `'sold'` either allowed (in which case the save succeeds) or disallowed (in which case `InvalidStateTransition` is thrown)

#### Scenario: Sold transition with needs_workers=true triggers WorkerJob auto-creation
- **GIVEN** a `CarbonListing` with `status = 'approved'` and `needs_workers = true`, inside a `PurchaseController` transaction
- **WHEN** the listener observes the transition to `sold`
- **THEN** within the same transaction a `worker_jobs(carbon_listing_id = $listing->id, status = 'open', worker_id = null)` row is inserted; if the insert fails for any reason (e.g. a stale UNIQUE collision), the parent transaction rolls back and the listing remains `approved`

#### Scenario: Sold transition with needs_workers=false has no WorkerJob side effect
- **GIVEN** a `CarbonListing` with `status = 'approved'` and `needs_workers = false`, inside a `PurchaseController` transaction
- **WHEN** the listener observes the transition to `sold`
- **THEN** the listing transitions cleanly, a `carbon_purchases` row is inserted (phase-2 behavior), and NO `worker_jobs` row is created
