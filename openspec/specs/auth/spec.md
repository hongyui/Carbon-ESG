# auth Specification

## Purpose

Defines Carbon-ESG's session-based authentication contract. The system uses Laravel Sanctum's **SPA stateful mode** — the Next.js frontend authenticates users via httpOnly session cookies plus an `X-XSRF-TOKEN` header. JavaScript never handles bearer tokens, eliminating an entire class of XSS-stealable-credential failure modes. The capability covers backend endpoints (register / login / logout / me), the CSRF cookie bootstrap flow, and the frontend session hook + protected route guard. Configuration alignment across four touchpoints (`SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `cors.php`, axios client) is itself a spec requirement because the failure modes are silent and severe.

## Requirements

### Requirement: SPA-Mode Session Authentication

The backend SHALL implement Sanctum **SPA stateful mode** so that the Next.js frontend (served from `FRONTEND_URL`) can authenticate users via **httpOnly session cookies plus an `X-XSRF-TOKEN` header**, without ever handling bearer tokens in JavaScript.

#### Scenario: Stateful identification on configured domain
- **GIVEN** `SANCTUM_STATEFUL_DOMAINS` includes the frontend host (e.g. `localhost:3000`)
- **WHEN** the frontend sends an authenticated request from that origin carrying both the Laravel session cookie and a matching `X-XSRF-TOKEN` header
- **THEN** `Auth::user()` resolves to the corresponding user and `auth:sanctum` middleware allows the request through

#### Scenario: Request from non-stateful origin is unauthenticated
- **WHEN** a request carrying the session cookie arrives from an origin **not** listed in `SANCTUM_STATEFUL_DOMAINS`
- **THEN** Sanctum treats it as anonymous, `Auth::user()` is `null`, and `auth:sanctum`-protected endpoints respond 401

#### Scenario: Missing or mismatched XSRF token is rejected
- **WHEN** a stateful request reaches a non-GET endpoint without `X-XSRF-TOKEN` (or with a value that does not match the cookie)
- **THEN** the response is 419 (Page Expired / token mismatch) and no business logic runs

### Requirement: CSRF Cookie Bootstrap

The backend SHALL expose `GET /sanctum/csrf-cookie` (provided by Sanctum, mounted by `statefulApi()`) so the SPA can call it once before its first stateful request to obtain the `XSRF-TOKEN` cookie that axios will mirror into the `X-XSRF-TOKEN` header.

#### Scenario: First-time CSRF cookie fetch
- **WHEN** the SPA calls `ensureCsrfCookie()` for the first time, which performs `GET /sanctum/csrf-cookie`
- **THEN** the response is 204 with `Set-Cookie: XSRF-TOKEN=...` (host-scoped, not httpOnly, so JS / axios can read it) and a Laravel session cookie

### Requirement: User Registration Endpoint

The backend SHALL accept `POST /api/register` with `name`, `email`, `password`, and `password_confirmation`. On success it MUST create the user, log them in (start a session), and return the user resource.

#### Scenario: Valid payload creates user and starts session
- **WHEN** an anonymous client POSTs valid `name`, unused `email`, matching `password`/`password_confirmation` (≥ 8 chars)
- **THEN** the response is 201 with `{ user: { id, name, email } }` (no password field), a session cookie is set, and the next call to `GET /api/me` returns the same user

#### Scenario: Duplicate email is rejected with 422
- **WHEN** the submitted `email` already exists in `users`
- **THEN** the response is 422 with `errors.email` describing the conflict and no session is established

#### Scenario: Weak or mismatched password is rejected with 422
- **WHEN** `password` is shorter than 8 chars, or `password_confirmation` does not match
- **THEN** the response is 422 with `errors.password` describing the rule violated

### Requirement: Login Endpoint

The backend SHALL accept `POST /api/login` with `email` and `password`. On success it MUST start a session for the matching user.

#### Scenario: Correct credentials start a session
- **WHEN** the SPA POSTs the correct `email` + `password` for an existing user
- **THEN** the response is 200 with `{ user: { id, name, email } }`, a session cookie is set, and subsequent `GET /api/me` returns the same user

#### Scenario: Wrong credentials return 401 with generic message
- **WHEN** the `email` does not exist, or the `password` is wrong
- **THEN** the response is 401 with a generic error (no distinction between "user not found" and "wrong password" to avoid user enumeration), and no session is established

#### Scenario: Missing fields return 422
- **WHEN** either `email` or `password` is missing or empty
- **THEN** the response is 422 with `errors` describing the missing fields

### Requirement: Logout Endpoint

The backend SHALL accept `POST /api/logout` (under `auth:sanctum`) and destroy the current session, invalidating the session cookie.

#### Scenario: Authenticated logout destroys session
- **WHEN** an authenticated client POSTs `/api/logout`
- **THEN** the response is 204, the server-side session is invalidated, and the next `GET /api/me` from the same client returns 401

#### Scenario: Unauthenticated logout returns 401
- **WHEN** an anonymous client POSTs `/api/logout` (no valid session cookie)
- **THEN** the response is 401 with no special "silent success" branch, keeping the contract uniform across protected endpoints

### Requirement: Current-Session Endpoint

The backend SHALL expose `GET /api/me` (under `auth:sanctum`) returning the currently authenticated user **plus four boolean role flags** that the frontend uses to render role-aware navigation without additional round trips. The flags are computed per request from `User::isAdmin()`, `User::isSeller()`, `User::hasPurchased()`, and `User::isWorker()` (the fourth one is defined in the `jobs` capability).

The response shape is:

```json
{
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "isAdmin": false,
    "isSeller": true,
    "hasPurchased": false,
    "isWorker": false
  }
}
```

This is a backward-compatible extension of the phase-2 shape. Existing client code that reads only `user.id` / `user.name` / `user.email` continues to work unchanged; clients that read the existing three flags continue to work unchanged; clients that read `isWorker` get the new worker information for free.

#### Scenario: Authenticated request returns user resource with role flags
- **WHEN** an authenticated client GETs `/api/me`
- **THEN** the response is 200 with `{ user: { id, name, email, isAdmin, isSeller, hasPurchased, isWorker } }`. The `isAdmin` flag reflects the `users.role === 'admin'` check; `isSeller` is true iff the user has at least one row in `carbon_listings`; `hasPurchased` is true iff the user has at least one row in `carbon_purchases`; `isWorker` is true iff the user has at least one row in `worker_applications` with `status = 'approved'`. Password and remember token fields remain excluded

#### Scenario: Anonymous request returns 401
- **WHEN** no session cookie is present (or the session has expired)
- **THEN** the response is 401 with an empty body; the frontend axios interceptor relies on this status to trigger a redirect to `/login`

#### Scenario: Role flags reflect current state, not cached state
- **GIVEN** a user who creates their first carbon listing during the session
- **WHEN** the frontend re-fetches `/api/me` after the create succeeds
- **THEN** `user.isSeller` is now `true` (it was `false` on the prior call). The backend re-computes flags on each request; there is no per-session cache that could go stale

#### Scenario: isWorker flips when admin approves the application
- **GIVEN** a user whose `WorkerApplication.status` was `pending` on their last `GET /api/me`, and an admin who just approved the application in a different session
- **WHEN** the user re-fetches `GET /api/me`
- **THEN** `user.isWorker` is now `true`. The flag is computed from a fresh `EXISTS` query on `worker_applications` per request, so no relogin or token refresh is needed

### Requirement: Frontend Session Hook with SSR Hydration

The frontend SHALL provide a `useSession()` hook that returns the currently authenticated user (or `null`). The session value MUST be **seeded server-side via `cookies()`** in the root layout to avoid a flash of unauthenticated UI on first render.

#### Scenario: Server component seeds initial session
- **WHEN** any route is server-rendered
- **THEN** the root `app/layout.tsx` reads cookies and calls `/api/me` server-side, passing the result as `initialUser` into `<SessionProvider>` so the first React render already knows the auth state

#### Scenario: Client-side login mutates session context
- **WHEN** a user submits the login form successfully
- **THEN** `<SessionProvider>` updates its in-memory user value and any subscribed component re-renders with the authenticated state, without a full page reload

#### Scenario: Server and client see the same user
- **WHEN** a server component and a client component on the same page both call `useSession()`
- **THEN** they return the same user object (within one render cycle), preventing hydration mismatch errors

### Requirement: Frontend 401 Auto-Redirect Interceptor

The frontend axios client SHALL register a response interceptor that, **on the client side only**, redirects to `/login` whenever a 401 is observed. Server-side 401 handling is the responsibility of the route's RSC code (`redirect()`).

#### Scenario: Client receives 401 and is redirected
- **WHEN** a client-side axios call to any `/api/*` endpoint receives a 401 response
- **THEN** the interceptor sets `window.location.href = '/login'` before the rejected promise propagates

#### Scenario: Server-side 401 does not trigger window redirect
- **WHEN** a server component's axios call receives 401 (during RSC render)
- **THEN** the interceptor does NOT touch `window` (because `typeof window === 'undefined'`), and the layout/page is responsible for calling Next.js `redirect('/login')`

### Requirement: Protected Route Group with Server-Side Guard

The frontend SHALL provide a `(protected)` route group whose layout checks the session server-side and redirects anonymous users to `/login` before any child page renders.

#### Scenario: Anonymous user requests a protected page
- **WHEN** a request without a valid session arrives at any path under `(protected)/`
- **THEN** the `(protected)/layout.tsx` server component detects the missing user (calling `/api/me` returns 401) and immediately `redirect('/login')`, with no child page render

#### Scenario: Authenticated user reaches /me demo
- **WHEN** an authenticated user navigates to `/me`
- **THEN** the layout passes the user through `<SessionProvider>`, and `app/(protected)/me/page.tsx` renders the user's `name` and `email`, confirming the end-to-end loop

### Requirement: Three-Way Configuration Alignment

The repository's auth-relevant configuration MUST stay aligned across **four touchpoints** so that the Sanctum SPA cookie flow works end-to-end. This requirement is process / verification, not code, but the change introduces failure modes severe enough to spec.

| Touchpoint | Value (dev) | Purpose |
|---|---|---|
| `backend/.env.example` `SANCTUM_STATEFUL_DOMAINS` | `localhost:3000` | Sanctum recognises which origins are stateful |
| `backend/.env.example` `SESSION_DOMAIN` | `localhost` | Laravel `Set-Cookie` Domain attribute the browser will accept |
| `backend/config/cors.php` `paths` | includes both `api/*` and `sanctum/csrf-cookie` | CORS allows the CSRF bootstrap route which is NOT under `/api` |
| `frontend/lib/api.ts` `withCredentials` + `withXSRFToken` | `true` for both | The browser actually sends cookies and axios mirrors XSRF into headers |

#### Scenario: All four touchpoints aligned
- **WHEN** all four settings hold the values above
- **THEN** `GET /sanctum/csrf-cookie` followed by `POST /api/login` followed by `GET /api/me` all succeed, and the session round-trip works end-to-end

#### Scenario: `SESSION_DOMAIN` missing or wrong
- **WHEN** `SESSION_DOMAIN` is empty or set to a domain the browser cannot match
- **THEN** the browser drops the session cookie, `POST /api/login` returns 200 but `GET /api/me` returns 401; this failure mode MUST be documented in the change's debug guidance
