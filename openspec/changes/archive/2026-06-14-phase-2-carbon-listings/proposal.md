## Why

[Phase-1](../archive/2026-06-14-phase-1-auth-sanctum/proposal.md) gave Carbon-ESG a working auth contract: register / login / logout / `me`. Users can identify themselves but they cannot **do anything yet**. The platform's reason for existing is to put carbon credits up for sale, let admins verify them, and let buyers acquire them. Without that loop, every other future phase (Web3 settlement, worker job applications, admin policy, payouts) has no domain object to attach to.

phase-2 ships the **commercial core**: the `CarbonListing` resource, the `CarbonPurchase` audit record, the seller → admin-review → approved → buyer-purchase state machine, and the role inference (`isSeller` / `hasPurchased` / `isAdmin`) that the frontend uses to render different navigation per identity. Web3 on-chain settlement is intentionally deferred to phase-3 so this phase can land as a complete, hands-on-testable DB-only flow without depending on a working contract deployment.

This is also the first phase that exercises the [design system](../../../frontend/DESIGN_SYSTEM.md) on multiple new surfaces (seller / market / admin), validating that the `<Button>` / `<Field>` primitives + locked CTA labels actually scale across the app.

## What Changes

**Backend** (`backend/`):

- New `carbon_listings` migration + `CarbonListing` Eloquent model with status enum (`pending` / `approved` / `rejected` / `recalled` / `sold`) and a `transitionTo()` state-machine method
- New `carbon_purchases` migration + `CarbonPurchase` Eloquent model with a 1:1 relationship to `CarbonListing` (each listing can only be sold once); separated from the listing row so the audit trail survives future Web3 tx_hash / wallet_address additions in phase-3
- `User` model gains `isSeller()`, `hasPurchased()`, `isAdmin()` helpers (role inference from row existence, NOT a new enum value)
- `CarbonListingPolicy` covering `view` / `recall` / `purchase` / `approve` / `reject` with explicit owner / admin / buyer rules
- New endpoints (all under `auth:sanctum`):
  - `POST /api/carbon-listings` — create (any user; auto status=pending)
  - `GET /api/carbon-listings/mine` — seller's own listings (any status)
  - `GET /api/carbon-listings` — buyer browse (approved only, paginated)
  - `GET /api/carbon-listings/{id}` — detail (owner sees own at any status; others only approved)
  - `POST /api/carbon-listings/{id}/recall` — seller withdraws (pending or approved → recalled)
  - `POST /api/carbon-listings/{id}/purchase` — buyer buys (approved + not own → sold)
  - `POST /api/admin/carbon-listings/{id}/approve` — admin policy gate
  - `POST /api/admin/carbon-listings/{id}/reject` — admin policy gate, with optional `reason`
  - `GET /api/admin/carbon-listings/pending` — admin queue
  - `GET /api/purchases` — buyer's own purchase history
- `GET /api/me` response **extended** with role flags `{ isAdmin, isSeller, hasPurchased }` so the frontend can render role-aware nav without N+1 lookups
- Pest tests covering each endpoint × happy path + at least one authorization / state failure case

**Frontend** (`frontend/`):

- `lib/types/user.ts` — `User` gains optional `isAdmin` / `isSeller` / `hasPurchased` boolean flags
- `lib/session/SessionProvider.tsx` + `getSessionFromCookies()` — propagate the extended user shape (no contract changes for code that doesn't care about roles)
- New route group `(protected)/seller/`:
  - `listings/new/page.tsx` — create form using `<Field>` + `<Button>`, posts to `POST /api/carbon-listings`
  - `listings/page.tsx` — my listings table-ish grid with status badges
  - `listings/[id]/page.tsx` — detail + recall button (conditional on status)
- New route group `(protected)/market/`:
  - `page.tsx` — paginated browse of approved listings using the editorial card layout from the landing page Field Cases section
  - `[id]/page.tsx` — detail + buy button
- New route `(protected)/purchases/page.tsx` — buyer's purchase history
- New route group `(protected)/admin/`:
  - `layout.tsx` — server-side admin gate (`redirect('/me')` if `!user.isAdmin`)
  - `review/page.tsx` — pending queue with approve / reject inline actions
- New header component for authenticated app shell (separate from the marketing `<StickyHeader>`):
  - Renders nav items conditional on role flags: 市場 (always) / 我的上架 (any user) / 我的購買 (any user) / 後台審核 (admin only)
- `app/(protected)/me/page.tsx` updated with quick links to the four new surfaces

**Out of scope** (留給後續 phase):

- **Web3 settlement** — `POST /purchase` writes only to `carbon_purchases`. No on-chain mint / transfer. Phase-3 `phase-3-web3-settlement` adds `tx_hash`, `wallet_address`, and the actual contract call wrapping the DB write in a `DB::transaction()` + `Bus::dispatchSync(MintCarbonToken::class)`
- **Image upload** for listings — text fields only in phase-2; image storage + S3-compatible bucket comes with phase-4 or a dedicated `phase-2.5-listing-images` if it's blocking
- **Email notifications** — admins / sellers / buyers don't get email on state changes yet. Phase-? adds Mailpit → Postmark / SES wiring
- **Worker job flow** — `workers` is a separate behavioral role with its own listings (土地維護工作). Phase-4 `phase-4-jobs` handles that
- **Search / advanced filter / sort UI** — `GET /api/carbon-listings` accepts paginated query only. Search comes when we have enough rows to justify it
- **Pricing in non-TWD currencies** — phase-2 locks `price_twd` (decimal 12,2); FX handling is a later concern
- **Forgot password / email verification / 2FA / OAuth** — auth scope is locked, see phase-1 non-goals

## Capabilities

### New Capabilities

- `carbon-listings`: the commercial core — listing lifecycle, admin review state machine, buyer purchase flow, role inference, and the corresponding backend endpoints + frontend surfaces

### Modified Capabilities

- `auth`: extends the `GET /api/me` Current-Session Endpoint to include `isAdmin` / `isSeller` / `hasPurchased` boolean flags so the frontend can do role-aware rendering without extra round trips. Existing scenarios for `/api/me` shape (`{ user: { id, name, email } }`) remain valid but the response gains the three flags

## Impact

**New paths** (backend):
- `backend/app/Models/CarbonListing.php`
- `backend/app/Models/CarbonPurchase.php`
- `backend/app/Policies/CarbonListingPolicy.php`
- `backend/app/Http/Controllers/CarbonListingController.php`
- `backend/app/Http/Controllers/Admin/CarbonListingReviewController.php`
- `backend/app/Http/Controllers/PurchaseController.php`
- `backend/app/Http/Requests/CarbonListings/{CreateRequest,RejectRequest}.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_carbon_listings_table.php`
- `backend/database/migrations/YYYY_MM_DD_HHMMSS_create_carbon_purchases_table.php`
- `backend/database/factories/{CarbonListingFactory,CarbonPurchaseFactory}.php`
- `backend/tests/Feature/CarbonListings/` (≥ 4 test files)
- `backend/tests/Feature/Admin/CarbonListingReviewTest.php`
- `backend/tests/Feature/PurchaseTest.php`

**New paths** (frontend):
- `frontend/app/(protected)/seller/listings/{page,new/page,[id]/page}.tsx`
- `frontend/app/(protected)/market/{page,[id]/page}.tsx`
- `frontend/app/(protected)/purchases/page.tsx`
- `frontend/app/(protected)/admin/{layout,review/page}.tsx`
- `frontend/components/AppHeader.tsx` (authenticated app shell nav, distinct from marketing `<StickyHeader>`)
- `frontend/lib/api/listings.ts` (typed client wrapping `/api/carbon-listings/*`)

**Modified paths**:
- `backend/app/Http/Controllers/Auth/AuthController.php` — `me()` returns extended payload
- `backend/app/Models/User.php` — `isSeller()` / `hasPurchased()` / `isAdmin()` helpers
- `backend/routes/api.php` — new resource + admin routes
- `frontend/lib/types/user.ts` — User gains optional role flags
- `frontend/lib/session/server.ts` — propagates extended shape (no contract change for unaware callers)
- `frontend/app/(protected)/me/page.tsx` — quick-link section

**Dependencies / 套件**: no new PHP or npm dependencies. Pagination uses Laravel's built-in paginator. Frontend reuses Tailwind v4 + Plus Jakarta Sans + lucide-react + motion already on package.json.

**Risks**:

- **Role inference performance** — naive `isSeller()` calls `User::hasMany(CarbonListing::class)->exists()` on every request. Mitigation: compute the three flags once in `me()` and cache nothing else; if N+1 emerges later, add an `Auth::user()->loadCount(['carbonListings', 'purchases'])` and switch helpers to read those counts
- **Admin policy bypass** — every admin endpoint MUST go through `CarbonListingPolicy` `before()` hook checking `$user->isAdmin()`. A missed `authorize()` call in a controller would let any authenticated user approve listings. Mitigation: Pest test asserts a non-admin authenticated user gets 403 on every admin endpoint
- **Race condition on `POST /purchase`** — two buyers POST simultaneously, both pass the `status === 'approved'` check, both succeed. Mitigation: wrap the buy in `DB::transaction()` and `lockForUpdate()` on the listing row; `CarbonPurchase.carbon_listing_id` is `UNIQUE` so the second insert fails with `QueryException` and we return 409 Conflict
- **State machine drift** — if a future feature mutates `status` directly without going through `CarbonListing::transitionTo()`, invalid transitions become possible (sold → recalled, etc.). Mitigation: model `boot()` registers a `saving` listener that calls `assertValidTransition($oldStatus, $newStatus)` whenever `status` is dirty

**Out of scope reminder**: phase-2 ships the **DB-only commercial flow**. A buyer who "buys" gets a `carbon_purchases` row and the listing becomes `sold` — they do NOT get an on-chain ERC-1155 token. That capability is phase-3. Marketing the platform as "blockchain carbon credits" before phase-3 lands would be misleading; the landing copy currently calls out `鏈上結算` as a platform feature but the actual contract integration is intentionally still in the design column. This proposal's verification step does NOT include any Web3 verification.
