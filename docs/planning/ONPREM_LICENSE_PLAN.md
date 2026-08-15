# Plan: On-Prem License + Monitoring — zonaKasir POS

> **Status:** Implemented 2026-08-15
> **Created:** 2026-08-15
> **Author:** Product Owner
> **Goal:** Jual on-prem per client dengan license per domain, terpantau dari pusat via heartbeat. Kasir client tidak pernah mati karena license.

---

## 0. Implemented

- `app/Services/LicenseService.php` — sign (openssl RSA-SHA256) + verify lokal
- `php artisan onprem:keypair` — generate RSA keypair (private = vendor only)
- `php artisan onprem:license --customer= --domain= --days=` — generate license `ZONA-{slug}-{payload}.{sig}`
- `php artisan onprem:verify` — validasi lokal, warning only
- `app/Filament/Tenant/Widgets/LicenseWarning` — banner merah di dashboard tenant
- Endpoint `POST /api/v1/onprem/heartbeat` (X-Onprem-Token) + tabel `onprem_instances`
- `php artisan onprem:heartbeat` — cron client 24 jam (silent fail, instance_id auto di storage)
- `app/Filament/Admin/Resources/OnpremInstanceResource` — dashboard badge Online/Ragu/Hilang
- Tests: 7 (license 4 + heartbeat 3)

## 1. Prinsip (Sudah Disepakati)

1. **On-prem = 1 server client = 1 database sendiri** (isolasi penuh). `tenant_id` tetap ada tapi hanya 1 baris aktif.
2. **License = identifikasi + entitlement, bukan DRM keras.** Kasir tidak pernah diblokir oleh license.
3. **Monitoring via heartbeat** — visibility ke client, bukan enforcement.
4. License warning-only; hukuman pelanggaran = stop update + support (bukan kunci kasir).

## 2. Model License

| Aspek | Keputusan |
|-------|-----------|
| Binding | Per **domain** (unik per client) |
| Bentuk | `ZONA-{CUSTOMER}-{16-hex}` (SHA-256 hash domain+customer, potong) |
| Validasi | **Lokal** — signature verify, tanpa internet, tanpa infra pusat |
| Expiry | Default **365 hari**, override `--days`; lewat = warning di admin panel on-prem, kasir tetap jalan |
| Nama customer | **Manual input** di command `--customer=` |
| Update | Client on-prem **ikut GitHub update** `app:update` |
| Tracking | Heartbeat berkala ke pusat (lihat §4) |

### Format License (opsional detail)
```
ZONA-TOKO-BUDI-4f3a...c21b
```
- Bagian `TOKO-BUDI` = human readable (slug customer)
- Bagian hex = hash `domain + customer + secret` → verifikasi lokal tanpa perlu query pusat

### Generate (di sisi kita, script/command)
```php
# di pusat, command artisan:
php artisan onprem:license --customer="Toko Budi" --domain="tokobudi.zona.id" --days=365
# output: ZONA-TOKO-BUDI-4f3a...c21b
```

## 3. Validasi Lokal (Side Client)

**Config:** `config/onprem.php` membaca dari `.env` client:
```php
'license_key' => env('ONPREM_LICENSE_KEY'),
'public_key'  => env('ONPREM_PUBLIC_KEY'),  # pasangan secret kita
```

**Verifikasi:** command `onprem:verify` — decrypt/verify signature license vs domain client. Hasil di-cache; di-eksekusi tiap `kernel.php` schedule (mis. harian) + saat panel admin dibuka.

**UI warning:** Filament Tenant panel — banner di dashboard kalau:
- License tidak valid / salah domain
- Masa aktif lewat (> N hari)
- Warning HANYA notifikasi, tidak ada blokir middleware (`CheckSubscription` path sama sekali tidak tersentuh).

## 4. Heartbeat (Monitoring ke Pusat)

### Side Client — tiap 24 jam
```
POST https://pusat.mu/api/v1/onprem/heartbeat
{
  "instance_id": "uuid-v4-di-generate sekali per deploy",
  "domain": "tokobudi.zona.id",
  "app_version": "1.2.3",
  "license_key": "ZONA-TOKO-BUDI-4f3a...",
  "php_version": "8.4"
}
```
- HTTP client Laravel standar (`Http::post`), jadwal di `app/Console/Kernel.php`.
- Config di `.env`: `ONPREM_HEARTBEAT_URL` (default kosong = heartbeat mati).
- **Heartbeat gagal TIDAK memengaruhi operasional** — try/catch, silent fail, retry esok.

### Side Pusat — endpoint + tabel
- Route **publik**: `routes/api.php` → `POST /api/v1/onprem/heartbeat` (JANGAN kena `CheckSubscription` — itu middleware tenant).
- Tabel `onprem_instances` (migration, DB pusat, bukan tenant):
  | Kolom | Isi |
  |-------|-----|
  | `id` | PK |
  | `instance_id` | uuid unik, unique |
  | `domain` | domain client |
  | `customer_name` | dari license/regitrasi |
  | `license_key` | key, unique |
  | `app_version` | versi terakhir |
  | `last_seen_at` | timestamp terakhir heartbeat |
  | `created_at/updated_at` | |

- Upsert: `updateOrCreate(['instance_id' => ...], [...])` + set `last_seen_at`.

## 5. Dashboard Pemantauan (Pusat)

**1 resource Filament baru di panel `/admin`: `OnpremInstanceResource`**

| Kolom | Badge |
|-------|-------|
| Customer | — |
| Domain | — |
| License key | — |
| App version | — |
| Last seen | 🟢 online (≤2 hari) · 🟡 ragu (2-7 hari) · 🔴 hilang (>7 hari) |

- Status badge computed dari `last_seen_at` vs now.
- Filter: status, customer, version.

## 6. Arsitektur Ringkas

```
PUSAT (kamu)                          ON-PREM (client)
┌──────────────────────┐              ┌────────────────────────┐
│ /admin OnpremResource│              │ config/onprem.php      │
│   (dashboard)        │              │  license_key + pub key │
│ onprem_instances tbl │              │ onprem:verify (lokal)  │
│ POST /api/v1/heartbeat◄──── 24jam ──│ onprem:heartbeat cron  │
│ admin:license cmd    │              │ UI warning banner      │
└──────────────────────┘              │ kasir TIDAK pernah      │
                                      │  diblokir              │
                                      └────────────────────────┘
```

## 7. File yang Kena (Estimasi)

| File | Perubahan |
|------|-----------|
| `config/onprem.php` | baru — license + heartbeat config |
| `database/migrations/..._create_onprem_instances_table.php` | baru — tabel monitoring |
| `app/Http/Controllers/Api/OnpremHeartbeatController.php` | baru — endpoint public |
| `routes/api.php` | +1 route heartbeat |
| `app/Console/Commands/VerifyLicense.php` | baru — validasi lokal |
| `app/Console/Commands/SendHeartbeat.php` | baru — cron client |
| `app/Console/Kernel.php` | +schedule heartbeat |
| `app/Filament/Admin/Resources/OnpremInstanceResource.php` | baru — dashboard |
| `app/Filament/Tenant/Pages/...` | +banner warning license |

**Sisi pusat license generation:** `app/Console/Commands/GenerateLicense.php` + konfigurasi keypair (openssl, publish private key JANGAN di commit).

## 8. Keamanan

- **JANGAN commit private key.** Publik key aja yang masuk repo client (config).
- Heartbeat endpoint: tanpa auth token rawan spam — tambah **shared secret** header (`ONPREM_HEARTBEAT_TOKEN` di `.env` client, validasi di controller).
- License hash pakai secret server-side, client hanya verifikasi signature (asimetris).
- `instance_id` di-generate sekali, disimpan di `.env`/storage client, jangan regenerate tiap deploy.

## 9. Open Questions

1. ~~Customer nama diambil dari mana~~ — manual input `--customer=` (keputusan user)
2. ~~Durasi default~~ — 365 hari, override `--days` (keputusan user)
3. ~~Alert otomatis saat instance hilang~~ — DIBATALKAN, cukup badge merah dashboard (keputusan user: keep simple)
4. ~~Endpoint cadangan~~ — 1 endpoint cukup (asumsi)

## 10. Keputusan Terkait

- DEC-003 — jual on-prem, lapisan SaaS idle
- `docs/planning/ONPREM_DEPLOYMENT_PLAN.md` — deploy checklist, `onprem:bootstrap`
- Plan ini menambah lapisan: license + monitoring di atas deployment plan
