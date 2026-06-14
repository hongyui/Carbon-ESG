## Context

[Phase-1](../archive/2026-06-14-phase-1-auth-sanctum/proposal.md) shipped Sanctum SPA auth. The platform can now identify a user but it has nothing for that user to interact with beyond `/me` saying their name. The next 80% of "what is Carbon-ESG" lives in this change: putting land up for sale, getting it reviewed, letting buyers buy.

Phase-2 also lands during a transition: the [design system](../../../frontend/DESIGN_SYSTEM.md) was extracted yesterday with only the auth surfaces as a working example. This phase puts the system through its first real-world test — three new role-aware surfaces (seller / market / admin), each of which has multiple sub-pages, all of which need to reuse `<Button>` + `<Field>` + Plus Jakarta Sans + locked CTA labels. If primitives crack here, we'll add them to `components/ui/` and back-port; if labels need extending (e.g. `送出` is currently locked for "generic submit" but seller create might want `送出審核`), we update DESIGN_SYSTEM.md as part of this phase.

The big risk this change carries is that it touches **everything**: data layer (2 new tables), authz (new Policy), domain (state machine), API (10 endpoints), frontend (8 new pages + role-aware nav). It is by far the largest change so far. The mitigation is splitting tasks.md into 8 commit-bounded groups so each group is reviewable in isolation and revertable without dragging the rest down.

## Goals / Non-Goals

**Goals:**

- A seller can put a carbon listing up for sale via `POST /api/carbon-listings`. The listing starts at `pending` and is invisible to buyers until an admin approves it
- An admin can see the pending queue, approve, or reject with an optional reason. The state machine prevents invalid transitions (you cannot un-approve, you cannot approve a recalled listing, etc.)
- An approved listing appears on `(protected)/market`. Any authenticated non-owner can buy it. Buying creates a `carbon_purchases` row and flips the listing to `sold` atomically
- A seller can recall a `pending` or `approved` listing; a `sold` listing is terminal
- The frontend renders role-aware navigation based on the three flags `isAdmin` / `isSeller` / `hasPurchased` returned by an enriched `/api/me`
- 100% of new endpoints have at least one happy-path and one authz / state-failure Pest test
- The state machine is exercised in a dedicated Pest test that walks the full graph once

**Non-Goals:**

- **Web3 on-chain settlement** — `POST /purchase` writes to DB only. Phase-3 wraps the write in a contract call. We carry the design constraint that the eventual Web3 implementation must be a strict superset (additional fields, additional event emission); phase-2 does not require any schema changes when phase-3 arrives
- **Image upload, file storage, S3 wiring** — phase-2 is text only. A listing has `title`, `description`, `location` (string), and a few numeric fields. No `image_url`. The frontend Field Cases section uses picsum placeholders for visual richness; production listings show no image until phase-4
- **Email notifications on state changes** — the platform Mailpit container is up but no `Notification` classes are wired in phase-2. A seller does not get an email when their listing is approved; a buyer does not get a receipt. This will surface as legitimate user feedback but is intentionally deferred
- **Worker / job application flow** — `worker` is a behavioral role for "I want to maintain land + record carbon measurements for pay." That has its own listing type, its own state machine, its own authz. Phase-4
- **Search, filter, sort UI** — `GET /api/carbon-listings` accepts `?page=N` and that is it for query parameters. No `?status=`, `?min_price=`, `?location_like=`. Server-side these would be 5 minutes of code but the UI surface for filter UI is a separate design problem that needs real listings to design against
- **Pricing in currencies other than TWD** — `price_twd` is `decimal(12, 2)`. International expansion is a deliberate non-goal
- **Soft delete** — `carbon_listings` and `carbon_purchases` use hard deletes. A rejected listing stays in the DB forever. A recalled listing stays in the DB forever. Sold listings stay sold. This is intentional for audit reasons; "delete my listing" is not a feature

## Decisions

### 1. Separate `carbon_purchases` table, NOT inline `buyer_id` / `sold_at` columns on `carbon_listings`

Two options were considered:

**Option A** — Add `buyer_id` (nullable FK) and `sold_at` (nullable timestamp) directly to `carbon_listings`. Single table, simpler query, less JOIN cost.

**Option B** — Separate `carbon_purchases` table with `id`, `carbon_listing_id` (UNIQUE), `buyer_id`, `price_twd`, `created_at`.

**Selected: B.** Three reasons:

1. **Audit trail.** Even though phase-2 says "a listing is sold once," phase-3 will add Web3 settlement which means `tx_hash`, `wallet_address`, `gas_used`, and possibly chain-of-custody history. Cramming all that into the listing row gets ugly fast. A purchase row gives those fields a natural home
2. **Buyer queries.** `GET /api/purchases` is "give me everything *I* have ever bought." With option A, that is `SELECT * FROM carbon_listings WHERE buyer_id = ?`, which works but conflates listing data with purchase data. With option B it is `SELECT * FROM carbon_purchases WHERE buyer_id = ? JOIN carbon_listings`, which makes "purchase" the first-class concept
3. **Unique constraint.** The race condition on simultaneous purchase (two buyers, same listing) is trivially resolved by a `UNIQUE INDEX (carbon_listing_id)` on `carbon_purchases`. The second insert fails with `QueryException`, we catch it, return 409. With option A you would need pessimistic locking and a manual check inside the transaction

The cost is one extra table and one extra JOIN on detail queries. Negligible.

### 2. Status state machine enforced by `CarbonListing::transitionTo($new)`

The naive approach is to let controllers do `$listing->status = 'approved'; $listing->save();` and trust nobody to mess up. They will mess up. We have five states and 10+ valid transitions out of 25 possible pairs. Without enforcement, "approve a sold listing" silently succeeds and the audit log lies forever.

The decision: `CarbonListing` has a single `transitionTo(string $newStatus, array $extras = []): void` method. It looks up the allowed transitions from `$oldStatus` in a hard-coded map and throws `InvalidStateTransition` if the new status is not in the allowed set. Controllers call `transitionTo()`, never set `status` directly. A `boot()` listener on `saving` asserts that if `status` is dirty, the previous DB value is in `$ALLOWED[$dirtyOldStatus]` — catching anyone who tries to bypass the helper.

Allowed transition table (also lives in the spec):

```
pending   → approved | rejected | recalled
approved  → sold | recalled
rejected  → (terminal)
recalled  → (terminal)
sold      → (terminal)
```

`transitionTo('approved')` also fills `approved_by` and `approved_at`. `transitionTo('rejected', ['admin_note' => '...'])` fills `admin_note`.

### 3. Role inference from row existence, NOT a new `users.role` enum value

Per [CLAUDE.md](../../../CLAUDE.md): `users.role` only stores `admin` or `general`. `seller`, `buyer`, `worker` are inferred from owning rows in the relevant tables.

The temptation here is to add `seller` and `buyer` as actual enum values — it is faster to query `WHERE role = 'seller'` than `WHERE EXISTS (SELECT 1 FROM carbon_listings WHERE user_id = users.id)`. We don't, because:

1. Role inference is **truth-by-evidence**. If your only listing is recalled, are you still "a seller"? Inference says no (no active rows means no active relationship). An enum says yes (you got flagged once, you stay flagged). The inference semantics match the product semantics
2. A buyer becomes a seller the moment they create a listing. A seller becomes a buyer the moment they buy something. Multi-role is the default. Trying to maintain a `role` enum that reflects multiple roles is `role_csv` or `roles_json` and that is worse than just querying
3. Admin is the exception: admin is a **granted** role, not a behavioral role. It stays as `users.role = 'admin'`

Backend helpers:

```php
public function isAdmin(): bool { return $this->role === 'admin'; }
public function isSeller(): bool { return $this->carbonListings()->exists(); }
public function hasPurchased(): bool { return $this->purchases()->exists(); }
```

`/api/me` calls these three helpers once per request and returns the booleans. Frontend stores them on the session user object and renders nav conditionally.

### 4. Authorization via Laravel Policies + `authorize()` in controllers, not inline checks

Per [CLAUDE.md security red line](../../../CLAUDE.md): "API 一律走 Sanctum middleware,**管理員端點還要過 Policy**(不直接靠 `if $role == 'admin'` 散落各處)."

`CarbonListingPolicy` covers:

| Method | Allowed when |
|---|---|
| `view($user, $listing)` | `$listing->status === 'approved'` OR `$user->id === $listing->user_id` OR `$user->isAdmin()` |
| `create($user)` | any authenticated user |
| `recall($user, $listing)` | `$user->id === $listing->user_id` AND `$listing->status in ['pending', 'approved']` |
| `purchase($user, $listing)` | `$user->id !== $listing->user_id` AND `$listing->status === 'approved'` |
| `approve($user, $listing)` | `$user->isAdmin()` AND `$listing->status === 'pending'` |
| `reject($user, $listing)` | `$user->isAdmin()` AND `$listing->status === 'pending'` |

Controllers call `$this->authorize('approve', $listing)` etc. Tests assert a non-admin gets 403 (not 200 / 401) on every admin endpoint.

### 5. `me()` returns extended payload (MODIFIED `auth` capability)

Adding role flags to the response is a contract change to `auth`. We treat this as a spec-modified change instead of a new endpoint because:

1. The frontend already calls `/me` server-side on every render to seed `SessionProvider`. Adding role flags there means **zero extra round trips** to render role-aware nav
2. A new endpoint (`/api/me/roles`) would either be called separately (one extra request per page load) or batched with `/me` (defeats the point of being a separate endpoint)
3. Old clients that don't read the flags don't care — adding fields to a JSON payload is backward-compatible

The spec delta on `auth` is a `## MODIFIED Requirements` block on the Current-Session Endpoint that updates the scenario response shape from `{ user: { id, name, email } }` to `{ user: { id, name, email, isAdmin, isSeller, hasPurchased } }`.

### 6. Frontend route group structure

Four route groups under `(protected)`:

```
app/(protected)/
├── layout.tsx                  ← session guard (existing)
├── me/page.tsx                 ← existing, gets quick links update
├── seller/
│   ├── layout.tsx              ← thin pass-through; no authz (anyone authenticated can become a seller)
│   └── listings/
│       ├── page.tsx            ← my listings
│       ├── new/page.tsx        ← create form
│       └── [id]/page.tsx       ← detail + recall
├── market/
│   ├── page.tsx                ← browse approved
│   └── [id]/page.tsx           ← detail + buy
├── purchases/page.tsx          ← my purchase history
└── admin/
    ├── layout.tsx              ← redirects to /me if !isAdmin
    └── review/page.tsx         ← pending queue
```

The `(protected)` group guard catches anonymous users. The `admin/layout.tsx` is a second-layer guard that catches non-admins. We do NOT need a `seller/` guard because anyone can become a seller — visiting `/seller/listings` when you have no listings just shows an empty state with a CTA to `seller/listings/new`.

### 7. `<AppHeader>` for the authenticated app shell, NOT reuse `<StickyHeader>`

`<StickyHeader>` (marketing) does scroll-aware transparency over the hero photo. Inside the app shell there is no photo, no scroll-magic. Reusing it would look wrong.

`<AppHeader>` is a thin opaque bar with:
- Logo + wordmark left-aligned
- Nav items center / right: 市場 (always), 我的上架 (any user), 我的購買 (any user), 後台審核 (admin only)
- User menu on far right with name + 登出

It renders inside `(protected)/layout.tsx` so it appears on every authenticated page. The marketing landing keeps `<StickyHeader>`. The two never coexist on the same page.

### 8. `<EmptyState>` primitive added to `components/ui/`

Seller with no listings, buyer with no purchases, admin with empty queue — three pages need an empty state. The pattern is consistent (icon + heading + 1-sentence subtext + optional CTA), so it earns a primitive.

`<EmptyState icon={...} title="..." body="..." cta={...} />` lives in `components/ui/EmptyState.tsx` and gets added to DESIGN_SYSTEM.md's primitives section as part of this phase.

### 9. Pagination on `GET /api/carbon-listings`: Laravel paginator, `?page=N`, 12 per page

Default Laravel `->paginate(12)`. Frontend market page reads `data` + `meta.last_page` and renders Prev / Page-N-of-M / Next. No infinite scroll, no cursor pagination, no jump-to-page. 12 fits a 3x4 grid cleanly on desktop.

This decision is deliberately boring. When listings cross 100+ we will revisit, almost certainly to add server-side filtering before scaling pagination UX.

### 10. Purchase price is **snapshotted**, not referenced

`carbon_purchases.price_twd` stores the price at the moment of purchase. The buyer paid this exact amount; if the listing's nominal `price_twd` changes later (it shouldn't for `sold` listings, but defensive), the purchase record does not. This is the same reason e-commerce systems do not foreign-key to a `products.price` column.

## Risks / Trade-offs

### Big phase, lots of moving parts

phase-2 is the biggest change in the project so far. 2 migrations, 2 models, 1 policy, 10 endpoints, 8 frontend pages, role-aware nav, a state machine, ~50 tests. This is intentional — the listing + review + purchase loop is one coherent product surface and splitting it across phases creates "what is this listing without a way to buy it?" zombie states.

The mitigation is the 8-group commit boundary in tasks.md. Each group lands as a self-contained `feat(*)` commit. If we have to back out the buyer flow because of a Pest discovery, we can revert task group 4 without losing the seller endpoints. The trade-off: more PR-style review work, but production safety.

### State machine surface area

Five states × five states = 25 transitions, of which 8 are valid. Easy to get wrong. The `transitionTo()` + `saving` listener double-guard is verbose for a small win on simple flows, but for state machines that propagate to audit logs and Web3 settlement in phase-3, locking it down now beats fixing it later.

### `isSeller()` / `hasPurchased()` cost on every `/me`

Two `EXISTS` subqueries per request. On a fresh user with zero rows, both queries hit indexes and return immediately (`< 1ms` each). At scale (10k+ rows per user) this is still fast because the queries are `LIMIT 1`. We don't pre-emptively cache because cache invalidation on listing creation / first purchase would be its own headache. If profiling later shows `/me` getting slow, the fix is `Auth::user()->loadCount(['carbonListings', 'purchases'])` on login and stashing the counts in the session.

### Web3 deferred but landing copy promises it

The frontend Field Cases section talks about "鏈上結算" as a platform feature. Phase-2 does not deliver that. We accept the marketing-vs-shipped gap as the cost of shipping the commercial loop before the Web3 plumbing. The landing copy stays factual — we are designing for it — but until phase-3 ships, the platform technically writes to a SQL row not an Ethereum contract. The honest interpretation is "settled in our system" which is true; the misleading interpretation is "settled on Ethereum mainnet" which is not. We do not change the copy in this phase; phase-3 will let us claim it truthfully.

### Admin tooling is intentionally minimal

`(admin)/review` is a list + two buttons. There is no admin dashboard with metrics, no bulk approve, no audit log viewer. This is enough to unblock the commercial loop but it is NOT the eventual admin surface. The admin tooling will get its own phase using shadcn/ui per the DESIGN_SYSTEM.md admin guidance, but not yet — we ship the minimum that makes the system functional.
