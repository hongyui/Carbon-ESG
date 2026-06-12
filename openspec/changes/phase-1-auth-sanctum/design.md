## Context

phase-0 後 backend / frontend 各自能 serve、docker stack 起得來,但兩邊只透過匿名 `/api/health` 連通。Sanctum 套件在 phase-0 task 2.2 已 `composer require`、middleware groups 已透過 `php artisan install:api` 預先掛好,**但 `bootstrap/app.php` 的 `statefulApi()` 還沒打開** — 也就是 Sanctum 物件存在但 SPA 模式根本還沒生效。axios 在 phase-0 task 3.3 已配 `withCredentials: true` / `withXSRFToken: true` / `ensureCsrfCookie()` helper,**前端對 Sanctum 的契約寫好了,但後端的對應行為(`sanctum/csrf-cookie` 路由 + session cookie 的 stateful 識別)還沒接通**。

也就是說兩邊都已經對著一個假想的對方寫好了介面,phase-1 要做的事是**把這條中間的線真的拉起來**,並用一條 protected route 的 end-to-end demo 證明它通了。

## Goals / Non-Goals

**Goals:**

- Backend 暴露 `register` / `login` / `logout` / `me` 四條 endpoint,以**httpOnly session cookie + CSRF token** 為認證契約
- Frontend 提供 `useSession()` hook(server side 透過 `cookies()` API 讀、client side 透過 React context 共享),以及一個 minimal `(protected)/me` 路由 demo 端到端流程
- 把 `SESSION_DOMAIN` / `SANCTUM_STATEFUL_DOMAINS` / `config/cors.php` / `withCredentials` 四個必須同時對齊的設定,在 `design.md` 跟 `tasks.md` 寫成 checklist,並在 [Ep-4 部落格](https://shouren-blog.lanya.dev) 補一段 debug clinic
- Pest 覆蓋每個 endpoint 一個 happy path + 一個失敗案例,共 8 個 test;前端先不引入 Playwright(等 phase-2 表單變多再加),由人工驗收 + axios interceptor 行為單測

**Non-Goals:**

- **角色細分**(seller / buyer / worker / admin 的功能差異)— phase-2 carbon-listings 進來時透過「有沒有對應 row」推導,本 phase 只負責「這個 user 是誰」
- **email verification / forgot password / 2FA / OAuth** — 各自獨立 phase,本 phase 連寄信都不做(註冊完即 active)
- **admin 端的 Policy / Gate** — phase-3 admin-review 統一處理,本 phase 連 admin 也只是「一個 user」
- **Cross-domain production deploy** — `SESSION_DOMAIN` 對齊在本機 `localhost` 場景簡單,production 上 Safari ITP / Chrome 第三方 cookie 規範會讓 SPA Sanctum 痛苦,本 phase 只 scope `localhost:3000` ↔ `localhost:8000`,production 對策延到 deploy phase
- **Web3 錢包綁定** — 整個 Web3 流程在 [CLAUDE.md 鎖死決策](../../../CLAUDE.md) 是「後端代發」,user 表跟錢包關係留給後續 phase

## Decisions

### 1. Sanctum SPA(session cookie)而非 API token

Sanctum 有兩種模式 — **SPA stateful**(httpOnly cookie + CSRF)跟 **API token**(`personal_access_tokens` 表 + Bearer header)。我們選 SPA,因為:

- httpOnly cookie 不會被 JS 讀到,**XSS 拿不到 session**(token 模式如果存 localStorage 很容易被拐)
- CSRF token 由瀏覽器 same-origin 保護,axios 已配好自動帶
- 後續所有 protected request 不用前端 component 自己處理 token 過期 / refresh — 瀏覽器 cookie expire 直接斷
- API token 模式更適合「給第三方 client / mobile app 用」,Carbon-ESG 前端就是同主機的 Next.js,SPA 一致性最高

Trade-off:SPA 需要 frontend / backend 在同一個 cookie domain 下,production cross-domain 部署比 token 模式複雜。**這個 trade-off 我們接受,本 phase scope 本機,production 對策延後**。

### 2. `statefulApi()` 寫在 `bootstrap/app.php`,不寫 controller 內

Laravel 11+ 用 `bootstrap/app.php` 的 builder pattern 取代 11 之前的 `Kernel.php`。Sanctum SPA 要靠 `EnsureFrontendRequestsAreStateful` middleware 把 cookie session 轉成 `Auth::user()` — 寫法是:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();
})
```

**全域掛在 `api` middleware group**,不是逐 endpoint 加。理由:auth endpoint 自己就需要 stateful(login 之前要 CSRF cookie),me / 之後 protected 路由也需要,**全 `api/*` 都 stateful 是最簡單一致的**。token guard 留給未來如果真的要做 mobile app,屆時加另一個 prefix(e.g. `api/v1/mobile`)。

### 3. 三件套對齊清單(寫進 tasks.md 變 checklist)

SPA Sanctum 最常見的踩坑就是「**設定四個地方但只改了三個**」。把它們釘死:

| 設定點 | 值 | 為了滿足什麼 |
|---|---|---|
| `backend/.env.example`:`SANCTUM_STATEFUL_DOMAINS` | `localhost:3000` | Sanctum 認哪些 origin 可以走 stateful 路徑 |
| `backend/.env.example`:`SESSION_DOMAIN` | `localhost` | Laravel `Set-Cookie` 的 `Domain=` 屬性,瀏覽器才認 |
| `backend/.env.example`:`SESSION_SAME_SITE` | `lax` | 本機 dev 用 lax 即可,production 要 review |
| `backend/config/sanctum.php`:`stateful` | reads `SANCTUM_STATEFUL_DOMAINS` env(預設值就支援 `:3000` 後綴) | 同上 |
| `backend/config/cors.php`:`paths` | 含 `api/*` 跟 `sanctum/csrf-cookie` | CSRF 那條 endpoint 走的不是 `api/*` prefix |
| `backend/config/cors.php`:`supports_credentials` | `true` | 沒這個瀏覽器不發 cookie,**phase-0 已設好** |
| `backend/config/cors.php`:`allowed_origins` | `[env('FRONTEND_URL')]` | **phase-0 已設好** |
| `frontend/lib/api.ts`:`withCredentials` | `true` | 前端願意帶 cookie,**phase-0 已設好** |
| `frontend/lib/api.ts`:`withXSRFToken` | `true` | axios 自動把 cookie 內 XSRF token 塞進 header,**phase-0 已設好** |

**phase-0 已經做完 frontend + 部分 backend(CORS),phase-1 主要補 `.env.example` 跟 `bootstrap/app.php`**。少改任一個的症狀清單留進 [Ep-4 debug clinic](https://shouren-blog.lanya.dev)。

### 4. 前端 `useSession()` 是 RSC-friendly 的 dual-mode

Next.js 16 App Router 的痛點:server component 沒有 `window`,不能用 React context;client component 沒有 `cookies()`,不能直接讀 session。所以 `useSession` 不是單一 hook,是**雙形態**:

- **Server side**(`app/(protected)/layout.tsx` 等 RSC):用 `import { cookies } from 'next/headers'` 拿 cookie,呼叫 `/api/me`,結果作為 prop 或 React context value 傳下去
- **Client side**(form 提交、登入按鈕):從 `<SessionProvider>` context 讀 cached value,login 成功後 mutate context + 重抓 `/api/me`

決策:**`SessionProvider` 掛在 root `app/layout.tsx`**,server 在 layout 先拿一次 session,以 `initialUser` prop 傳給 `<SessionProvider>` 當 hydration seed。**避免 client 側第一次渲染就閃白屏發 fetch**。

### 5. `ensureCsrfCookie()` 一次性 + 自動失效

phase-0 的 `lib/api.ts` 有個 module-scope `csrfFetched` boolean,只 fetch 一次 `/sanctum/csrf-cookie`。但**頁面 hard reload / 跨 tab 操作**會讓記憶體裡的 boolean reset,所以下一次 `ensureCsrfCookie()` 又會打一次 API,行為自然。

**特別情況**:logout 後 session 結束,但 XSRF cookie 仍在(瀏覽器側我們不刻意清)。下次 login 前如果 `csrfFetched=true`,axios 還是會帶舊 XSRF token — Laravel `VerifyCsrfToken` middleware 對未登入 request 一樣會驗,且新的 session 一旦建立,Laravel 會自動 rotate XSRF。所以**不需要在 logout 後手動 reset `csrfFetched`**,讓下一次 `pnpm dev` reload / 自然 page navigate 處理。

### 6. axios response interceptor 處理 401 全域重導

每個 component 自己 try/catch 401 太 boilerplate。在 `lib/api.ts` 加 interceptor:

```ts
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      if (typeof window !== 'undefined') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  },
);
```

**只在 client side 跑**(`window !== undefined`),server side 由 `app/(protected)/layout.tsx` 自己 redirect。為什麼:server side 拿 401 應該 throw 給 Next.js,讓 layout 用 `redirect()` 是更合 framework 慣例的做法。

Trade-off:有些情境下 user 故意打可能 401 的 endpoint(e.g. 「我這個 token 還有效嗎」)— phase-1 沒這需求,以後加 opt-out 機制(e.g. `api.get('/foo', { skipAuthRedirect: true })`)。

### 7. `register` 自動 login(回 session cookie)

兩種做法:
- **Option A**:register 只建 user,要求前端 redirect 到 login form 再登一次。語意更清楚但 UX 差
- **Option B**:register 建 user + 觸發 `Auth::login()` 同步 session,response 帶 user object 跟 session cookie。一條 API call 完成註冊 + 登入

**選 B**。理由:Carbon-ESG 是「先 sign up → 立刻能用」的 flow,要 user 註冊完馬上再登入是無謂摩擦。實作上 `Auth::login($user)` 在 controller 內一行搞定。

### 8. password 規則:Laravel `Password::defaults()` + `confirmed`

`config/auth.php` 沒設,直接在 `RegisterRequest` 寫:

```php
'password' => ['required', 'confirmed', Password::defaults()],
```

`Password::defaults()` 在 `AppServiceProvider::boot()` 設成:
- min 8 chars
- mixedCase (production)
- numbers
- symbols (production)

`->confirmed()` 要 frontend 帶 `password_confirmation` 欄位。決策:**本機 dev 用 `Password::min(8)` 即可,production / staging 啟用完整規則**,避免 dev 體驗痛苦。

## Risks / Trade-offs

### 三件套對齊脆弱

**症狀**:可能會看到 axios 打 `/api/login` 收到 419(CSRF mismatch)、200 但 `Set-Cookie` 沒被瀏覽器存、login 成功但下一條 `/api/me` 還是 401。每一個都對應到不同那一格沒對齊。Mitigation:tasks.md 的 verification 部分,要 hands-on 跑「login → me 回 200」之前,把上面九格 checklist 全部勾完。Ep-4 部落格寫一段 debug clinic 公開。

### Safari ITP / Chrome 第三方 cookie 將汰除

**情境**:當前端 deploy 到 `app.carbon-esg.com`、後端 deploy 到 `api.carbon-esg.com`,Safari ITP 會把 `api.carbon-esg.com` 視為 third-party cookie 來源,**直接吞 Set-Cookie**。Mitigation:**本 phase 不解這個**,本機 dev 都在 `localhost` (same site)。production 對策:reverse proxy 把 `/api/*` 轉到 backend(domain 統一)、或上 cookie-less token 模式(但這跟 SPA 模式衝突)。延到 deploy phase 處理。

### `Auth::login()` 在 register 內自動建 session vs 純 API 風格

**Trade-off**:Option B(register 自動 login)讓「API 是純 stateless 介面」這個 mental model 破壞了 — register 不只建 row 還改 session。但這就是 Sanctum SPA 模式的本質,我們已經接受 SPA 不是 stateless API,所以這個 trade-off 跟整體一致。

### 全域 401 interceptor 跟「不該重導」場景衝突

**情境**:未來有頁面想「user 可以選擇登入,但沒登也能看內容」(e.g. 公開首頁背景跑個 `/api/me` 拿到名字)。401 自動重導會把這條 anonymous 路徑也彈到 login。Mitigation:phase-1 沒這需求;未來加 opt-out flag(`{ skipAuthRedirect: true }`),不需要 重設計 interceptor。

### 沒有 email verification 就 active

**Trade-off**:可能會被 bot 註冊。本 phase 接受 — phase 後續加 email verification(`MustVerifyEmail` interface + signed URL),那是獨立功能不會打斷 phase-1。
