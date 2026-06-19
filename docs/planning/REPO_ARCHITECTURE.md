# zonaKasir — Arsitektur & Branch Strategy

> **Dokumen ini menjelaskan arsitektur dual-branch:**
> - `main` — MySQL, Docker VPS, development (default)
> - `vercel` — PostgreSQL, Vercel config, deployment testing

---

## Daftar Isi

1. [Branch Strategy](#1-branch-strategy)
2. [Branch `main` — MySQL / VPS / Local Dev](#2-branch-main--mysql--vps--local-dev)
3. [Branch `vercel` — PostgreSQL / Vercel](#3-branch-vercel--postgresql--vercel)
4. [Perbedaan File Antar Branch](#4-perbedaan-file-antar-branch)
5. [FAQ](#5-faq)

---

## 1. Branch Strategy

```
main (default)
├── Database:    MySQL 8.0
├── Hosting:     VPS (Docker) / Local (Docker/Lando)
├── PHP:         8.2 (Docker) / 8.4 (Lando)
├── Build:       Vite + Tailwind (full Filament preset)
└── Status:      ✅ PRODUCTION-READY

vercel
├── Database:    PostgreSQL 15
├── Hosting:     Vercel (serverless) / Local (Docker)
├── PHP:         8.4
├── Build:       Vite + Tailwind (no Filament preset — fallback)
├── Vercel:      vercel.json + api/index.php
└── Status:      🟡 DEPLOYMENT TESTING (250MB limit issue)
```

### Aturan

| Jangan | Lakukan |
|--------|---------|
| ❌ Push perubahan Vercel/PostgreSQL ke `main` | ✅ Semua perubahan Vercel di branch `vercel` |
| ❌ Merge `vercel` ke `main` | ✅ Branch terpisah selamanya |
| ❌ Hapus branch `vercel` | ✅ Keep for reference |

---

## 2. Branch `main` — MySQL / VPS / Local Dev

### Stack

```
PHP 8.2 (Docker) / 8.4 (Lando)
MySQL 8.0
Redis
Mailpit
```

### Cara Run (Local)

```bash
# 1. Start Docker
docker compose up -d

# 2. Install dependencies
docker compose exec app composer install
docker compose exec app npm install

# 3. Database
docker compose exec app php artisan migrate --path=database/migrations/tenant --seed
docker compose exec app php artisan key:generate

# 4. Build frontend
docker compose exec app npm run dev

# 5. Access
# App:  http://localhost:80
# DB:   localhost:3307 (user: root, pass: secret)
```

### Atau pakai Lando

```bash
lando start
lando composer install
lando npm install
lando artisan migrate --path=database/migrations/tenant --seed
```

### File Penting di `main`

| File | Fungsi |
|------|--------|
| `docker-compose.yml` | MySQL + Redis + Mailpit |
| `Dockerfile` | PHP 8.2, pdo_mysql |
| `.lando.yml` | PHP 8.2, MySQL |
| `config/database.php` | Default `mysql` |
| `tailwind.config.js` | Full Filament preset (`import preset from './vendor/...'`) |
| `resources/css/filament/tenant/theme.css` | `@import vendor/filament/...` + `@apply` |

### Deploy ke VPS (Staging)

Push ke `main` → auto-deploy via GitHub Actions ke VPS.

---

## 3. Branch `vercel` — PostgreSQL / Vercel

### Stack

```
PHP 8.4
PostgreSQL 15
Redis
Mailpit
```

### Perbedaan dari `main`

| Aspek | `main` | `vercel` |
|-------|--------|----------|
| **Database** | MySQL 8.0 | PostgreSQL 15 |
| **PHP version** | 8.2 (Docker) | 8.4 (Docker + Vercel) |
| **DB driver** | `pdo_mysql` | `pdo_pgsql` + `pdo_mysql` |
| **Guard** | MySQL-only (`AppServiceProvider`) | Removed (supports both) |
| **Locking** | `GET_LOCK()` | `pg_advisory_lock()` + MySQL fallback |
| **Filament preset** | Full import | Fallback (no vendor) |
| **Vendor import CSS** | `@import vendor/filament/...` | commented out |
| **Tailwind apply** | `@apply bg-white ...` | Plain CSS equivalent |
| **Docker DB** | `mysql` service | `postgres` service |
| **Lando** | PHP 8.2 + MySQL | PHP 8.4 + PostgreSQL |
| **CI/CD** | MySQL container | PostgreSQL container |
| **Vercel** | Tidak ada | `vercel.json` + `api/index.php` |

### Files Khusus `vercel`

| File | Fungsi |
|------|--------|
| `vercel.json` | Vercel config: PHP runtime, routes, build |
| `api/index.php` | Vercel entry point (Laravel bootstrap + `/tmp` storage) |
| `.vercelignore` | Exclude vendor/node_modules from upload |

### Files Termodifikasi di `vercel`

| File | Perubahan |
|------|-----------|
| `config/database.php` | Default `pgsql`, testing pgsql, dynamic activity_log |
| `app/Providers/AppServiceProvider.php` | MySQL guard **dihapus** |
| `app/Tenant.php` | Dynamic driver based on default connection |
| `app/Services/Tenants/LedgerService.php` | `GET_LOCK` diganti `acquireLock()` dual-driver |
| `app/Filament/Admin/Pages/PaymentSubscriptions.php` | `DATE_FORMAT`→`TO_CHAR`, `DATEDIFF`→`EXTRACT` |
| `app/Http/Resources/ProductCollection.php` | `$this->category?->id` (nullsafe) |
| `database/seeders/CategorySeeder.php` | `SET FOREIGN_KEY_CHECKS`→dual-driver |
| `database/seeders/ProductSeeder.php` | Same |
| `tailwind.config.js` | `presets: []` (no Filament preset) |
| `resources/css/filament/tenant/theme.css` | Vendor import di-comment, `@apply`→plain CSS |
| `Dockerfile` | PHP 8.4 + pdo_pgsql + pgsql + postgresql-client |
| `docker-compose.yml` | MySQL→PostgreSQL service |
| `.lando.yml` | PHP 8.4 + PostgreSQL |
| `.env.example` | `DB_CONNECTION=pgsql`, `DB_HOST=postgres` |
| `.env.testing` | `DB_CONNECTION=pgsql` |
| `phpunit.xml` | `DB_TESTING_DRIVER=pgsql` |
| `tests/RefreshDatabaseWithTenant.php` | PG transaction fix (commit before seeders) |

### Cara Run (Local — PostgreSQL)

```bash
# 1. Switch branch
git checkout vercel

# 2. Start Docker
docker compose up -d

# 3. Install dependencies
docker compose exec app composer install
docker compose exec app npm install

# 4. Database
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan key:generate

# 5. Build frontend
docker compose exec app npm run dev
```

### Deploy ke Vercel

**Tidak bisa — Laravel + PHP runtime >250MB limit Vercel.** Landing page bisa. Untuk API Laravel, gunakan:

| Alternatif | Harga |
|------------|-------|
| **VPS (current)** | $10-20/bln ✅ |
| **Railway** | $5-20/bln |
| **Laravel Vapor** | $39/bln |

---

## 4. Perbedaan File Antar Branch

### 90 file berbeda antara `main` dan `vercel`:

#### Config & Environment (6 files)

| File | main | vercel |
|------|------|--------|
| `.env.example` | `DB_CONNECTION=mysql` | `DB_CONNECTION=pgsql` |
| `.env.testing` | `DB_CONNECTION=mysql` | `DB_CONNECTION=pgsql` |
| `phpunit.xml` | `DB_TESTING_DRIVER=mysql` | `DB_TESTING_DRIVER=pgsql` |
| `config/database.php` | Default `mysql` | Default `pgsql` + testing pgsql |
| `.vercelignore` | — | Ada (exclude vendor/node_modules) |
| `vercel.json` | — | Ada (PHP runtime 0.9.0) |

#### App Code (4 files)

| File | main | vercel |
|------|------|--------|
| `app/Providers/AppServiceProvider.php` | MySQL guard | Guard dihapus |
| `app/Tenant.php` | `driver => 'mysql'` hardcode | Dynamic driver |
| `app/Services/Tenants/LedgerService.php` | `GET_LOCK` | `acquireLock()` dual-driver |
| `app/Filament/Admin/Pages/PaymentSubscriptions.php` | `DATE_FORMAT`/`DATEDIFF` | `TO_CHAR`/`EXTRACT` |
| `app/Http/Resources/ProductCollection.php` | `$this->category->id` | `$this->category?->id` |

#### Frontend (3 files)

| File | main | vercel |
|------|------|--------|
| `tailwind.config.js` | `import preset from './vendor/...'` | `presets: []` |
| `resources/css/filament/tenant/theme.css` | `@import vendor/...` + `@apply` | Vendor import di-comment + plain CSS |
| `api/index.php` | — | Ada (Vercel entry point + `/tmp` storage) |

#### Docker & CI (5 files)

| File | main | vercel |
|------|------|--------|
| `Dockerfile` | PHP 8.2, pdo_mysql | PHP 8.4, pdo_mysql + pdo_pgsql |
| `docker-compose.yml` | MySQL service | PostgreSQL service |
| `.lando.yml` | PHP 8.2, MySQL | PHP 8.4, PostgreSQL |
| `.github/workflows/deploy-staging.yml` | pdo_mysql only | pdo_mysql + pdo_pgsql |
| `.github/workflows/pre-release.yml` | MySQL container | PostgreSQL container |

#### Database & Migrations (~65 files)

| Perubahan | main | vercel |
|-----------|------|--------|
| `database/migrations/tenant/*.php` | `->after()` + `->enum()` | `->after()` removed, `->enum()`→string |
| `database/seeders/CategorySeeder.php` | `SET FOREIGN_KEY_CHECKS=0` | Dual-driver (MySQL + PG) |
| `database/seeders/ProductSeeder.php` | Same | Same |

#### Tests (3 files)

| File | main | vercel |
|------|------|--------|
| `tests/RefreshDatabaseWithTenant.php` | Seeders in transaction | PG: commit before seeders |
| `tests/Feature/E2E/PlanE2ETest.php` | `toBe(99000)` | `toEqual(99000)` |
| `tests/Feature/Http/.../SellingControllerTest.php` | `payment_method_id => 1` | Dynamic + `cash_drawer_enabled` reset |

#### Dokumen (1 file)

| File | main | vercel |
|------|------|--------|
| `docs/planning/MYSQL_TO_POSTGRESQL_MIGRATION.md` | — | Ada (dokumentasi migrasi) |

---

## 5. FAQ

### Q: Saya di branch `main`, mau ganti ke `vercel`?
```bash
git checkout vercel
```

### Q: Saya di `vercel`, mau balik ke `main`?
```bash
git checkout main
```

### Q: Saya bikin perubahan di `main`. Gimana dapetnya di `vercel`?
Cherry-pick manual atau merge dari `main`:
```bash
git checkout vercel
git merge main
# resolve conflict kalo ada
```

### Q: Vercel deployment gagal (250MB limit), gimana?
Laravel terlalu besar untuk Vercel serverless (limit 250MB uncompressed). Gunakan alternatif:
- **Railway** — deploy full Laravel + PostgreSQL ($5-20/bln)
- **Laravel Vapor** — serverless optimized ($39/bln)
- **VPS** — existing setup ($10-20/bln)

### Q: Saya mau test PostgreSQL lokal?
```bash
git checkout vercel
docker compose up -d
php artisan migrate:fresh --seed
```

### Q: Saya mau test Vercel deploy?
Branch `vercel` sudah include `vercel.json` + `api/index.php`. Tapi tetap kena 250MB limit.

### Q: `main` kehapus?
Tidak. `main` aman — tidak tersenggol.

---

## 6. Runtime Architecture

### 6.1 Runtime `main` — VPS / Docker / Local

```
Request → Nginx (port 80)
              ↓
         PHP-FPM (8.2)
              ↓
       Laravel App
              ↓
    ┌───────┴───────┐
    ▼               ▼
  MySQL 8.0       Redis
  (port 3306)    (port 6379)
```

**File system:** Read-write (`storage/`, `public/`)  
**Queue:** `php artisan queue:work` (background supervisor)  
**Websockets:** Pusher / Laravel Reverb  
**File upload:** Local `storage/app/public`  
**Cron:** `php artisan schedule:run`  
**Session:** Database / Redis  
**Cache:** Redis / File  

**Docker Compose services:**
```
app     → PHP-FPM + Nginx + Supervisor
mysql   → 8.0
redis   → alpine
mailpit → SMTP debug
```

**Request lifecycle (VPS):**
```
1. Browser → nginx:80
2. nginx → public/index.php → Laravel Kernel
3. Kernel → middleware → route → controller → service → model
4. DB query → MySQL
5. Response → JSON / Blade / Filament
6. Kernel terminate
```

**Supervisor processes (VPS):**
```
nginx
php-fpm
queue:work (laravel-worker)
```

---

### 6.2 Runtime `vercel` — Vercel Serverless

```
Request → Vercel Edge Network
              ↓
         vercel-php@0.9.0
         (Node.js 18 → PHP 8.4)
              ↓
       api/index.php (entry point)
              ↓
    ┌───────┴───────┐
    ▼               ▼
  PostgreSQL      Redis (Upstash)
  (Neon)          (external)
```

**Vercel Function Structure (Lambda):**
```
Lambda (max 250MB uncompressed):
├── PHP binary (~40MB)
│   ├── php, php-fpm
│   └── extensions (pdo_pgsql, gd, mbstring, etc.)
├── Composer vendor (~150MB —no-dev)
│   ├── laravel/framework
│   ├── filament/filament
│   ├── spatie/*
│   └── 161 packages
├── App code (~10MB)
│   ├── app/, config/, resources/, routes/, public/
└── Node build (~10MB)
    └── vite, tailwindcss, etc.
```

**Request lifecycle (Vercel):**
```
1. Browser → Vercel Edge → lambda (cold start ~5-10s)
2. api/index.php bootstrap:
   a. Set VERCEL env flag
   b. Create /tmp/storage/{logs,cache,sessions,views}
   c. Bootstrap Laravel with STORAGE_PATH=/tmp/storage
   d. Handle request via Laravel Kernel
3. Kernel → middleware → route → controller → service → model
4. DB query → PostgreSQL (Neon pooled connection)
5. Response → JSON / HTML
6. Lambda cold → next request starts fresh
```

**File system:** Read-only (`/var/task/`), only `/tmp` is writable  
**Queue:** ❌ Tidak bisa  
**Websockets:** ❌ Tidak bisa  
**File upload:** ❌ Perlu S3/R2  
**Cron:** ❌ Tidak bisa  
**Session:** Harus Redis  
**Cache:** Harus Redis  
**Log:** `/tmp/storage/logs` (hilang setelah cold start)  

**Limitations per Platform:**

| Feature | VPS (main) | Vercel (vercel) |
|---------|------------|-----------------|
| Queue (queue:work) | ✅ | ❌ |
| File storage | ✅ Local | ❌ S3/R2 needed |
| Cron (schedule:run) | ✅ | ❌ |
| Websockets | ✅ Pusher/Reverb | ❌ |
| Cold start | ❌ N/A | ⚠️ 5-10s |
| Function size | Unlimited | ⚠️ 250MB max |
| Log persistence | ✅ Forever | ⚠️ /tmp hilang |
| Session | ✅ DB/Redis | ✅ Redis only |

**Kesimpulan:** Vercel hanya cocok untuk **stateless API**. Queue, storage, cron tetap butuh VPS / Vapor / Railway.

---

## 7. Environment Variables

### 7.1 `main` (VPS / Local)
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zonakasir
DB_USERNAME=root
DB_PASSWORD=
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 7.2 `vercel` (PostgreSQL)
```
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://user:pass@host:5432/db?sslmode=require
DB_HOST=postgres
DB_PORT=5432
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=sync
```

### 7.3 Vercel Dashboard Env (if attempting deploy)
| Key | Value | Notes |
|-----|-------|-------|
| `APP_KEY` | `base64:...` | `php artisan key:generate --show` |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `DB_CONNECTION` | `pgsql` | |
| `DATABASE_URL` | `postgresql://...` | Neon/Supabase |
| `SESSION_DRIVER` | `redis` | |
| `CACHE_DRIVER` | `redis` | |
| `QUEUE_CONNECTION` | `sync` | |
| `REDIS_URL` | `redis://...` | Upstash |
| `COMPOSER_FLAGS` | `--no-dev --optimize-autoloader` | Already set |
| `VERCEL_ANALYZE_BUILD_OUTPUT` | `1` | Already set |

---

## 8. Troubleshooting

### 8.1 Vercel Build — 250MB Limit
```
Error: A Serverless Function has exceeded the unzipped maximum size of 250 MB.
```
**Penyebab:** Laravel (~150MB) + PHP runtime (~90MB) + app (~10MB) = >250MB.  
**Solusi:** Gunakan VPS / Railway / Laravel Vapor.

### 8.2 Vercel Storage Read-Only
```
There is no existing directory at "/var/task/user/storage/logs"
```
**Penyebab:** Vercel filesystem read-only.  
**Fix:** `api/index.php` handle otomatis — create `/tmp/storage/` di bootstrap.

### 8.3 PDO::MYSQL_ATTR_SSL_CA Deprecated
```
Deprecated: Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since PHP 8.5
```
**Penyebab:** PHP 8.4+ deprecation. Cuma warning, aman.

### 8.4 Tailwind Build — Missing Vendor Preset
```
[vite:css] [postcss] Cannot find module './vendor/filament/support/tailwind.config.preset'
```
**Penyebab:** Import dari vendor/ gak ada di Vercel build.  
**Fix:** Branch `vercel` sudah pakai `presets: []`.

### 8.5 Tailwind @apply — Class Not Found
```
The `bg-white` class does not exist
```
**Penyebab:** `@apply` with Filament classes (no preset loaded).  
**Fix:** Branch `vercel` sudah ganti ke plain CSS.

---

## 9. Vercel CLI — Setup & Usage

> **Catatan:** Semua contoh di bawah menggunakan placeholder `<team>`, `<project>`, `<token>`.
> Ganti dengan nilai dari akun Vercel kamu masing-masing.
>
> Contoh riil dari repo ini:
> - Team: `argasokataman-codes-projects`
> - Project: `zona-kasir`
> - Token: (lihat dari https://vercel.com/account/tokens)

### 9.1 Install Vercel CLI

```bash
npm install -g vercel
# or
yarn global add vercel
```

### 9.2 Login (2 cara)

#### Cara A: Login interaktif (browser)
```bash
vercel login
# Buka link yang muncul, login via browser
# Akan terhubung ke AKUN VERCEL KAMU SENDIRI
```

#### Cara B: Login dengan token (CI/CD)
```bash
# Token dari https://vercel.com/account/tokens
vercel login --token=<your-vercel-token>
```

### 9.3 Token Vercel

Generate di https://vercel.com/account/tokens

```bash
# Cek status login
vercel whoami --token=<your-vercel-token>

# Output akan berbeda tergantung akun kamu, contoh:
# argasokataman-code
```

### 9.4 Link Project

```bash
# Dari root repo
vercel link --token=<your-vercel-token> --yes

# Output: ✓ Linked to <your-team>/<your-project>
```

### 9.5 Deploy Branch `vercel` to Production

```bash
# Deployment langsung (upload archive)
vercel deploy --prod --archive=tgz --yes

# Output:
#   Inspect    https://vercel.com/.../xxx
#   Production https://zona-kasir-xxx.vercel.app
#   Building...
```

### 9.6 Deploy via Git (auto-deploy)

Vercel auto-deploy ketika push ke **Production Branch**. Setting di Dashboard:
```
Vercel Dashboard → Settings → Git → Production Branch → pilih branch
```

### 9.7 List Deployments

```bash
vercel list --token=<your-vercel-token>
```

### 9.8 Check Deployment Status

```bash
vercel inspect <deployment-url> --token=<your-vercel-token>
```

### 9.9 View Build Logs

```bash
vercel logs <deployment-url> --token=<your-vercel-token>
```

### 9.10 Environment Variables via CLI

```bash
# Pull env dari Vercel ke .env.local
vercel env pull --token=<your-vercel-token>

# Set env via API (example)
curl -s -X POST "https://api.vercel.com/v10/projects/<project-id>/env" \
  -H "Authorization: Bearer <your-vercel-token>" \
  -H "Content-Type: application/json" \
  -d '{"key":"DB_CONNECTION","value":"pgsql","target":["production","preview"],"type":"encrypted"}'
```

### 9.11 Project API (for automation)

Base URL: `https://api.vercel.com`

```
GET    /v9/projects/<project>          → Get project config
PATCH  /v9/projects/<project>          → Update project config
POST   /v10/projects/<project>/env     → Add env variable
GET    /v10/projects/<project>/env     → List env variables
POST   /v1/projects/<project>/link     → Link git repository
DELETE /v1/projects/<project>/env/<id> → Delete env variable
```

### 9.12 Commands Cheat Sheet

| Tujuan | Perintah |
|--------|----------|
| Login | `vercel login --token=<your-vercel-token>` |
| Cek login | `vercel whoami --token=<your-vercel-token>` |
| Link project | `vercel link --token=<your-vercel-token> --yes` |
| Deploy production | `vercel deploy --token=<your-vercel-token> --prod --archive=tgz --yes` |
| Deploy preview | `vercel deploy --token=<your-vercel-token> --archive=tgz --yes` |
| List deployments | `vercel list --token=<your-vercel-token>` |
| Inspect deployment | `vercel inspect <url> --token=<your-vercel-token>` |
| View logs | `vercel logs <url> --token=<your-vercel-token>` |
| Set env | `vercel env add <key> --token=<your-vercel-token>` |
| Pull env | `vercel env pull --token=<your-vercel-token>` |

### 9.13 ⚠️ Catatan Penting

| Hal | Detail |
|-----|--------|
| **Token bersifat rahasia** | Jangan commit token ke repo. Gunakan environment variable `VERCEL_TOKEN` |
| **Token expire** | Token Vercel tidak expire kecuali di-revoke manual |
| **250MB limit** | Laravel + PHP runtime >250MB. Vercel tidak cocok untuk Laravel monolith |
| **Biaya** | Hobby (free): 250MB function limit. Pro ($20/bln): same 250MB limit |
| **Region** | Build default di `iad1` (US East). Bisa diubah di project settings |

---

**Dokumen Version:** 2.1 — + Vercel CLI Guide
**Last Updated:** 2026-06-19
**Branch:** `main` & `vercel`
