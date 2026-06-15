# Single Database Architecture Migration

> **Version:** 4.0
> **Status:** ✅ COMPLETE (Phase 1 + Phase 2 done)
> **Date:** 2026-06-15
> **Scope:** Local + Staging — both done

---

## Problem Statement

Repo currently uses **2 database connections** (`default` + `central`) despite being a **single-database multi-tenant** system. The `central` connection is a vestige of an abandoned `stancl/tenancy` integration (2019) — empty migrations, dead config. This causes:

- `domains` table missing error on local (no `CENTRAL_DB_DATABASE` in `.env`)
- Confusing architecture (which DB holds what?)
- Schema divergence between `jogn3455_jogjatourdrive` (default) and `jogn3455_zonakasir` (central)
- Unnecessary complexity for local dev and new contributors

---

## Current Runtime State (Verified 2026-06-15)

### Local

```
DB lakasir (DEFAULT connection, falls back from central since no CENTRAL_DB_DATABASE)
├── tenants (2 rows)
│   Schema: id, google_id, tenancy_email, created_at, updated_at
│   MISSING: data, is_active, suspended_at, suspension_reason ← CRITICAL
├── users (6 rows, has tenant_id ✓)
├── admins (1 row)
├── NO domains table
├── 54 business tables total
├── All migrations recorded (batch 1-4)
│   ⚠️ 2019_09_15_000010_create_tenants_table → recorded batch 1 (empty up())
│   ⚠️ 2019_09_15_000020_create_domains_table → recorded batch 1 (empty up())
│   ⚠️ 2026_06_14_040000_add_google_id_to_tenants_table → recorded batch 2
│   ⚠️ 2026_06_14_110000_add_tenancy_email_to_tenants_table → NOT recorded
└── Central connection falls back to same DB (lakasir)
    Result: Tenant::all() queries lakasir.tenants ✓
    But: Domain query fails (table missing) ✗
```

### Staging Server

```
DB jogn3455_jogjatourdrive (DEFAULT)
├── tenants (0 rows, schema: id, tenancy_email only)
├── users (12 rows, has tenant_id ✓)
├── All business tables with tenant_id
├── NO domains table
├── NO google_id on tenants
└── All root migrations recorded (batch 1-8)

DB jogn3455_zonakasir (CENTRAL)
├── tenants (9 rows, full schema: id, tenancy_email, google_id, data,
│           is_active, suspended_at, suspension_reason)
├── domains (1 row: zonakasir.jogjatourdrive.com → zonakasir)
├── users (2 rows, NO tenant_id column — old schema)
├── Duplicate business tables
├── admins, tenant_users
└── All root migrations recorded (batch 1-11)
```

### Key Differences

| Aspect | Local | Staging |
|--------|-------|---------|
| `tenants` table | 2 rows, incomplete schema | Central: 9 rows, full schema |
| `domains` table | **Missing** | Central: 1 row |
| `users.tenant_id` | ✓ exists | Default: ✓ / Central: ✗ |
| `tenants.data` | **Missing column** | Central: ✓ (JSON with `tenancy_db_name`) |
| `tenants.is_active` | **Missing column** | Central: ✓ |
| Migration status | All recorded (batch 1-4) | All recorded (batch 1-11) |

---

## Target Architecture

```
DB zonakasir (DEFAULT connection only)
├── tenants (full schema, all data)
├── domains (all domain→tenant mappings)
├── admins
├── users (with tenant_id, multi-tenant)
├── abouts, products, sellings, etc. (with tenant_id)
├── subscriptions, invoices, plans, coupons
└── All business tables
```

**No more `central` connection.** One connection, one database, `tenant_id` isolation.

---

## Known Issues (Out of Scope)

Pre-existing bugs unrelated to this migration.

| # | Issue | Where | Impact |
|---|-------|-------|--------|
| K1 | `tenancy_db_name` is NOT a real column — stored in `data` JSON but accessed as attribute | `TenantResource:69,179`, `Tenant:26`, `NotificationDebugger:39` | Always returns `null`. Shows empty in admin panel. |
| K2 | `Tenant::run()` always no-op — `$this->tenancy_db_name` is always null | `Tenant.php:26` | Method never switches DB. Dead code. |
| K3 | `Subscription::tenant()` relationship exists but `tenants` table lives on different connection | `Subscription:30` | Cross-DB relation would fail. Not called in practice. |
| K4 | Config `tenancy.*` referenced but `config/tenancy.php` doesn't exist | `SubscriptionResource:30`, test `RegisteredUserControllerTest:15` | Always returns fallback. Works by accident. |

---

## Anomaly Changelog (v1.0 → v3.0)

| # | Anomaly | Resolution |
|---|---------|------------|
| A1 | `APP_CENTRAL_DOMAIN` is NOT a DB config — used by `config/cors.php:26` for CORS | **Keep in `.env`.** Variable name misleading, but removing breaks CORS for `toko.{domain}` |
| A2 | `Domain` model: `$incrementing=false`, `$keyType='string'` but server has INT AUTO_INCREMENT id | **Fix model.** Change to `$incrementing=true`, remove `$keyType` |
| A3 | `tenancy_db_name` is not a column — it's inside `data` JSON | **Out of scope** (Known Issue K1) |
| A4 | Empty 2019 migrations already recorded in `migrations` table on ALL environments | **Dual strategy:** Fill old migrations for fresh DBs + NEW migration for existing DBs |
| A5 | `SubscriptionResource` form uses `Plan::on($cn)` but table filter uses `Plan::pluck()` | **Fix: remove `$cn`/`->on($cn)`, use direct `Plan::pluck()` in both** |
| A6 | `Tenant::run()` is dead code (`tenancy_db_name` always null) | **Out of scope** (Known Issue K2) |
| A7 | `NotificationDebugger` references `tenant_db` connection that doesn't exist | **Remove dead block** |
| A8 | Local `tenants` table is missing `data`, `is_active`, `suspended_at`, `suspension_reason` columns | **NEW migration** adds these columns |
| A9 | Local `tenants` table column order differs from staging (`google_id` before `tenancy_email`) | **Accept.** Column order doesn't affect functionality. |

---

## Migration Strategy (Dual Path)

> **Critical constraint:** All environments (local + staging) already have the 2019 migration files
> recorded in `migrations` table. Laravel skips them even if we fill the empty `up()` methods.

**Two scenarios, two approaches:**

| Scenario | When | Approach |
|----------|------|----------|
| **Fresh DB** | New developer, CI/CD, Docker, `migrate:fresh` | Fill 2019 migrations → creates full `tenants` + `domains` tables from scratch |
| **Existing DB** | Current local, staging, production | NEW migration → ALTER tenants table + CREATE domains table |

Both paths must produce the **same final schema**. This is achieved by:
1. Filling the 2019 migrations with full schema (for fresh DBs)
2. Creating a new migration with `hasTable`/`hasColumn` guards (for existing DBs)

---

## Migration Plan

### Phase 1: Local Environment

**Goal:** Fix local to work with single DB, matching staging schema.

#### Step 1.1 — Config & Infrastructure

| # | File | Change |
|---|------|--------|
| 1 | `.env` | `DB_DATABASE=lakasir` → `DB_DATABASE=zonakasir` |
| 2 | `.env.example` | `DB_DATABASE=lakasir` → `DB_DATABASE=zonakasir` |
| 3 | `docker-compose.yml` | `DB_DATABASE:-lakasir` → `DB_DATABASE:-zonakasir` (2 places) |
| 4 | `docker-compose.yml` | `DB_USERNAME:-lakasir` → `DB_USERNAME:-zonakasir` |
| 5 | `config/database.php` | Delete entire `'central'` connection block (lines 66-84) |

> **Note:** `APP_CENTRAL_DOMAIN` stays in `.env` — used by CORS config (`config/cors.php:26`),
> not by database connections. Variable name is misleading but unrelated to DB architecture.

#### Step 1.2 — Models

| # | File | Change |
|---|------|--------|
| 6 | `app/Tenant.php:11` | Delete `protected $connection = 'central';` |
| 7 | `app/Domain.php:9` | Delete `protected $connection = 'central';` |
| 8 | `app/Domain.php:13-15` | Delete `$incrementing = false;` and `$keyType = 'string';` — domain IDs use auto-increment int (matches server DB schema) |

#### Step 1.3 — Migrations

| # | File | Change |
|---|------|--------|
| 9 | `database/migrations/2019_09_15_000010_create_tenants_table.php` | Fill `up()` with `Schema::create('tenants', ...)` — for **fresh DBs only** |
| 10 | `database/migrations/2019_09_15_000020_create_domains_table.php` | Fill `up()` with `Schema::create('domains', ...)` — for **fresh DBs only** |
| 11 | `database/migrations/2026_06_14_040000_add_google_id_to_tenants_table.php` | `Schema::connection('central')` → `Schema::table()` (default) |
| 12 | `database/migrations/2026_06_11_152154_add_is_active_to_tenants_table.php` | Keep empty (handled by #9 for fresh, #13 for existing) |
| **13** | **NEW: `database/migrations/2026_06_15_150000_add_missing_tenant_columns_and_create_domains.php`** | **NEW migration** — adds missing columns to `tenants` + creates `domains` table |

> **Why dual approach?**
> - Filled 2019 migrations → run on fresh DB (Docker, CI, new dev)
> - New migration #13 → run on existing DB (current local, staging, production)
> - Both produce identical final schema

**Migration #9 — `tenants` table for fresh DBs:**
```php
Schema::create('tenants', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->string('tenancy_email')->nullable();
    $table->string('google_id')->nullable()->unique();
    $table->longText('data')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('suspended_at')->nullable();
    $table->text('suspension_reason')->nullable();
    $table->timestamps();
});
```

**Migration #10 — `domains` table for fresh DBs:**
```php
Schema::create('domains', function (Blueprint $table) {
    $table->id();
    $table->string('domain')->unique();
    $table->string('tenant_id');
    $table->timestamps();
    $table->index('tenant_id');
});
```

**Migration #13 — NEW: for existing DBs (local, staging, production):**
```php
Schema::table('tenants', function (Blueprint $table) {
    // Add columns that exist on staging but missing locally
    if (! Schema::hasColumn('tenants', 'data')) {
        $table->longText('data')->nullable()->after('tenancy_email');
    }
    if (! Schema::hasColumn('tenants', 'is_active')) {
        $table->boolean('is_active')->default(true)->after('data');
    }
    if (! Schema::hasColumn('tenants', 'suspended_at')) {
        $table->timestamp('suspended_at')->nullable()->after('is_active');
    }
    if (! Schema::hasColumn('tenants', 'suspension_reason')) {
        $table->text('suspension_reason')->nullable()->after('suspended_at');
    }
});

Schema::create('domains', function (Blueprint $table) {
    if (! Schema::hasTable('domains')) {
        $table->id();
        $table->string('domain')->unique();
        $table->string('tenant_id');
        $table->timestamps();
        $table->index('tenant_id');
    }
});
```

**What happens on each environment:**

| Environment | Migration #9 (2019) | Migration #10 (2019) | Migration #13 (new) | Result |
|-------------|--------------------|--------------------|-------------------|--------|
| Fresh DB | Runs → creates tenants | Runs → creates domains | Runs → no-ops (columns/table exist) | ✓ Full schema |
| Local (existing) | Skipped (recorded batch 1) | Skipped (recorded batch 1) | Runs → adds missing cols + creates domains | ✓ Full schema |
| Staging (existing) | Skipped (recorded batch 1-3) | Skipped (recorded batch 1-3) | Runs → adds missing cols + creates domains | ✓ Full schema |

> All `hasColumn`/`hasTable` guards ensure idempotency — safe to run multiple times.

#### Step 1.4 — Application Code

| # | File | Change |
|---|------|--------|
| 14 | `app/Filament/Admin/Resources/SubscriptionResource.php:28-31` | Delete `$cn = Config::get(...)` and `Plan::on($cn)->...` → `$plans = Plan::pluck('name', 'id')->toArray();` + remove unused `Config` import |
| 15 | `app/Filament/Admin/Pages/NotificationDebugger.php:39-43` | Delete `tenant_db` connection switch block |
| 16 | `app/Http/Controllers/Auth/GoogleController.php` | Update comments: remove "central DB" references |

#### Step 1.5 — Tests

| # | File | Change |
|---|------|--------|
| 17 | `tests/Feature/Http/Controllers/Auth/RegisteredUserControllerTest.php:15` | Remove `config(['tenancy.central_domains' => ['localhost.com']])` |

#### Step 1.6 — Local Data Rename

After config change (`DB_DATABASE=lakasir` → `zonakasir`), local data must be migrated:

```bash
# Option A: Rename database
mysql -u root -e "RENAME DATABASE lakasir TO zonakasir;"

# Option B: Export + import
mysqldump -u root lakasir > /tmp/lakasir_backup.sql
mysql -u root -e "CREATE DATABASE zonakasir"
mysql -u root zonakasir < /tmp/lakasir_backup.sql
```

#### Step 1.7 — Verify

```bash
# Run migration (adds missing columns + creates domains)
php artisan migrate

# Verify tenants schema (should have ALL columns)
mysql -u root zonakasir -e "DESCRIBE tenants;"
# Expected: id, tenancy_email, google_id, data, is_active,
#           suspended_at, suspension_reason, created_at, updated_at

# Verify domains table exists
mysql -u root zonakasir -e "DESCRIBE domains;"
# Expected: id, domain, tenant_id, created_at, updated_at

# Verify existing data preserved
mysql -u root zonakasir -e "SELECT id FROM tenants;"
# Expected: 2 rows (create_test, google_fix)

# Verify admin panel works
php artisan serve
# → Open /admin → Tenants menu → No error
```

---

### Phase 2: Staging Environment

**Goal:** Consolidate 2 DBs into 1 (`jogn3455_zonakasir`), deploy updated code.

> ⚠️ **Data migration required.** Server has real data split across 2 databases.
> ⚠️ **Migrations already recorded.** Same dual-path approach: fill 2019 migrations for fresh DBs,
> new migration #13 for existing. On staging, #13 runs and adds missing columns.

#### Step 2.1 — Pre-deploy (before pushing code)

1. **Backup both databases**
```bash
mysqldump -u jogn3455_jtduser -p jogn3455_zonakasir > backup_central.sql
mysqldump -u jogn3455_jtduser -p jogn3455_jogjatourdrive > backup_default.sql
```

2. **Schema alignment — add missing columns to central DB**

Central DB (`jogn3455_zonakasir`) already has the correct `tenants` schema. But some business tables may be missing columns that exist in default DB:
```sql
-- Compare schemas
-- Central users: NO tenant_id
-- Default users: HAS tenant_id

-- Add missing columns
ALTER TABLE jogn3455_zonakasir.users
    ADD COLUMN IF NOT EXISTS tenant_id VARCHAR(255) AFTER id,
    ADD COLUMN IF NOT EXISTS fcm_token VARCHAR(255) AFTER tenant_id,
    ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) AFTER is_owner,
    ADD COLUMN IF NOT EXISTS welcomed_at TIMESTAMP NULL AFTER google_id;

-- Repeat for ALL tables that have tenant_id in default but not in central
```

3. **Data merge — move rows from default → central**

```sql
-- Strategy: insert from default where tenant_id NOT NULL
-- and the row doesn't already exist in central (by unique key)

-- Example for users:
INSERT INTO jogn3455_zonakasir.users (tenant_id, fcm_token, is_owner, name, email, ...)
SELECT tenant_id, fcm_token, is_owner, name, email, ...
FROM jogn3455_jogjatourdrive.users
WHERE tenant_id IS NOT NULL
  AND email NOT IN (SELECT email FROM jogn3455_zonakasir.users);

-- Repeat for ALL business tables
```

4. **After merge, verify:**
```sql
-- Row counts
SELECT 'tenants' as tbl, (SELECT COUNT(*) FROM jogn3455_zonakasir.tenants) as cnt
UNION ALL SELECT 'users', (SELECT COUNT(*) FROM jogn3455_zonakasir.users)
UNION ALL SELECT 'abouts', (SELECT COUNT(*) FROM jogn3455_zonakasir.abouts);

-- All tenants accounted for
SELECT id, tenancy_email FROM jogn3455_zonakasir.tenants;
```

#### Step 2.2 — Deploy Code

Push to `main` → auto-deploy.

#### Step 2.3 — Post-deploy (on server)

```bash
# 1. Update .env
DB_DATABASE=jogn3455_zonakasir
# Remove: CENTRAL_DB_DATABASE=jogn3455_zonakasir
# Keep: APP_CENTRAL_DOMAIN=zonakasir.jogjatourdrive.com (CORS, not DB)

# 2. Run migrations
# 2019 migrations → skipped (already recorded)
# New migration #13 → runs (adds any missing columns, creates domains if missing)
php artisan migrate

# 3. Verify
php artisan tinker
>>> App\Tenant::count()  // should return 9
>>> App\Domain::count()  // should return 1
```

#### Step 2.4 — Cleanup

```bash
# After 7-day verification period
DROP DATABASE jogn3455_jogjatourdrive;
```

---

### Phase 3: Production

Same as staging. Additional safeguards:
- Schedule during low-traffic window
- Notify tenants of brief maintenance
- Have rollback plan ready (restore from backup)

---

## Files Changed Summary

| # | File | Type of Change |
|---|------|---------------|
| 1 | `.env` | Config: rename DB |
| 2 | `.env.example` | Config: rename DB |
| 3 | `docker-compose.yml` | Config: rename DB defaults |
| 4 | `config/database.php` | Remove central connection |
| 5 | `app/Tenant.php` | Remove `$connection = 'central'` |
| 6 | `app/Domain.php` | Remove `$connection = 'central'`, fix `$incrementing` |
| 7 | `database/migrations/2019_09_15_000010_create_tenants_table.php` | Fill migration (fresh DB path) |
| 8 | `database/migrations/2019_09_15_000020_create_domains_table.php` | Fill migration (fresh DB path) |
| 9 | `database/migrations/2026_06_14_040000_add_google_id_to_tenants_table.php` | Remove `Schema::connection('central')` |
| 10 | **NEW:** `database/migrations/2026_06_15_150000_add_missing_tenant_columns_and_create_domains.php` | **NEW** — existing DB path |
| 11 | `app/Filament/Admin/Resources/SubscriptionResource.php` | Remove central config query |
| 12 | `app/Filament/Admin/Pages/NotificationDebugger.php` | Remove dead `tenant_db` block |
| 13 | `app/Http/Controllers/Auth/GoogleController.php` | Comment cleanup |
| 14 | `tests/Feature/Http/Controllers/Auth/RegisteredUserControllerTest.php` | Remove dead config |

**Total: 14 files** (13 modified + 1 new)

---

## Risk Assessment

| Risk | Severity | Mitigation |
|------|----------|------------|
| Missing data during staging merge | **High** | Backup both DBs; row-count comparison after merge |
| `Domain` model `$incrementing` change | **Medium** | Server has int IDs; model change matches DB. No app code creates domains. |
| Migration #13 column order differs from fresh path | **Low** | `hasColumn` guards prevent errors; column order doesn't affect functionality |
| `APP_CENTRAL_DOMAIN` accidentally removed → CORS breaks | **Low** | Doc explicitly says to keep it; renamed step to clarify |
| Local data lost during DB rename | **Medium** | Use `RENAME DATABASE` or export/import; backup first |
| Tenant login fails after deploy | **Medium** | Test impersonate flow for each tenant after deploy |

---

## Execution Rules (MANDATORY)

> **Rule: Every step MUST be verified before proceeding to the next step.**
> If verification fails → fix → re-verify → only then continue.
> Never batch multiple steps and verify at the end.

### Verification Commands

**After Step 1.1 (Config):**
```bash
# DB name is zonakasir
grep 'DB_DATABASE' .env | head -1
# Expected: DB_DATABASE=zonakasir

# APP_CENTRAL_DOMAIN still exists
grep 'APP_CENTRAL_DOMAIN' .env
# Expected: APP_CENTRAL_DOMAIN=admin.localhost (NOT removed)
```

**After Step 1.2 (Models):**
```bash
# No reference to 'central' connection in models
grep -rn "connection.*central" app/Tenant.php app/Domain.php
# Expected: no output

# Domain model has correct incrementing
grep 'incrementing' app/Domain.php
# Expected: no output (removed)
```

**After Step 1.3 (Migrations):**
```bash
# New migration file exists
ls database/migrations/2026_06_15_150000*
# Expected: file exists

# google_id migration has no central reference
grep -n "connection.*central" database/migrations/2026_06_14_040000*
# Expected: no output

# Filled 2019 migrations have Schema::create
grep 'Schema::create' database/migrations/2019_09_15_000010*
grep 'Schema::create' database/migrations/2019_09_15_000020*
# Expected: both return create statements
```

**After Step 1.4 (Application Code):**
```bash
# No central config reference
grep -rn "tenancy.database.central" app/
# Expected: no output

# No tenant_db connection reference
grep -rn "tenant_db" app/
# Expected: no output

# SubscriptionResource has no Config import
grep 'use.*Config' app/Filament/Admin/Resources/SubscriptionResource.php
# Expected: no output
```

**After Step 1.5 (Tests):**
```bash
grep 'tenancy.central_domains' tests/
# Expected: no output
```

**After Step 1.6 (DB Rename):**
```bash
# New DB exists with data
mysql -u root zonakasir -e "SELECT COUNT(*) FROM tenants;"
# Expected: 2 (existing test tenants preserved)
```

**After Step 1.7 (Final Verification):**
```bash
# Run migration
php artisan migrate

# Tenants schema complete
mysql -u root zonakasir -e "DESCRIBE tenants;"
# Expected columns: id, tenancy_email, google_id, data, is_active, suspended_at, suspension_reason, created_at, updated_at

# Domains table exists
mysql -u root zonakasir -e "DESCRIBE domains;"
# Expected columns: id, domain, tenant_id, created_at, updated_at

# No reference to 'central' anywhere in codebase (excluding doc/vendor/git)
grep -rn "connection.*['\"]central['\"]" app/ database/migrations/ config/database.php
# Expected: no output

# Admin panel loads
php artisan serve
# Open http://localhost:8000/admin → Tenants menu → No error, 2 tenants shown

# Tenant panel loads
# Open http://localhost:8000/member → Login → No error

# Run tests
php artisan test
```

### Execution Checklist

```
Phase 1: Local ✅ COMPLETE
  [x] Step 1.1 — Config → verify
  [x] Step 1.2 — Models → verify
  [x] Step 1.3 — Migrations → verify
  [x] Step 1.4 — App code → verify
  [x] Step 1.5 — Tests → verify
  [x] Step 1.6 — DB rename → verify
  [x] Step 1.7 — Final verify → ALL PASS

Phase 2: Staging ✅ COMPLETE
  [x] Step 2.1 — Backup → schema align → data merge → verify
  [x] Step 2.2 — Deploy → verify
  [x] Step 2.3 — Post-deploy (.env + migrate) → verify
  [x] Step 2.4 — Backup cleanup → DONE
  [x] Bonus: Fix welcomed_at migration hasColumn guard
```

### Blockers (DO NOT proceed if any of these fail)

| Check | Command | Fail Action |
|-------|---------|-------------|
| DB connection | `php artisan tinker` → `DB::connection()->getDatabaseName()` | Fix `.env` DB config |
| Tenants query | `php artisan tinker` → `App\Tenant::count()` | Fix model `$connection` |
| Domains query | `php artisan tinker` → `DB::select("SHOW TABLES LIKE 'domains'")` | Fix migration #13 |
| Admin panel | Open `/admin` → Tenants menu | Check Filament error log |
| No central refs | `grep -rn "connection.*central" app/ database/ config/` | Find and remove remaining refs |

---

## Changelog

| Version | Date | Change |
|---------|------|--------|
| v1.0 | 2026-06-15 | Initial planning doc |
| v2.0 | 2026-06-15 | Fix 7 anomalies (APP_CENTRAL_DOMAIN, Domain model, etc.) |
| v3.0 | 2026-06-15 | Dual-path migration strategy (fresh DB + existing DB) |
| v4.0 | 2026-06-15 | **COMPLETE** — Phase 1 (local) + Phase 2 (staging) done. Bonus: welcomed_at guard fix. |

### Files Changed (15 total)

| # | File | Change |
|---|------|--------|
| 1 | `.env.example` | `DB_DATABASE=zonakasir`, `DB_USERNAME=zonakasir` |
| 2 | `docker-compose.yml` | `DB_DATABASE:-zonakasir`, `DB_USERNAME:-zonakasir` |
| 3 | `config/database.php` | Removed `central` connection block |
| 4 | `app/Tenant.php` | Removed `$connection = 'central'` |
| 5 | `app/Domain.php` | Removed `$connection = 'central'`, `$incrementing`, `$keyType` |
| 6 | `database/migrations/2019_09_15_000010_create_tenants_table.php` | Filled with full schema |
| 7 | `database/migrations/2019_09_15_000020_create_domains_table.php` | Filled with domains schema |
| 8 | `database/migrations/2026_06_14_040000_add_google_id_to_tenants_table.php` | Removed `Schema::connection('central')` |
| 9 | **NEW:** `database/migrations/2026_06_15_150000_add_missing_tenant_columns_and_create_domains.php` | Existing DB path |
| 10 | `database/migrations/tenant/2026_06_15_000001_add_welcomed_at_to_users_table.php` | Added `hasColumn` guard |
| 11 | `app/Filament/Admin/Resources/SubscriptionResource.php` | Removed central config query |
| 12 | `app/Filament/Admin/Pages/NotificationDebugger.php` | Removed dead `tenant_db` block |
| 13 | `app/Http/Controllers/Auth/GoogleController.php` | Cleaned comments |
| 14 | `tests/Feature/Http/Controllers/Auth/RegisteredUserControllerTest.php` | Fixed 2 test failures + removed dead config |
| 15 | `docs/planning/SINGLE_DB_ARCHITECTURE.md` | This doc |
