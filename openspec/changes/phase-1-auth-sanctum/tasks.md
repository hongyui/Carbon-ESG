## 1. Backend — Sanctum SPA Wiring

- [x] 1.1 Edit `backend/bootstrap/app.php`: inside the `withMiddleware(function (Middleware $middleware) { ... })` closure, add `$middleware->statefulApi();` so the `api` group runs Sanctum's `EnsureFrontendRequestsAreStateful`
- [x] 1.2 Edit `backend/app/Models/User.php`: add `use Laravel\Sanctum\HasApiTokens;` at the top and add `HasApiTokens` to the `use ...` trait list inside the class
- [x] 1.3 Inspect `backend/config/sanctum.php`: confirm the `stateful` array already reads `env('SANCTUM_STATEFUL_DOMAINS')`. Default config does — no edit needed, just check
- [x] 1.4 Update `backend/.env.example`: add `SESSION_DOMAIN=localhost` immediately after the existing `SESSION_*` lines, add `SESSION_SAME_SITE=lax`, and confirm `SANCTUM_STATEFUL_DOMAINS=localhost:3000` from phase-0 is still present
- [x] 1.5 Update `backend/.env` to match (copy from `.env.example` or hand-edit) so `php artisan serve` reads the new values
- [x] 1.6 Inspect `backend/config/cors.php`: confirm `paths` already contains both `api/*` and `sanctum/csrf-cookie` (phase-0 default); add `sanctum/csrf-cookie` if missing
- [x] 1.7 Smoke test: `curl -i http://localhost:8000/sanctum/csrf-cookie` returns 204 with `Set-Cookie: XSRF-TOKEN=...` and `Set-Cookie: laravel_session=...`
- [x] 1.8 Commit as `feat(backend): activate Sanctum SPA middleware and align session domain`

## 2. Backend — Auth Endpoints + Pest Coverage

- [ ] 2.1 `mkdir -p backend/app/Http/Requests/Auth` and create `RegisterRequest.php` with rules: `name: required|string|max:255`, `email: required|email|unique:users`, `password: required|confirmed|min:8`
- [ ] 2.2 In the same dir create `LoginRequest.php` with rules: `email: required|email`, `password: required|string`
- [ ] 2.3 `mkdir -p backend/app/Http/Controllers/Auth` and create `AuthController.php` with empty methods `register`, `login`, `logout`, `me`
- [ ] 2.4 Implement `register(RegisterRequest $request)`: `User::create([...validated, 'password' => $validated['password']])` (Laravel 11 `hashed` cast auto-bcrypts), `Auth::login($user)`, return 201 with `['user' => $user->only(['id', 'name', 'email'])]`
- [ ] 2.5 Implement `login(LoginRequest $request)`: if `Auth::attempt($request->only('email', 'password'))` succeeds, `$request->session()->regenerate()`, return 200 with user; otherwise throw `ValidationException` mapped to 401 with generic `auth.failed` message
- [ ] 2.6 Implement `logout(Request $request)`: `Auth::guard('web')->logout()`, `$request->session()->invalidate()`, `$request->session()->regenerateToken()`, return `response()->noContent()` (204)
- [ ] 2.7 Implement `me(Request $request)`: return `['user' => $request->user()->only(['id', 'name', 'email'])]`
- [ ] 2.8 Add routes to `backend/routes/api.php` under no middleware: `Route::post('/register', [AuthController::class, 'register'])`, `Route::post('/login', [AuthController::class, 'login'])`. Add under `Route::middleware('auth:sanctum')->group(function() { ... })`: `POST /logout`, `GET /me`
- [ ] 2.9 Create `backend/tests/Feature/Auth/RegisterTest.php` (Pest): happy path returns 201 + sets cookie + `me` returns same user; duplicate email returns 422
- [ ] 2.10 Create `backend/tests/Feature/Auth/LoginTest.php` (Pest): happy path returns 200 + sets cookie; wrong password returns 401 with generic message; missing fields return 422
- [ ] 2.11 Create `backend/tests/Feature/Auth/LogoutTest.php` (Pest): authenticated logout returns 204 + subsequent `me` returns 401; anonymous logout returns 401
- [ ] 2.12 Create `backend/tests/Feature/Auth/MeTest.php` (Pest): authenticated returns user; anonymous returns 401
- [ ] 2.13 Run `./vendor/bin/pest tests/Feature/Auth` and confirm all 8+ tests pass
- [ ] 2.14 Commit as `feat(backend): add Sanctum SPA auth endpoints with Pest coverage`

## 3. Frontend — Session Foundation

- [ ] 3.1 Create `frontend/lib/types/user.ts` exporting `export interface User { id: number; name: string; email: string }`
- [ ] 3.2 Create `frontend/lib/session/server.ts` with `getSessionFromCookies()`: read incoming cookies via `next/headers`, fetch `${API_URL}/api/me` with those cookies forwarded as `Cookie` header, return `User | null` (null on 401)
- [ ] 3.3 Create `frontend/lib/session/SessionProvider.tsx` (Client component): React context that holds `{ user: User | null, setUser: (u: User | null) => void }`, props `{ initialUser, children }`
- [ ] 3.4 Create `frontend/lib/session/useSession.ts`: client hook reading from `SessionProvider` context, throws if used outside provider
- [ ] 3.5 Edit `frontend/lib/api.ts`: append `api.interceptors.response.use(r => r, e => { if (e.response?.status === 401 && typeof window !== 'undefined') window.location.href = '/login'; return Promise.reject(e); })`
- [ ] 3.6 Edit `frontend/app/layout.tsx`: make it an `async` server component, call `getSessionFromCookies()` once, wrap `{children}` in `<SessionProvider initialUser={user}>{children}</SessionProvider>`
- [ ] 3.7 Commit as `feat(frontend): add useSession hook with SSR seed and 401 interceptor`

## 4. Frontend — Auth UI

- [ ] 4.1 Create `frontend/app/(auth)/layout.tsx`: minimal centered container for login / register pages (server component, no auth check — it's the unauthenticated zone)
- [ ] 4.2 Create `frontend/app/(auth)/login/page.tsx` (Client component): form with `email` + `password`, `onSubmit` calls `ensureCsrfCookie()` then `api.post('/api/login', ...)`, on success calls `useSession().setUser(response.data.user)` then `router.push('/me')`
- [ ] 4.3 Create `frontend/app/(auth)/register/page.tsx` (Client component): form with `name` + `email` + `password` + `password_confirmation`, similar submit flow as login but POSTs `/api/register`
- [ ] 4.4 Create `frontend/app/(protected)/layout.tsx` (Server component): call `getSessionFromCookies()`; if null, `import { redirect } from 'next/navigation'; redirect('/login')`. Otherwise render `{children}` (SessionProvider in root already has the user)
- [ ] 4.5 Create `frontend/components/LogoutButton.tsx` (Client component): button that calls `api.post('/api/logout')` then `useSession().setUser(null)` then `router.push('/login')`
- [ ] 4.6 Create `frontend/app/(protected)/me/page.tsx` (Server component): reads session via the layout-provided user (or call hook from within a wrapping client component), displays `<h1>Hi, {user.name}</h1>` + `<p>{user.email}</p>` + `<LogoutButton />`
- [ ] 4.7 Commit as `feat(frontend): add login/register/me pages and protected route guard`

## 5. Verification — End-to-End Hands-On + Test Suites

- [ ] 5.1 Start docker stack: `docker compose up -d --wait` and confirm `docker compose ps` shows mysql + redis healthy
- [ ] 5.2 Backend up: `cd backend && php artisan migrate && php artisan serve` (separate terminal)
- [ ] 5.3 Frontend up: `cd frontend && pnpm dev` (separate terminal)
- [ ] 5.4 In browser, navigate to `http://localhost:3000/me` while logged out — confirm redirect to `/login`
- [ ] 5.5 In browser, register a new user (name / email / password / confirmation) — confirm redirect to `/me` showing the new user's name
- [ ] 5.6 In browser DevTools Network tab, confirm `/sanctum/csrf-cookie` was called once before login/register and `Set-Cookie: XSRF-TOKEN=...` is visible
- [ ] 5.7 In browser, click LogoutButton on `/me` — confirm redirect to `/login` and that hitting `/me` again redirects back to `/login` (session truly cleared)
- [ ] 5.8 Login again with the registered credentials — confirm `/me` works
- [ ] 5.9 Run `cd backend && ./vendor/bin/pest` — all phase-0 + phase-1 tests pass
- [ ] 5.10 Run `cd frontend && pnpm install --frozen-lockfile` — exit 0
- [ ] 5.11 Run `openspec validate --all` — `change/phase-1-auth-sanctum` + `spec/bootstrap` both pass
- [ ] 5.12 If any fix-up needed during verification, commit as `fix(...)` then return to 5.1; otherwise no extra commit
