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

**Dokumen Version:** 1.0
**Last Updated:** 2026-06-19
**Branch:** `main` & `vercel`
