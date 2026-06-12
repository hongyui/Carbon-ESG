## Why

phase-0 把 stack 架好了(Laravel 12 + Next.js 16 + docker mysql/redis/mailpit),但前後端只有 `/api/health` 連通,**沒有任何身分系統**。Carbon-ESG 四種角色(admin / seller / buyer / worker)的所有功能 — 碳匯登記、買賣、土地工作回報、後台審核 — 都必須先解掉「**這個請求是誰、有沒有資格做這件事**」這個前提。

技術上的選擇早在 [phase-0 的 `CLAUDE.md`](../../../CLAUDE.md) 鎖死:**Laravel Sanctum SPA 模式 + httpOnly cookie + CSRF token**,前端不碰 token、不存 localStorage、不用 MetaMask。這個方向 phase-1 要把它端到端跑通,phase-2 之後才能假設 `Auth::user()` / `useSession()` 永遠有可信來源。

從進度看,phase-0 archived 之後 active change 槽位空著,**沒做 phase-1 就動 phase-2 等於在沙地上蓋房子**。先做。

## What Changes

**Backend**(`backend/`):

- 開 `bootstrap/app.php` 加 `statefulApi()` middleware — Sanctum SPA 模式的入口,讓 `api/*` 路由能讀 session cookie
- `User` model 加 `HasApiTokens` trait + 對齊 `fillable` / `hidden`
- 新增 `app/Http/Controllers/Auth/AuthController` 含四個 endpoint:
  - `POST /api/register` — 建立 user、自動 login
  - `POST /api/login` — 驗證帳密、建立 session
  - `POST /api/logout` — 銷毀 session + invalidate cookie
  - `GET /api/me` — 回當前登入 user(protected, `auth:sanctum`)
- 新增 `app/Http/Requests/Auth/{RegisterRequest, LoginRequest}` 抽出驗證規則
- 三件套對齊:`config/sanctum.php` 的 `stateful` 陣列、`backend/.env.example` 的 `SANCTUM_STATEFUL_DOMAINS=localhost:3000` + `SESSION_DOMAIN=localhost`、`config/cors.php` 的 `supports_credentials: true`
- Pest tests:每個 endpoint 一個 happy path + 一個失敗案例(401 / 422),共 8 個 test
- `routes/api.php` 把 4 條 auth route 註冊好,`me` 套 `auth:sanctum` middleware

**Frontend**(`frontend/`):

- `lib/api.ts` 加 axios response interceptor — 401 自動重導 `/login`,避免每個 component 自己 try/catch
- `lib/hooks/useSession.ts` — 一個 React context provider + hook,讀 `/api/me` 結果共享給整棵 tree
- `app/(auth)/login/page.tsx` + `app/(auth)/register/page.tsx` — minimal form,call `ensureCsrfCookie()` → 表單 submit
- `app/(protected)/layout.tsx` — 沒 session(API 回 401)就 server-side redirect `/login`
- `app/(protected)/me/page.tsx` — demo route,顯示當前登入 user 資料,確認端到端跑通

**不在此 phase 範圍**(留給後續 change):

- 角色推導(seller / buyer / worker 看有沒有對應 row)→ phase-2 carbon-listings 一起做
- email verification、forgot password、2FA → 各自獨立 phase
- admin 用 Policy / Gate 細控 → phase-3 admin-review
- OAuth / social login → 不在重構藍圖

## Capabilities

### New Capabilities

- `auth`: Sanctum SPA session-based authentication — register / login / logout / 當前 session 查詢,涵蓋 backend endpoint + 前端 session-aware UI 兩端的契約

### Modified Capabilities

- (none) — phase-0 的 `bootstrap` 容量 requirements 都關於目錄結構 / docker stack / Node 版本 / `CLAUDE.md`,**沒有對 backend API endpoint 範圍的規範**,所以 phase-1 新增 `auth` 路由不修改 `bootstrap` 既有 requirement。後續 phase 如果要重訂 backend route 結構,再用 `## MODIFIED Requirements` 改 `bootstrap`

## Impact

**新增**:
- `backend/app/Http/Controllers/Auth/AuthController.php`
- `backend/app/Http/Requests/Auth/RegisterRequest.php`
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/tests/Feature/Auth/` 四個測試檔
- `frontend/lib/hooks/useSession.tsx`
- `frontend/app/(auth)/login/page.tsx`
- `frontend/app/(auth)/register/page.tsx`
- `frontend/app/(protected)/layout.tsx`
- `frontend/app/(protected)/me/page.tsx`

**修改**:
- `backend/bootstrap/app.php`(加 `statefulApi()`)
- `backend/app/Models/User.php`(加 `HasApiTokens`)
- `backend/routes/api.php`(註冊 4 條 auth route)
- `backend/.env.example`(`SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN`)
- `backend/config/sanctum.php`(`stateful` 對齊 `FRONTEND_URL`)
- `frontend/lib/api.ts`(axios response interceptor)
- `frontend/app/layout.tsx`(掛 `<SessionProvider>`)

**Dependencies / 套件**:本 phase 不引入新 PHP / npm 套件 — Sanctum 在 phase-0 已 `composer require`,axios 在 phase-0 已 `pnpm add`。

**Risks**:
- `SESSION_DOMAIN` / `SANCTUM_STATEFUL_DOMAINS` / CORS `supports_credentials` 任何一邊沒對齊,瀏覽器吞 cookie → 401 卻無 error message,這是 SPA Sanctum 最常見的踩坑點,設計階段要寫 debug clinic
- production 部署時 frontend / backend domain 不同會牽涉到第三方 cookie 政策(Safari ITP / Chrome 即將推的 cookie 規範),本 phase 先 scope 在本機 dev,production 留 phase-1.5 或 deploy phase

**Out of scope reminder**: 本 phase **不做** 角色細分(seller / buyer / worker / admin 的權限差異)— 那是 phase-2 carbon-listings 進來時透過「有沒有對應 row」推導的事,本 phase 只負責「這個 user 是誰」。
