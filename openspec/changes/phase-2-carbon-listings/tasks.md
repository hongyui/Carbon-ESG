## 1. Database Schema

- [x] 1.1 Create migration `create_carbon_listings_table.php` with columns per spec: `id`, `user_id` FK (cascade), `title` string, `description` text, `hectares` decimal(8,2), `tonnes_co2e` decimal(10,2), `location` string, `price_twd` decimal(12,2), `status` string (default `pending`), `admin_note` text nullable, `approved_by` FK users.id nullable, `approved_at` timestamp nullable, `timestamps()`
- [x] 1.2 Create migration `create_carbon_purchases_table.php` with `id`, `carbon_listing_id` FK (cascade) **UNIQUE**, `buyer_id` FK users.id, `price_twd` decimal(12,2), `timestamps()`
- [x] 1.3 Run `cd backend && php artisan migrate` against docker mysql; verify both tables present via `php artisan tinker --execute="echo Schema::hasTable('carbon_listings'); echo Schema::hasTable('carbon_purchases');"`
- [x] 1.4 Commit as `feat(backend): add carbon_listings + carbon_purchases migrations`

## 2. Models, Policies, Role Inference, /me Enrichment

- [x] 2.1 Create `app/Models/CarbonListing.php` with `$fillable`, `$casts` (`hectares` / `tonnes_co2e` / `price_twd` → decimal; `approved_at` → datetime), `STATUS_PENDING` / `STATUS_APPROVED` / `STATUS_REJECTED` / `STATUS_RECALLED` / `STATUS_SOLD` class constants
- [x] 2.2 Implement `CarbonListing::transitionTo(string $newStatus, array $extras = []): void` with the allowed-transition map from the spec. On `approved`, stamp `approved_by` from `Auth::id()` and `approved_at = now()`. On `rejected`, store `$extras['admin_note']` if present
- [x] 2.3 Create `app/Exceptions/InvalidStateTransition.php` extending `RuntimeException`
- [x] 2.4 Register a `static::saving()` boot listener on `CarbonListing` that, when `status` is dirty, asserts the original→dirty transition is in the allowed map; throws `InvalidStateTransition` if not. Guards against direct `$listing->status = ...; $listing->save()`
- [x] 2.5 Create `app/Models/CarbonPurchase.php` with `$fillable`, `belongsTo(CarbonListing)`, `belongsTo(User, 'buyer_id')`
- [x] 2.6 Add `User::carbonListings()` (hasMany), `User::purchases()` (hasMany on `buyer_id`)
- [x] 2.7 Add `User::isAdmin(): bool` (reads `role === 'admin'`), `User::isSeller(): bool` (`->carbonListings()->exists()`), `User::hasPurchased(): bool` (`->purchases()->exists()`)
- [x] 2.8 Create `app/Policies/CarbonListingPolicy.php` with `view`, `create`, `recall`, `purchase`, `approve`, `reject` methods per spec
- [x] 2.9 Register the policy in `app/Providers/AppServiceProvider::boot()` via `Gate::policy(CarbonListing::class, CarbonListingPolicy::class)`
- [x] 2.10 Extend `AuthController::me()`: change the response payload from `['user' => $user->only([...])]` to spread role flags `isAdmin` / `isSeller` / `hasPurchased` into the user object
- [x] 2.11 Update `tests/Feature/Auth/MeTest.php`: extend the happy-path assertion to include the three role flag fields
- [x] 2.12 New `tests/Feature/CarbonListings/UserRoleInferenceTest.php`: fixtures for (no rows, has listing, has purchase, both) × assert each helper boolean
- [x] 2.13 New `tests/Feature/CarbonListings/StateMachineTest.php`: walk every allowed transition with `transitionTo()`; assert each disallowed transition throws `InvalidStateTransition`; assert direct `$listing->status = ...; $listing->save()` is also caught by the saving listener
- [x] 2.14 Run `./vendor/bin/pest tests/Feature/CarbonListings tests/Feature/Auth/MeTest.php` — all pass
- [x] 2.15 Commit as `feat(backend): add CarbonListing model with state machine, policy, role helpers, /me role flags`

## 3. Backend Seller Endpoints + Pest

- [x] 3.1 Create `app/Http/Requests/CarbonListings/CreateRequest.php` with rules: `title required|string|max:255`, `description required|string`, `hectares required|numeric|gt:0`, `tonnes_co2e required|numeric|gt:0`, `location required|string|max:255`, `price_twd required|numeric|gt:0`
- [x] 3.2 Create `app/Http/Controllers/CarbonListingController.php` with `store(CreateRequest)`, `mine(Request)`, `show(CarbonListing)`, `recall(CarbonListing)` methods
- [x] 3.3 In `store`: `CarbonListing::create([...$validated, 'user_id' => $request->user()->id])`; return 201 with the listing
- [x] 3.4 In `mine`: `$request->user()->carbonListings()->latest()->get()`; return as `{ listings: [...] }`
- [x] 3.5 In `show`: `$this->authorize('view', $listing)`; return as `{ listing: {...} }`
- [x] 3.6 In `recall`: `$this->authorize('recall', $listing); $listing->transitionTo('recalled'); $listing->save()`; return 200 with the updated listing
- [x] 3.7 Register routes in `routes/api.php` inside the existing `auth:sanctum` group: `POST /carbon-listings`, `GET /carbon-listings/mine`, `GET /carbon-listings/{carbonListing}`, `POST /carbon-listings/{carbonListing}/recall`
- [x] 3.8 New `tests/Feature/CarbonListings/CreateTest.php`: happy 201 + persisted row; 422 on `price_twd = -1`; 422 on missing `title`
- [x] 3.9 New `tests/Feature/CarbonListings/MyListingsTest.php`: returns all statuses for current user only, newest first
- [x] 3.10 New `tests/Feature/CarbonListings/ShowTest.php`: owner sees pending; non-owner sees approved (200); non-owner sees pending (403); admin sees any
- [x] 3.11 New `tests/Feature/CarbonListings/RecallTest.php`: owner recalls pending → 200, status=recalled; owner recalls approved → 200; owner recalls sold → 403; non-owner recalls → 403
- [x] 3.12 Run `./vendor/bin/pest tests/Feature/CarbonListings` — all pass
- [x] 3.13 Commit as `feat(backend): add seller listing endpoints (store/mine/show/recall) with Pest coverage`

## 4. Backend Market + Purchase + Admin Endpoints + Pest

- [ ] 4.1 Add `index` method to `CarbonListingController`: paginate approved listings only, 12 per page, `?page=` query, newest first
- [ ] 4.2 Add `purchase` method to `CarbonListingController`: `$this->authorize('purchase', $listing)`, then `DB::transaction(fn() => ...)` wrapping `$listing = CarbonListing::lockForUpdate()->find($id); transition to sold; create CarbonPurchase`. Catch `QueryException` with code 23000 (unique violation) → return 409 with message about listing no longer available
- [ ] 4.3 Create `app/Http/Controllers/PurchaseController.php` with `index(Request)` returning `$user->purchases()->with('carbonListing')->latest()->paginate(12)`
- [ ] 4.4 Create `app/Http/Controllers/Admin/CarbonListingReviewController.php` with `pending(Request)`, `approve(CarbonListing)`, `reject(RejectRequest, CarbonListing)`
- [ ] 4.5 In `pending`: `CarbonListing::where('status', 'pending')->oldest()->paginate(12)` (FIFO queue)
- [ ] 4.6 In `approve`: `$this->authorize('approve', $listing); $listing->transitionTo('approved'); $listing->save()`; 200 with updated listing
- [ ] 4.7 In `reject`: `$this->authorize('reject', $listing); $listing->transitionTo('rejected', ['admin_note' => $request->validated('reason')]); $listing->save()`; 200
- [ ] 4.8 Create `app/Http/Requests/CarbonListings/RejectRequest.php` with rules: `reason nullable|string|max:1000`
- [ ] 4.9 Register routes: `GET /carbon-listings` (buyer browse, public in auth:sanctum group), `POST /carbon-listings/{id}/purchase`, `GET /purchases`, plus admin group `GET /admin/carbon-listings/pending`, `POST /admin/carbon-listings/{id}/approve`, `POST /admin/carbon-listings/{id}/reject`
- [ ] 4.10 New `tests/Feature/CarbonListings/BrowseTest.php`: returns only approved; pagination meta correct; ordering newest first
- [ ] 4.11 New `tests/Feature/CarbonListings/PurchaseTest.php`: happy 201 + purchase row + status=sold; owner=403; sold=403; non-approved=403; non-existent=404
- [ ] 4.12 New `tests/Feature/CarbonListings/PurchaseRaceTest.php`: simulate concurrent purchase by manually creating a second purchase row in-test then triggering the controller; assert 409
- [ ] 4.13 New `tests/Feature/Admin/CarbonListingReviewTest.php`: non-admin gets 403 on every admin endpoint; admin sees pending queue scoped to pending only; approve stamps `approved_by` + `approved_at`; reject with reason stores `admin_note`
- [ ] 4.14 New `tests/Feature/PurchaseHistoryTest.php`: GET /purchases scoped to current user only
- [ ] 4.15 Run `./vendor/bin/pest tests/Feature` — all tests across phase-0 + phase-1 + phase-2 pass
- [ ] 4.16 Commit as `feat(backend): add market browse + purchase + admin review endpoints with Pest coverage`

## 5. Frontend Session Enrichment + AppHeader + Primitives

- [ ] 5.1 Update `frontend/lib/types/user.ts`: `User` interface gains optional `isAdmin?: boolean; isSeller?: boolean; hasPurchased?: boolean`
- [ ] 5.2 Verify `frontend/lib/session/server.ts` `getSessionFromCookies()` continues to return the enriched payload by extracting `data.user` whole (no manual field whitelist). Add a typed test or rely on TypeScript narrowing
- [ ] 5.3 Create `frontend/lib/api/listings.ts`: typed client with `createListing(input)`, `getMyListings()`, `getMarket(page)`, `getListing(id)`, `recallListing(id)`, `purchase(id)`, `getMyPurchases(page)`, `adminPending(page)`, `adminApprove(id)`, `adminReject(id, reason?)`. Each function returns the typed payload
- [ ] 5.4 Create `frontend/components/AppHeader.tsx` (`'use client'`): logo + role-aware nav items (`市場` / `我的上架` / `我的購買` / `後台審核`) per spec rule, plus user dropdown with name + 登出. Reads role flags from `useSession()`. Use `<Button>` primitive for the 登出 action
- [ ] 5.5 Update `frontend/app/(protected)/layout.tsx`: render `<AppHeader />` at the top of the protected shell, before `{children}`
- [ ] 5.6 Create `frontend/components/ui/EmptyState.tsx` primitive: props `{ icon, title, body, cta? }`. Renders centered with consistent spacing. Use lucide-react for icon prop type
- [ ] 5.7 Update `frontend/DESIGN_SYSTEM.md`: add `<EmptyState>` to the primitives section; add the new CTA-label locks (`送出審核` / `購買` / `撤回` / `核准` / `退件`) to the CTA-label table
- [ ] 5.8 Update `frontend/app/(protected)/me/page.tsx`: replace the "下一步" placeholder section with quick-link buttons to `/seller/listings`, `/market`, `/purchases`, and (admin only) `/admin/review`
- [ ] 5.9 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [ ] 5.10 Commit as `feat(frontend): role-aware AppHeader + session role flags + EmptyState primitive`

## 6. Frontend Seller Surfaces

- [ ] 6.1 Create `frontend/app/(protected)/seller/layout.tsx` as a thin pass-through (`return <>{children}</>`)
- [ ] 6.2 Create `frontend/app/(protected)/seller/listings/new/page.tsx`: `'use client'` form using `<Field>` × 6 (title / description / hectares / tonnes_co2e / location / price_twd). Submit calls `createListing()`. Success → `router.push('/seller/listings')`. Error → red error block per design system
- [ ] 6.3 Create `frontend/app/(protected)/seller/listings/page.tsx`: server component fetching `/api/carbon-listings/mine`. Render in 2-col grid. If empty, render `<EmptyState>` with CTA to `/seller/listings/new`
- [ ] 6.4 Create `frontend/components/StatusBadge.tsx`: takes `status: 'pending'|'approved'|...`, renders a color-coded pill (zinc for pending; emerald for approved; red for rejected; amber for recalled; emerald-dark for sold) with the Chinese label (`審核中` / `已核准` / `已退件` / `已撤回` / `已售出`)
- [ ] 6.5 Create `frontend/app/(protected)/seller/listings/[id]/page.tsx`: detail card showing all listing fields + `<StatusBadge>`. If status is pending or approved, show a `撤回` ghost button that opens a confirm dialog. Click confirm → call `recallListing(id)` → router.refresh()
- [ ] 6.6 Hands-on flow: log in as a fresh user, navigate to /seller/listings, see empty state. Click `送出審核`, fill form, submit. See the new listing in /seller/listings with `審核中` badge. Open detail, click 撤回, confirm, see status flip to `已撤回`
- [ ] 6.7 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [ ] 6.8 Commit as `feat(frontend): add seller listing surfaces (new + my listings + detail + recall)`

## 7. Frontend Market + Purchase + Admin Surfaces

- [ ] 7.1 Create `frontend/app/(protected)/market/page.tsx`: server component fetching paginated approved listings. Render as editorial cards (adapt the landing's Field Cases card pattern: location + meta + body + price). Pagination controls at the bottom
- [ ] 7.2 Create `frontend/app/(protected)/market/[id]/page.tsx`: detail card. If current user is the owner, replace the 購買 button with a small zinc note 「這是您自己的上架」. Otherwise show 購買 (primary button) opening a confirm dialog → calls `purchase(id)` → on success `router.push('/purchases')` and `router.refresh()`
- [ ] 7.3 Create `frontend/app/(protected)/purchases/page.tsx`: server component fetching `/api/purchases` paginated. Render list with listing title, location, price, purchased_at. Empty state → CTA to `/market`
- [ ] 7.4 Create `frontend/app/(protected)/admin/layout.tsx`: server component reading `getSessionFromCookies()`. If `!user.isAdmin`, call `redirect('/me')`. Otherwise `<>{children}</>`
- [ ] 7.5 Create `frontend/app/(protected)/admin/review/page.tsx`: server component fetching `/api/admin/carbon-listings/pending`. Render each row with listing summary, 核准 primary button, 退件 ghost button. Reject opens a small inline form with optional reason `<Field>` + 退件 confirm. Both actions call the API + `router.refresh()`
- [ ] 7.6 Add error handling on purchase race: if API returns 409, show a toast / inline error 「這筆碳匯已經被別人買走了」 instead of redirecting
- [ ] 7.7 Hands-on flow: register a buyer user, browse /market, click a listing, click 購買, confirm. Verify listing disappears from market and appears in /purchases. Switch to an admin user (set role=admin via `php artisan tinker`), visit /admin/review, approve / reject some pending listings
- [ ] 7.8 Hands-on flow: as a non-admin, navigate directly to /admin/review URL. Verify server-side redirect to /me with no admin content flashing
- [ ] 7.9 Run `cd frontend && ./node_modules/.bin/tsc --noEmit` — no errors
- [ ] 7.10 Commit as `feat(frontend): add market browse + purchase history + admin review surfaces`

## 8. Verification

- [ ] 8.1 `cd backend && ./vendor/bin/pest --colors=never` — all phase-0 + phase-1 + phase-2 tests pass
- [ ] 8.2 `cd frontend && ./node_modules/.bin/tsc --noEmit` exits 0
- [ ] 8.3 `cd frontend && pnpm install --frozen-lockfile` exits 0
- [ ] 8.4 `openspec validate --all` shows `spec/auth`, `spec/bootstrap`, `spec/carbon-listings`, `change/phase-2-carbon-listings` all passing (carbon-listings spec lives in the change until archive syncs it)
- [ ] 8.5 End-to-end manual: docker compose up; backend serve; frontend dev. Walk full seller → admin → buyer loop on real UI; verify session role flags update without manual refresh after creating first listing / completing first purchase
- [ ] 8.6 a11y spot-check: tab through the create-listing form, verify focus rings emerald-600/20 visible; verify error messages have `role="alert"`; verify admin review buttons have accessible labels
- [ ] 8.7 If anything broke during 8.1-8.6, commit fixes as `fix(...)` and re-run from 8.1. Otherwise no additional commit
