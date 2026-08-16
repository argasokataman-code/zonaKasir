# Plan: On-Prem Deployment (Single Tenant) — zonaKasir POS

> **Status:** Implemented 2026-08-15 (see below)
> **Created:** 2026-08-15
> **Author:** Product Owner
> **Goal:** Jual on-prem ke client (kasir utuh, tanpa lapisan SaaS). Deploy lancar tanpa central platform.

---

## 0. Implemented

- `config/onprem.php` — `ONPREM_MODE`, license, heartbeat config
- `CheckSubscription` + `PlanFeatureMiddleware` — bypass kalau `onprem.mode`
- `php artisan onprem:bootstrap` — tenant + owner + subscription permanen
- `app/Console/Kernel.php` — skip billing cron kalau onprem
- License + monitoring: lihat `ONPREM_LICENSE_PLAN.md`
- Tests: `tests/Feature/OnpremHeartbeatTest.php`, `tests/Feature/LicenseServiceTest.php`

## 1. Tujuan

Produk kasir utuh berjalan standalone di server client. Lapisan SaaS (admin landlord, billing subscription, register self-serve, payout) gak dipakai tapi **tetap di codebase** (idle) — menghindari kerja strip besar + risk break.

**Jual model:** one-time fee + support. Bukan subscription.

## 2. Kenapa Sekarang (Lazy Path)

- Sistem single-DB multi-tenant (`app/Tenant.php:30-34`) — gak ada central DB/auth/license server, gak ada domain routing wajib (`TenantPanelProvider.php:188` path-based `/member`)
- Satu-satunya blokir: subscription gate + plan seeder + cron billing
- Bisa bypass tanpa hapus kode → diffs kecil, gak perlu tes ulang besar

## 3. Yang Wajib Di-handle (Blockers)

| # | Blocker | Lokasi | Solusi On-Prem |
|---|---------|--------|----------------|
| B1 | Semua API tenant 403 tanpa subscription | `app/Http/Middleware/CheckSubscription.php:31-58` | 2 opsi: (a) env flag `ONPREM_MODE=true` → middleware langsung `next()`, atau (b) seeder subscription aktif permanen |
| B2 | Fitur mati kalau plan gak punya | `app/Services/PlanAccessService.php:10-37` | Seeder plan tertinggi + subscription `active` tanpa `ends_at` (null = gak pernah expire) |
| B3 | Cron `billing:check` expire trial, generate invoice | `app/Console/Commands/CheckBilling.php` + `Kernel.php:17-21` | Matiin dari schedule di env on-prem (atau scheduler gak jalan = gak ada cron) |
| B4 | Trial expire auto via middleware | `CheckSubscription.php:61-80` | Nihil — teratasi B1/B2 (status `active`, `ends_at` null) |
| B5 | Renewal butuh Midtrans | `SubscriptionWebhookController.php` | Gak dipakai on-prem — subscription permanen |

## 4. Opsi Implementasi

### Opsi A (Recommended): Env flag `ONPREM_MODE` + Seeder `TenantBootstrap`

Kerja kecil, gak hapus apa pun:

1. **Config:** tambah `config/onprem.php` baca `ONPREM_MODE` dari `.env` (default `false`).
2. **CheckSubscription:** baris awal — `if (config('onprem.mode')) return $next($request);`
3. **PlanFeatureMiddleware:** sama, bypass kalau onprem.
4. **Seeder baru `database/seeders/TenantBootstrapSeeder.php`:**
   - Buat tenant (id, `tenancy_email`)
   - Buat user owner + role admin + assign
   - Seed PermissionSeeder, PaymentMethodSeeder, CategorySeeder
   - Buat subscription `status=active`, `ends_at=null`, plan tertinggi
5. **Command `php artisan onprem:bootstrap`** — wrap seeder (single entry, gak perlu tinker manual).
6. **Schedule:** `app/Console/Kernel.php` — skip `CheckBilling` kalau onprem.

### Opsi B: Tanpa kode, 100% tinker manual
- Seeder + artisan per langkah. Bisa, tapi rawan salah input client → bukan buat dijual.

## 5. Deploy Checklist On-Prem (untuk client/partner)

```bash
# 1. Prasyarat
# PHP ^8.4, PostgreSQL 15+, Composer, Node 18+

# 2. Install
git clone <repo> /var/www/zonaKasir
cd /var/www/zonaKasir
composer install --no-dev --optimize-autoloader
cp .env.example .env
# edit .env: DB_*, APP_URL, ONPREM_MODE=true, MIDTRANS/FLIP/FIREBASE boleh kosong
php artisan key:generate

# 3. DB
createdb zonakasir
php artisan migrate                        # root migrations
php artisan migrate --path=database/migrations/tenant --seed   # tenant + PlanSeeder
php artisan onprem:bootstrap               # tenant + user owner + subscription permanen

# 4. Assets
php artisan filament:assets
php artisan livewire:publish --assets
npm ci && npm run build

# 5. Queue/scheduler
# JANGAN pasang cron billing:check (onprem)
php artisan schedule:work   # opsional, hanya task lain
```

## 6. Fitur yang Gak Aktif di On-Prem (dokumentasi ke client)

- Panel admin landlord `/admin` (Tenant/Subscription/Invoice/Coupon/Withdrawal resource)
- Register self-serve + trial 7 hari + Google auto-provision — route & tombol di-hide saat `ONPREM_MODE=true` (`prevent.onprem.register` middleware)
- Google login dimatikan total di on-prem (login email/password saja, `TenantLogin.php`)
- Payment gateway Midtrans (renewal) & Flip (payout/withdrawal) — nav + API di-hide
- Coupon trial-extension
- Update dari GitHub `app:update` — tetap jalan kalau repo publik (opsional)

**Fitur AKTIF penuh:** POS/cart, selling/purchasing/product/cashier report, printer, setting, update, user & role, semua fungsi kasir.

## 7. Estimasi Kerja

| Item | Effort |
|------|--------|
| `config/onprem.php` + bypass 2 middleware | ~0.5 jam |
| `TenantBootstrapSeeder` + command | ~1 jam |
| Skip `CheckBilling` di schedule | ~0.25 jam |
| Update README + deploy doc | ~0.5 jam |
| **Total** | **~2-3 jam** |

## 8. Open Questions

1. ~~Update `app:update` dari GitHub~~ — client ikut GitHub update (keputusan user)
2. ~~License per on-prem client~~ — ya, lihat `ONPREM_LICENSE_PLAN.md` (keputusan user)
3. ~~Perlu installer script~~ — checklist doc dulu, script belakangan kalau ada partner (keputusan user)

## 9. Keputusan Terkait

- Sistem sudah SaaS total single-DB (`app/Tenant.php`); on-prem = fitur opsional, bukan rewrite
