# Carbon-ESG Frontend

Next.js 16 + React 19 + Tailwind v4 + axios。Sanctum SPA 模式對接 [backend](../backend)。

## First-time setup(每台機器只跑一次)

```bash
# 1. Node 版本對齊 .nvmrc
nvm use                       # 期望 v20.19.0(或同 major)

# 2. pnpm 11 安全 gate
#    只想 dev mode 改東西可以跳過;production build 才會用到 sharp native binary
cd frontend
pnpm approve-builds
#   - 用 [space] 勾選 sharp + unrs-resolver、Enter 確認
#   - 對 (y/N) 打 "y"(大寫 N 是 default,直接 Enter 等於拒絕,踩過一次)
```

## 每次開發(三個 terminal)

```bash
# Terminal 1:docker stack(mysql / redis / mailpit)— 從 repo root
docker compose up -d --wait

# Terminal 2:backend
cd backend && php artisan serve     # http://localhost:8000

# Terminal 3:frontend(本資料夾)
cd frontend
pnpm install                         # 第一次 / pull 後跑;pnpm 11 之後通常自動 reconcile
pnpm dev                              # http://localhost:3000
```

打開 `http://localhost:3000` 應看到「**Hello, Carbon-ESG · Backend API: http://localhost:8000**」。

## 環境變數

`cp .env.example .env.local`,依需要改 `.env.local`。
**`.env.local`** 是 Next.js 讀的本機 override,跟 repo root 的 `.env`(docker-compose 用)是**兩份 env,別搞混**。

## 結構

- `app/` — Next.js 16 App Router(RSC 預設)
- `lib/api.ts` — Sanctum-aware axios client(`withCredentials` + `withXSRFToken` + `ensureCsrfCookie()`)
- `public/` — 靜態資源

## 文件

- 設計決策見 repo root [CLAUDE.md](../CLAUDE.md)
- 後端 / 前端 / docker / Node 版本的 spec 見 [`openspec/specs/bootstrap/spec.md`](../openspec/specs/bootstrap/spec.md)(phase-0 archive 後生效)
