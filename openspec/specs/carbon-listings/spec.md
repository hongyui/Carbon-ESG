# carbon-listings Specification

## Purpose

Defines Carbon-ESG's commercial core: how a landowner registers a carbon credit for sale, how an administrator reviews and gates it onto the market, how a buyer purchases it, and how role discrimination flows from row existence rather than enum columns on `users`. The capability owns two tables (`carbon_listings`, `carbon_purchases`), a five-state state machine guarding listing lifecycle transitions, a Laravel Policy that replaces ad-hoc role checks, and the full set of seller / buyer / admin endpoints plus the Next.js surfaces that consume them. Web3 settlement and worker job flows are explicitly out of scope; this spec governs the off-chain commercial contract only.

## Requirements

### Requirement: Carbon Listing Resource

The backend SHALL provide a `carbon_listings` table and an Eloquent `CarbonListing` model that owns the full lifecycle of a land's carbon credit offering: text fields, area, estimated absorption, asking price, status, and audit pointers.

The model MUST include at minimum the following columns: `id`, `user_id` (FK → users, on delete cascade), `title` (string), `description` (text), `hectares` (decimal 8,2), `tonnes_co2e` (decimal 10,2), `location` (string), `price_twd` (decimal 12,2), `status` (string enum), `admin_note` (text nullable), `approved_by` (FK → users.id, nullable), `approved_at` (timestamp nullable), `created_at`, `updated_at`.

The `status` column MUST take one of exactly five string values: `pending`, `approved`, `rejected`, `recalled`, `sold`. New listings are created at `pending`. The values `rejected`, `recalled`, and `sold` are terminal.

#### Scenario: Migration creates carbon_listings with the required columns
- **WHEN** a developer runs `php artisan migrate`
- **THEN** a `carbon_listings` table exists in MySQL with the columns and types listed above, with `status` defaulting to `'pending'` and a NOT NULL constraint on `user_id`, `title`, `hectares`, `tonnes_co2e`, `location`, `price_twd`

#### Scenario: A new listing defaults to pending
- **WHEN** `CarbonListing::create(['user_id' => $user->id, 'title' => '...', ...])` runs without an explicit `status` field
- **THEN** the persisted row has `status = 'pending'`, `approved_by = null`, `approved_at = null`, `admin_note = null`

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

### Requirement: Carbon Purchase Resource

The backend SHALL provide a `carbon_purchases` table and an Eloquent `CarbonPurchase` model that records every completed buy. A purchase row MUST exist for every `sold` listing and MUST NOT exist for any non-`sold` listing.

The model MUST include at minimum: `id`, `carbon_listing_id` (FK → carbon_listings, on delete cascade, **UNIQUE**), `buyer_id` (FK → users), `price_twd` (decimal 12,2, snapshotted from the listing at purchase time), `created_at`, `updated_at`.

The `UNIQUE` constraint on `carbon_listing_id` is the primary defense against the simultaneous-purchase race condition.

#### Scenario: Purchase row is created atomically with the listing transition
- **WHEN** a buyer's `POST /api/carbon-listings/{id}/purchase` succeeds
- **THEN** within a single `DB::transaction()`: a `carbon_purchases` row is inserted with `buyer_id = current_user`, `price_twd = listing.price_twd`, and the listing transitions to `sold`

#### Scenario: Concurrent purchase loses gracefully
- **GIVEN** two authenticated buyers POST `/api/carbon-listings/{id}/purchase` for the same approved listing within milliseconds
- **WHEN** both requests reach the controller
- **THEN** the first request's transaction commits (purchase row inserted, listing → sold). The second request's transaction fails on the `UNIQUE(carbon_listing_id)` constraint, and the API returns HTTP 409 Conflict with a message describing that the listing is no longer available

### Requirement: Role Inference Helpers on User Model

The `User` model SHALL expose three boolean helpers used by `/api/me` and any controller / policy that needs to discriminate behavior by role: `isAdmin()`, `isSeller()`, `hasPurchased()`.

`isAdmin()` reads the existing `users.role` column. `isSeller()` and `hasPurchased()` are inferred by existence checks on `carbon_listings` and `carbon_purchases` respectively. None of these introduce a new `users.role` enum value; `seller` and `buyer` remain behavioral roles per the project's role-inference principle.

#### Scenario: A user with no listings is not a seller
- **GIVEN** a user who has never created a carbon listing
- **WHEN** `$user->isSeller()` is called
- **THEN** it returns `false`, and the underlying query is an `EXISTS` subquery on `carbon_listings` with `LIMIT 1`

#### Scenario: A user with at least one listing is a seller (regardless of listing status)
- **GIVEN** a user whose only `carbon_listings` row has `status = 'recalled'`
- **WHEN** `$user->isSeller()` is called
- **THEN** it returns `true` — the inference is "has ever participated as a seller," not "has an active listing"

#### Scenario: A user with at least one purchase has purchased
- **GIVEN** a user with exactly one row in `carbon_purchases`
- **WHEN** `$user->hasPurchased()` is called
- **THEN** it returns `true`

### Requirement: Authorization Policy

The backend SHALL implement `CarbonListingPolicy` and Laravel's `authorize()` flow on every protected controller method. Inline `if ($user->role === 'admin')` checks in controller bodies are NOT permitted.

Policy methods and their rules:

| Method | Allowed iff |
|---|---|
| `view($user, $listing)` | `$listing->status === 'approved'` OR `$user->id === $listing->user_id` OR `$user->isAdmin()` |
| `create($user)` | the user is authenticated (i.e. always true under `auth:sanctum`) |
| `recall($user, $listing)` | `$user->id === $listing->user_id` AND `$listing->status in ['pending', 'approved']` |
| `purchase($user, $listing)` | `$user->id !== $listing->user_id` AND `$listing->status === 'approved'` |
| `approve($user, $listing)` | `$user->isAdmin()` AND `$listing->status === 'pending'` |
| `reject($user, $listing)` | `$user->isAdmin()` AND `$listing->status === 'pending'` |

A user attempting an action denied by the policy MUST receive HTTP 403. (Anonymous users are caught earlier by `auth:sanctum` middleware and receive 401, never 403.)

#### Scenario: Non-admin cannot approve a listing
- **GIVEN** an authenticated non-admin user
- **WHEN** they POST `/api/admin/carbon-listings/{id}/approve` on a pending listing
- **THEN** the response is 403, no state change occurs, and the policy's `approve` method returned false because `$user->isAdmin()` was false

#### Scenario: Seller cannot purchase their own listing
- **WHEN** a user posts `/api/carbon-listings/{id}/purchase` on a listing where they are the owner
- **THEN** the response is 403, no purchase row is created, and the policy's `purchase` method returned false on the owner check

#### Scenario: Buyer can view an approved listing they don't own
- **GIVEN** an approved listing owned by user A
- **WHEN** user B GETs `/api/carbon-listings/{id}`
- **THEN** the response is 200 with the listing payload, because the policy's `view` method allowed the `status === 'approved'` branch

### Requirement: Seller Endpoints

The backend SHALL provide the following routes under `auth:sanctum`:

- `POST /api/carbon-listings` — create a new listing (status auto-set to `pending`, `user_id` auto-set to current user)
- `GET /api/carbon-listings/mine` — list the current user's listings, all statuses, newest first
- `POST /api/carbon-listings/{id}/recall` — transition a pending or approved listing to `recalled`

Validation on create (via FormRequest): `title` required string max 255; `description` required string; `hectares` required decimal > 0; `tonnes_co2e` required decimal > 0; `location` required string max 255; `price_twd` required decimal > 0.

#### Scenario: Valid create returns 201 with the listing
- **WHEN** an authenticated user POSTs `/api/carbon-listings` with valid fields
- **THEN** the response is 201 with `{ listing: { id, title, status: 'pending', ... } }` and the row is persisted

#### Scenario: Invalid create returns 422 with field errors
- **WHEN** an authenticated user POSTs `/api/carbon-listings` with `price_twd = -1`
- **THEN** the response is 422 with `errors.price_twd` describing the rule violated

#### Scenario: Recall transitions an approved listing
- **GIVEN** an approved listing owned by the current user
- **WHEN** the user POSTs `/api/carbon-listings/{id}/recall`
- **THEN** the response is 200, the listing's status is now `recalled`, and the next GET on the same listing reflects that

#### Scenario: Recall on a sold listing is rejected
- **GIVEN** a sold listing
- **WHEN** the owner POSTs `/api/carbon-listings/{id}/recall`
- **THEN** the response is 403 (policy denies because status is not in `[pending, approved]`) — NOT 422 — and no state change occurs

### Requirement: Buyer Endpoints

The backend SHALL provide the following buyer-facing routes under `auth:sanctum`:

- `GET /api/carbon-listings` — paginated browse of `approved` listings only (12 per page, newest first), accepts `?page=N`
- `GET /api/carbon-listings/{id}` — view detail; access controlled by `CarbonListingPolicy::view`
- `POST /api/carbon-listings/{id}/purchase` — buy an approved listing (transitions to `sold`, creates `carbon_purchases` row)
- `GET /api/purchases` — paginated list of the current user's own purchases, newest first, with the related listing eager-loaded

#### Scenario: Browse only returns approved listings
- **GIVEN** a mix of listings with statuses `pending`, `approved`, `rejected`, `recalled`, `sold`
- **WHEN** an authenticated user GETs `/api/carbon-listings`
- **THEN** the response includes only the `approved` listings, ordered by `created_at` descending, paginated 12 per page

#### Scenario: Purchase creates the row and flips status atomically
- **GIVEN** an approved listing not owned by the current user
- **WHEN** the user POSTs `/api/carbon-listings/{id}/purchase`
- **THEN** the response is 201 with the purchase row, the listing's status is `sold`, both writes happened in the same DB transaction, and the next `GET /api/carbon-listings/{id}` reflects `sold`

#### Scenario: Purchase history scope is per-user
- **GIVEN** users A and B have each completed several purchases
- **WHEN** user A GETs `/api/purchases`
- **THEN** the response includes only user A's purchases — user B's are absent regardless of pagination

### Requirement: Admin Review Endpoints

The backend SHALL provide the following admin-only routes under `auth:sanctum` + `CarbonListingPolicy`:

- `GET /api/admin/carbon-listings/pending` — paginated list of all `pending` listings, oldest first (FIFO queue), no other status visible
- `POST /api/admin/carbon-listings/{id}/approve` — transition a pending listing to `approved`, stamp `approved_by` + `approved_at`
- `POST /api/admin/carbon-listings/{id}/reject` — transition a pending listing to `rejected`, optionally accept `{ reason: string }` body which becomes `admin_note`

#### Scenario: Admin approves a pending listing
- **GIVEN** an authenticated admin user and a pending listing
- **WHEN** they POST `/api/admin/carbon-listings/{id}/approve`
- **THEN** the response is 200, `status` is now `approved`, `approved_by` is the admin's id, and `approved_at` is set to the current timestamp

#### Scenario: Admin rejects with a reason
- **WHEN** the admin POSTs `/api/admin/carbon-listings/{id}/reject` with body `{ "reason": "面積數據與地籍資料不符" }`
- **THEN** the response is 200, `status` is now `rejected`, and `admin_note` holds that reason

#### Scenario: Non-admin gets 403 on the queue
- **WHEN** a non-admin authenticated user GETs `/api/admin/carbon-listings/pending`
- **THEN** the response is 403 — they never see the queue's content

### Requirement: Frontend Role-Aware Navigation

The frontend SHALL render the authenticated app shell's primary navigation conditional on the three role flags returned by `/api/me`. The locked nav-item labels (extending the design system's CTA-label lock) are:

| Label | Visible when |
|---|---|
| `市場` | always (any authenticated user) |
| `我的上架` | always (any authenticated user; renders empty state if `!isSeller`) |
| `我的購買` | always (any authenticated user; renders empty state if `!hasPurchased`) |
| `後台審核` | `user.isAdmin === true` ONLY |

The header MUST live in `components/AppHeader.tsx`, distinct from the marketing `<StickyHeader>`. The marketing header never renders on `(protected)` routes.

#### Scenario: Admin sees all four nav items
- **GIVEN** an authenticated admin user
- **WHEN** the app shell renders on any `(protected)` route
- **THEN** the header shows 市場, 我的上架, 我的購買, 後台審核 in that order

#### Scenario: Non-admin sees three nav items, never the admin link
- **GIVEN** an authenticated non-admin user
- **WHEN** the app shell renders
- **THEN** the header shows 市場, 我的上架, 我的購買 — 後台審核 is absent from the DOM, not merely hidden by CSS

### Requirement: Frontend Surfaces for Seller / Market / Admin

The frontend SHALL provide the following pages under `(protected)`, each using the `<Button>` + `<Field>` primitives and Plus Jakarta Sans typography from the design system:

- `(protected)/seller/listings/new/page.tsx` — create-listing form
- `(protected)/seller/listings/page.tsx` — my listings, grouped by status with status badges
- `(protected)/seller/listings/[id]/page.tsx` — detail with conditional 撤回 (recall) button
- `(protected)/market/page.tsx` — paginated browse using the editorial card layout
- `(protected)/market/[id]/page.tsx` — detail with conditional 購買 (purchase) button (hidden if owner)
- `(protected)/purchases/page.tsx` — my purchase history
- `(protected)/admin/layout.tsx` — server-side admin guard (redirects to `/me` if not admin)
- `(protected)/admin/review/page.tsx` — pending queue with inline 核准 (approve) / 退件 (reject) actions

The new locked CTA labels (extending design system): `送出審核` (submit listing for review), `購買` (purchase), `撤回` (recall), `核准` (approve), `退件` (reject).

#### Scenario: Non-admin visiting /admin/review is redirected
- **GIVEN** an authenticated non-admin user
- **WHEN** the user navigates directly to `/admin/review`
- **THEN** the `admin/layout.tsx` server component detects `!user.isAdmin`, calls `redirect('/me')`, and the review page never renders

#### Scenario: Empty seller listings shows EmptyState + CTA
- **GIVEN** an authenticated user with no listings
- **WHEN** they visit `/seller/listings`
- **THEN** the page renders the `<EmptyState>` primitive with title 「還沒有上架的碳匯」, body explaining what a listing is, and a primary CTA labeled `送出審核` linking to `/seller/listings/new`

#### Scenario: Owner viewing their own listing in the market sees no buy button
- **GIVEN** an authenticated user A and their own approved listing
- **WHEN** user A navigates to `/market/[id]`
- **THEN** the detail page renders the listing but the `購買` button is absent — replaced by a small note 「這是您自己的上架」
