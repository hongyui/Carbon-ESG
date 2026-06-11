# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案總覽

Carbon-ESG 是一個碳權交易整合平台,結合電商流程與以太坊智能合約結算。

- 原始版本(2023 大學專題,原生 PHP + Bootstrap + Web3.js)已凍結保留在 `legacy/`(commit `f052705`),僅供查語意,**不再開發**。
- 整個 repo 正在重構為 Next.js + Laravel 的雙子專案結構,每個重構決策都用 [OpenSpec](https://github.com/Fission-AI/OpenSpec) 紀錄。

四種角色:**管理員 (admin)** 審核 / **賣家 (seller)** 登記碳匯 / **買家 (buyer)** 購買 / **工人 (worker)** 整理土地並回報。`users.role` 只存 `admin` 跟 `general`,「賣家/買家/工人」是看有沒有對應 row 推導出來的**行為角色**,不存在 user 表上。

## 重構決策(鎖死,違反請先開 OpenSpec change)

| 項目 | 決策 |
|---|---|
| 前端 | Next.js 16 (App Router) + TypeScript + Tailwind v4 |
| 後端 | Laravel 12(純 API) — 不選 11 是因為 11.x 全版被 Composer 安全公告擋 |
| 資料庫 | MySQL 8 — schema 重新設計,寫 Eloquent migrations,**不用 sqlite** |
| 認證 | Laravel Sanctum SPA mode(httpOnly cookie + CSRF),**不用** localStorage token |
| Web3 | 後端代發 — `web3p/web3.php`,平台錢包代簽,買家不需要 MetaMask |
| 包管理 | pnpm (frontend) · Composer (backend) |
| 測試 | Pest (backend) · Vitest + Playwright (frontend) |
| Monorepo | `/backend` + `/frontend` 兩個獨立子專案 + `/legacy` 凍結保留 |

## OpenSpec 紀律(強制)

**任何超過 1 個檔案的修改都必須先走 OpenSpec change 流程**,不准直接動 code。

### 目錄角色

- `openspec/specs/<capability>/spec.md` — **已 archive 的真相**。新人讀這裡就知道系統現在怎樣
- `openspec/changes/<change-name>/` — **提案中的變更**。每個 phase / feature / refactor 各一個資料夾,內含 `proposal.md` / `design.md` / `tasks.md` / `specs/<capability>/spec.md`(delta)
- `openspec/changes/archive/` — 已完成的 change 歷史

### Slash command 流程(Claude Code)

```
/opsx:propose  <change-name>   # 開新 change,產生 proposal/design/tasks/delta
/opsx:explore                  # 探索模式 (思考用,不寫 code)
/opsx:apply                    # 依 tasks.md 逐項實作
/opsx:sync                     # 把 delta 寫回 specs/
/opsx:archive                  # 完成後封存,specs/ 永久更新
```

### 規則

- Change 名稱用 kebab-case,**不能以數字開頭**(`phase-0-...` ✓,`0-phase-...` ✗)
- 動工前先 `openspec validate <change-name>` 確保 proposal/spec/tasks 完整
- `tasks.md` 的 task group(`## 1.`、`## 2.`)= commit 邊界。**做完一組就 commit**,不要一次推一大包
- 同時間只開一個 change,archive 之前不開下一個(除非是 parallel feature)
- 違反既有 spec 的設計要在 change 的 delta 寫 `## MODIFIED Requirements`,不能繞過

## 目錄結構

```
Carbon-ESG/
├── CLAUDE.md            # 本檔
├── README.md
├── docker-compose.yml   # mysql + redis + mailhog(Phase 0 task 4.1)
├── .env.example         # docker-compose 用的環境變數
├── .nvmrc               # Node 20.19.0
│
├── backend/             # Laravel 12 (Phase 0 task 2.x 建)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/api.php   # API 路由(routes/web.php 留空)
│   └── tests/           # Pest
│
├── frontend/            # Next.js 16 (Phase 0 task 3.x 建)
│   ├── app/             # App Router
│   ├── lib/api.ts       # Sanctum-aware axios client
│   └── components/
│
├── legacy/              # 原始 PHP 站,凍結 — 不要動
│
└── openspec/
    ├── config.yaml
    ├── specs/           # 5 個 capability (auth/bootstrap/carbon-listings/jobs/web3)
    └── changes/
```

## 命名與風格

- **PHP / Laravel**:Pint (PSR-12)。Controller suffix `Controller`、FormRequest suffix `Request`、Resource suffix `Resource`
- **TypeScript / Next.js**:ESLint + Prettier。React component PascalCase、hook `useXxx`、server action `xxxAction`
- **Schema**:全部 `snake_case`,布林用 `tinyint(1)` **不再用「是/否」字串**,狀態用 enum string (`pending` / `approved` / `rejected` / `recalled` / `sold`)
- **Git commit**:Conventional Commits (`feat(carbon): ...`, `fix(auth): ...`, `chore(...): ...`)
- **Branch**:`feat/<scope>` / `fix/<scope>` / `chore/<scope>`,從 `main` 開,PR 合回 `main`
- **註解**:預設不寫。只在「為什麼這樣做不直觀」時加一行;不要寫「這段在做什麼」(命名應自明)

## 本地開發

```bash
# 1. 啟動依賴
docker compose up -d mysql redis

# 2. Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve              # http://localhost:8000

# 3. Frontend
cd frontend
pnpm install
cp .env.example .env.local
pnpm dev                       # http://localhost:3000
```

驗證:
- `curl http://localhost:8000/api/health` → `{"status":"ok"}`
- 瀏覽器開 `http://localhost:3000` 看到首頁

`SANCTUM_STATEFUL_DOMAINS=localhost:3000` 跟 `backend/config/cors.php` 的 allowed origin 必須對齊,不然 SPA cookie 拿不到。

## 安全紅線(這些觸碰請主動 flag)

1. **私鑰絕不進 git**。`.env` 跟 `.env.example` 必須分開,`.env.example` 只放 placeholder
2. **密碼一律 `bcrypt`**,絕不存明文(舊系統 `login.php:16` 直接 SQL 比對明文,新版禁止)
3. **API 一律走 Sanctum middleware**,管理員端點還要過 Policy(不直接靠 `if $role == 'admin'` 散落各處)
4. **檔案上傳必驗 mimetype + size**:`validate('file|mimes:jpg,png|max:5120')`,不直接拼字串路徑
5. **CSRF / XSS 防護**:不要關 Laravel 預設的中介層;前端 axios 必開 `withCredentials` + `withXSRFToken`
6. **不在 API response 或前端 bundle 暴露任何 `0x[a-fA-F0-9]{64}` 樣式字串**(私鑰格式)

## 重構參考(legacy → 新位置對照)

舊 PHP → 新位置(查語意用,寫完即可放生):

| 舊檔 | 新位置 |
|---|---|
| `legacy/index.php` | `frontend/app/(public)/page.tsx` |
| `legacy/login.php` / `register.php` | `frontend/app/(public)/login` + `backend POST /api/login` |
| `legacy/rcarbon.php` | `frontend/app/(buyer)/carbon` + `backend GET /api/carbon-listings` |
| `legacy/admin.php` | `frontend/app/(admin)/review` |
| `legacy/state.php` | `frontend/app/(auth)/status` |
| `legacy/registJob.php` | `frontend/app/(worker)/jobs/apply` |
| `legacy/jobrecall.php` | `frontend/app/(worker)/jobs/report` |
| `legacy/pdo.php` | **廢棄**,改用 Eloquent |
| `legacy/js/contract.js` / `js/utils/web3.js` | `backend/app/Services/Web3/` |

## 已知 legacy 坑(重構時務必修正)

- `legacy/pdo.php:31` 用全形括弧 `（)`,語法錯誤 — 從沒成功跑過,不要照抄
- `legacy/login.php:16` 直接 SQL 比對明文密碼,**新版必須 bcrypt + timing-safe compare**
- 多處 `is_check` / `isCheck` / `is_job_check` 三種拼法混用 — 統一用 `status` enum
- `$_SESSION['role']` 從未 `isset` 檢查就直接用 — 新版走 middleware
- `images/` 圖片上傳直接拼字串路徑、沒驗 mimetype — 新版 Laravel validation + S3-compatible storage
- 沒有任何 CSRF / XSS 防護 — Sanctum + Next.js 預設處理掉
