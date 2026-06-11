# Admin SaaS Panel — Development Plan

**Stack:** Laravel 11 + Filament 3 (Admin Panel) + stancl/tenancy
**Path:** `{domain}/admin`
**Auth:** Admin model (central DB) via `admin` guard

---

## Phase 1 — Tenant & Domain Management (Core)

> **Goal:** Admin bisa lihat, kelola, suspend, login sebagai tenant.
> **Durasi:** ~2-3 hari

| # | Task | File | Priority |
|---|------|------|----------|
| 1 | **Tenant List** (existing) — kolom: ID, domain, name, email, registered date, status | `app/Filament/Admin/Resources/TenantResource.php` | ✅ Done |
| 2 | **Domain management** — lihat & kelola domain tenant, suspend tenant (flag `is_active`) | Migration + TenantResource update | 🔴 High |
| 3 | **Tenant impersonate** — klik "Login as Tenant" → redirect + auto login | `ImpersonateAction` + session | 🔴 High |
| 4 | **Tenant detail page** — info tenant, jumlah user, total transaksi | ViewTenant + widgets | 🟠 Medium |
| 5 | **Maintenance mode per tenant** — nonaktifin akses tenant tanpa SQL manual, tampilkan halaman "Akun dinonaktifkan" | `CheckTenantActive` middleware + tenant route | 🔴 High |
| 6 | **Tenant data export/delete (GDPR)** — export semua data tenant (JSON) + hapus total (database+cabinet) | Export action + DeleteTenant job | 🟠 Medium |
| 7 | **Activity log** — catat siapa yg login, impersonate, hapus tenant | `spatie/laravel-activitylog` | 🟡 Low |

### Migration (Phase 1)
```php
Schema::table('tenants', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->after('data');
    $table->timestamp('suspended_at')->nullable()->after('is_active');
    $table->text('suspension_reason')->nullable()->after('suspended_at');
});
```

### Middleware Flow
```
Tenant request → CheckTenantActive
  ├── if !active → return view "tenant-suspended" (dengan alasan + kontak support)
  └── if active → proceed to app
```

---

## Phase 2 — Subscription & Billing

> **Goal:** Admin bikin paket, tenant langganan, billing jalan otomatis + manual.
> **Durasi:** ~5-6 hari

### A. Infrastructure (~1 hari)

| # | Task | Detail |
|---|------|--------|
| 1 | **Plan model** — tabel `plans`: name, slug, price_monthly, price_yearly, features (JSON) | `app/Models/Plan.php` + migration |
| 2 | **PlanResource** — CRUD paket harga via admin | `app/Filament/Admin/Resources/PlanResource.php` |
| 3 | **Subscription model** — tabel `subscriptions`: tenant_id, plan_id, status, start/end, billing_cycle | `app/Models/Subscription.php` + migration |

### B. Subscription Flow (~1.5 hari)

| # | Task | Detail |
|---|------|--------|
| 4 | **Trial flow** — pas register, auto-assign free trial plan (14/30 hari) | Update `RegisterTenant` + `TrialMiddleware` |
| 5 | **Check subscription middleware** — blokir tenant expired (redirect ke billing page) | `CheckSubscription` middleware |
| 6 | **Upgrade / downgrade plan** — tenant ganti paket, prorate calculation | `SubscriptionService::swap()` |
| 7 | **Cancel subscription** — tenant cancel, grace period, data retention policy | `SubscriptionService::cancel()` |

### C. Payment (~2 hari)

| # | Task | Detail |
|---|------|--------|
| 8 | **Manual billing** — admin catat pembayaran transfer, update status subscription | `ManualPayment` resource + form |
| 9 | **Invoice model** — tabel `invoices`: tenant_id, subscription_id, amount, status, paid_at | + migration |
| 10 | **Payment Gateway** — integrasi Midtrans / Xendit / Stripe (opsional, post-MVP) | Webhook handler |
| 11 | **Coupon / Promo** — diskon %, diskon nominal, trial extension, referral code | `Coupon` + `Redemption` models |

### D. Billing Automation (~0.5 hari)

| # | Task | Detail |
|---|------|--------|
| 12 | **Cron job setup** — artisan command `billing:check` setiap hari: cek subscription expired, kirim reminder, auto-suspend | `app/Console/Commands/CheckBilling.php` |
| 13 | **Email notification** — trial mau habis (H-3), tagihan jatuh tempo (H-0), akun suspended, invoice paid | `app/Notifications/` + mail config |

### E. Dashboard & Reports (~0.5 hari)

| # | Task | Detail |
|---|------|--------|
| 14 | **Billing dashboard** — MRR, active subs, churn rate, revenue chart | `BillingStats` widget + charts |
| 15 | **Subscription list** — filter status (active/expired/trialing) | `SubscriptionResource` |

### Schema (Phase 2)
```php
Schema::create('plans', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->decimal('price_monthly', 12);
    $table->decimal('price_yearly', 12)->nullable();
    $table->json('features')->nullable(); // ["max_stores" => 3, "max_users" => 5]
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id');
    $table->foreignId('plan_id');
    $table->string('status'); // trialing, active, past_due, expired, cancelled
    $table->string('billing_cycle'); // monthly, yearly
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamp('starts_at');
    $table->timestamp('ends_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->timestamps();
});

Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id');
    $table->foreignId('subscription_id');
    $table->string('number')->unique();
    $table->decimal('amount', 12);
    $table->string('status'); // pending, paid, failed, refunded
    $table->string('payment_method')->nullable(); // manual_transfer, midtrans, etc
    $table->text('notes')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});

Schema::create('coupons', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->string('type'); // percentage, nominal, trial_extension
    $table->decimal('value', 12)->nullable(); // 20 (for 20%), or 50000 (for Rp50k)
    $table->integer('trial_days')->nullable(); // extra trial days
    $table->integer('max_redemptions')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

### Cron Setup
```bash
# Setiap jam 06:00 pagi
* 6 * * * cd /home/jogn3455/public_html/zonakasir.jogjatourdrive.com && php artisan billing:check >> storage/logs/billing.log 2>&1
```

---

## Phase 3 — Admin Features & Monitoring

> **Goal:** Admin monitor sistem + komunikasi dengan tenant.
> **Durasi:** ~2-3 hari

| # | Task | Detail |
|---|------|--------|
| 1 | **System Health** — queue status, DB connection, disk usage, PHP version, SSL cert expiry | `app/Filament/Admin/Pages/SystemHealth.php` |
| 2 | **Error Log Viewer** — lihat `storage/logs/laravel.log` via admin (dengan filter level) | `LogViewer` page |
| 3 | **Broadcast Notification** — kirim notifikasi ke semua tenant atau per-tenant (via email + in-app) | Livewire + queue job |
| 4 | **Tenant Impersonation Log** — catat dan tampilkan riwayat impersonate admin | Dari activity log |
| 5 | **Send Mail to Tenant** — form kirim email langsung ke tenant dari admin | Livewire + mail |
| 6 | **Export Tenants CSV** — download daftar tenant + filter | Export action |

---

## File Structure

```
app/
├── Filament/Admin/
│   ├── Resources/
│   │   ├── TenantResource.php              ← (existing + Phase 1)
│   │   │   └── Pages/
│   │   │       ├── ListTenants.php          ← (existing)
│   │   │       └── ViewTenant.php           ← (existing)
│   │   ├── PlanResource.php                ← NEW (Phase 2)
│   │   ├── SubscriptionResource.php         ← NEW (Phase 2)
│   │   ├── InvoiceResource.php              ← NEW (Phase 2)
│   │   └── CouponResource.php               ← NEW (Phase 2)
│   ├── Widgets/
│   │   ├── DashboardStats.php              ← (existing)
│   │   ├── TenantCharts.php                ← NEW (Phase 1)
│   │   └── BillingStats.php                ← NEW (Phase 2)
│   └── Pages/
│       ├── SystemHealth.php                ← NEW (Phase 3)
│       └── LogViewer.php                   ← NEW (Phase 3)
├── Http/Middleware/
│   ├── CheckTenantActive.php               ← NEW (Phase 1)
│   └── CheckSubscription.php               ← NEW (Phase 2)
├── Models/
│   ├── Admin.php                          ← (existing)
│   ├── Plan.php                           ← NEW (Phase 2)
│   ├── Subscription.php                   ← NEW (Phase 2)
│   ├── Invoice.php                        ← NEW (Phase 2)
│   └── Coupon.php                         ← NEW (Phase 2)
├── Services/
│   ├── SubscriptionService.php            ← NEW (Phase 2)
│   └── BillingService.php                 ← NEW (Phase 2)
├── Console/Commands/
│   └── CheckBilling.php                   ← NEW (Phase 2)
└── Notifications/
    ├── TrialAboutToExpire.php             ← NEW (Phase 2)
    ├── SubscriptionExpired.php            ← NEW (Phase 2)
    └── InvoicePaid.php                    ← NEW (Phase 2)

database/migrations/
├── YYYY_MM_DD_add_is_active_to_tenants.php          ← NEW (Phase 1)
├── YYYY_MM_DD_create_plans_table.php                ← NEW (Phase 2)
├── YYYY_MM_DD_create_subscriptions_table.php         ← NEW (Phase 2)
├── YYYY_MM_DD_create_invoices_table.php              ← NEW (Phase 2)
└── YYYY_MM_DD_create_coupons_table.php               ← NEW (Phase 2)
```

---

## Commit Plan

| Step | Message | Files |
|------|---------|-------|
| P1.1 | `feat(admin): tenant domain + suspend + maintenance mode` | Migration + TenantResource + CheckTenantActive |
| P1.2 | `feat(admin): tenant impersonate + activity log` | Impersonate action + spatie/activitylog |
| P1.3 | `feat(admin): tenant export/delete + detail page` | Export action + hapus tenant + widget |
| P2.1 | `feat(admin): plan & subscription models + CRUD` | Plans + Subscriptions migrations, models, resources |
| P2.2 | `feat(admin): subscription flow + trial + middleware` | RegisterTenant + CheckSubscription + swap |
| P2.3 | `feat(admin): manual billing + invoices + coupons` | InvoiceResource + CouponResource + ManualPayment |
| P2.4 | `feat(admin): billing cron + email notifications` | CheckBilling command + Notification classes |
| P2.5 | `feat(admin): billing dashboard + reports` | BillingStats + SubscriptionResource filter |
| P3.1 | `feat(admin): system health + log viewer` | SystemHealth + LogViewer pages |
| P3.2 | `feat(admin): broadcast notification + tenant email + export` | Notification system + export CSV |

---

## Dependencies

```bash
composer require spatie/laravel-activitylog     # Phase 1
composer require maatwebsite/laravel-excel       # Phase 1 (export CSV)
# Notifications:
# composer require laravel-notification-channels/webhook  # Phase 3 (broadcast)
# Payment gateway (post-MVP):
# composer require midtrans/midtrans-php
```

---

## Anti-Patterns & Best Practices

| ❌ Jangan | ✅ Lakukan |
|-----------|------------|
| Migrasi tenant database dari admin (lama, riskan) | Admin cuma akses central DB. Tenant DB diurus per-tenant |
| Tenan langsung kena suspend tanpa peringatan | Kirim email dulu H-7, H-3, H-1, baru suspend |
| Simpan credential payment gateway di tenant DB | Semua billing di central DB |
| Impersonate tanpa log | Setiap impersonate wajib tercatat (siapa, kapan, tenant apa) |
| Hapus tenant langsung permanent | Soft delete dulu, grace period 30 hari, baru permanent delete |
| Cron tiap menit | Cron cukup 1×/hari (pagi). Subscription yg urgent via webhook |

---

## Notes

- **Phase 1** ready to start (TenantResource & AdminPanelProvider udah ada)
- **Phase 2** diskusiin payment gateway (manual transfer dulu atau langsung midtrans?)
- **Phase 3** bisa dikerjain kapan aja, gak blocking phase lain
- Semua perubahan di **central DB only** — gak perlu migrate tenant databases
- Sebelum deploy perubahan admin, test dulu di local via `admin.localhost:8080/admin`
