# Rebranding Plan: Lakasir → zonaKasir

**Dokumen ini berisi rencana lengkap rebranding dari Lakasir menjadi zonaKasir.**
**Status:** ✅ Fase 1 (Tekstual), 2 (CSS), 3 (JS), 4 (Logo) selesai. Fase 5 (Infra) & 6 (Dokumentasi dasar) tersisa.

---

## Ringkasan

**Total ~200+ referensi** ditemukan di ~55 file (exclude vendor/node_modules/cache).
Dari scan mendalam (text search + analisis tambahan: warna, class, infra, dll).

| Kategori | Jumlah | Level |
|----------|--------|-------|
| 🟢 Teks branding (aman langsung ganti) | ~85 | Rendah |
| 🟡 Tailwind CSS class `*-lakasir-*` | 38 | Sedang |
| 🟡 Brand color `#FF6600` + `orange-500/600` | 29 | Sedang |
| 🟡 Infrastruktur (Docker, CI/CD, Lando) | 18 | Sedang |
| 🟡 JS global vars `window.lakasir*` | 3 | Sedang |
| 🟡 Meta tags + SEO | 7 | Sedang |
| 🔴 Tenant DB naming | 4 | Tinggi |
| 🔴 GitHub org/URL (auto-updater) | 11 | Tinggi |
| 🔴 External package `lakasir/has-crud-action` | 2 | ⏭️ Skip (external) |
| 📄 Dokumentasi | ~55 | Ringan |

---

## 🔴 Fase 0: Critical Path (Kerjakan Paling Akhir)

Item yang **bergantung pada hal lain** — kerjakan setelah fase lain selesai.

### 0.1 — Rename database (production)
- `.env` — `DB_DATABASE=lakasir` → `zonakasir`
- `.env` — `DB_DATABASE_TESTING=lakasir_testing` → `zonakasir_testing`
- `.env.example` — `DB_DATABASE=lakasir` & `DB_USERNAME=lakasir`
- `docker-compose.yml` — semua default env DB
- `.github/workflows/pre-release.yml` — env CI
- **Effek:** Semua koneksi DB harus disesuaikan + rename manual via MySQL

### 0.2 — Rename GitHub org/repo
- `README.md` — clone URL, badge URLs, star history
- `config/updater.php` — API URL auto-updater
- `.github/FUNDING.yml` — org username sponsor
- `resources/views/livewire/pages/welcome.blade.php:163` — link ke Flutter repo
- **Effek:** Semua URL GitHub berubah. Auto-updater akan broken sampai diupdate.

### 0.3 — Tenant DB prefix `lakasir_` (🔴 Kritis — Jangan Sentuh Dulu)
- `app/Services/RegisterTenant.php:34` — `'tenancy_db_name' => 'lakasir_'.$name`
- `database/seeders/UserSeeder.php:25` — `Str::after($dbName, 'lakasir_')`
- `tests/Pest.php:68` — `DROP DATABASE lakasir_toko_testing`
- `tests/.../RegisteredUserControllerTest.php:23,109` — `DROP DATABASE lakasir_tokotest`
- **Jika diubah:** Semua tenant DB dengan prefix `lakasir_` tidak terbaca.
- **Rekomendasi:** Tunda. Buat backward-compatible yang cek kedua prefix.

---

## Fase 1: Branding Visual (Aman — Langsung Ganti)

### 1.1 — APP_NAME
| File | Baris | Lama | Baru |
|------|-------|------|------|
| `.env` | 1 | `APP_NAME=Lakasir` | `APP_NAME=zonaKasir` |
| `.env.example` | 1 | `APP_NAME=Lakasir` | `APP_NAME=zonaKasir` |
| `.env.testing` | 1 | `APP_NAME=Lakasir` | `APP_NAME=zonaKasir` |

> ⚠️ **Efek samping:** Session cookie name di `config/session.php` pakai `Str::slug(APP_NAME)`.
> Berubah dari `lakasir_session` ke `zonakasir_session` → **semua user akan logout paksa.**

### 1.2 — Meta Tags & SEO
`resources/views/livewire/components/layouts/guest.blade.php`:
- Baris 6: meta description "Lakasir merupakan..."
- Baris 8: meta author "Lakasir"
- Baris 12: `og:title` "Lakasir - Aplikasi..."
- Baris 13: `og:description`
- Baris 15: `og:image` URL
- Baris 19: `twitter:card`
- Baris 21: `twitter:title`
- Baris 22: `twitter:description`
- Baris 23: `twitter:image`

### 1.3 — Auth Layout
`resources/views/livewire/components/layouts/auth.blade.php:62`:
- `<a class="navbar-brand" href="#">Lakasir</a>` → `zonaKasir`

### 1.4 — Register Page
`resources/views/livewire/forms/auth/register.blade.php`:
- Cek apakah ada teks "Lakasir" (logo image path sudah di Fase 6)

### 1.5 — Welcome/Landing Page
`resources/views/livewire/pages/welcome.blade.php` (~20 teks):
| Lokasi | Teks |
|--------|------|
| Baris 69, 76 | Deskripsi fitur "Lakasir bisa..." |
| Baris 99 | Logo text `<p>Lakasir</p>` |
| Baris 104 | WhatsApp link `Halo%20dengan%20lakasir%2C...` |
| Baris 154 | Hero title `<span>Lakasir</span>` |
| Baris 158-159 | Paragraph "Lakasir merupakan..." / "Lakasir hadir..." |
| Baris 186 | Testimonial "Lakasir memudahkan... terimakasih Lakasir" |
| Baris 198 | Section title "Tentang Lakasir" |
| Baris 199 | About paragraph (5x "lakasir") |
| Baris 204 | "Menu Lakasir" |
| Baris 241 | "Fitur Lakasir" |
| Baris 242 | "Dengan lakasir..." |
| Baris 323 | Footer "Lakasir made with" |

### 1.6 — Email Notification
`app/Notifications/DomainCreated.php:30,34`:
- `'Selamat datang di Lakasir'`
- `'Lakasir'` (salutation)

### 1.7 — Demo Credentials
`app/Filament/Tenant/Pages/TenantLogin.php:49`:
- `'email' => 'demo@lakasir.com'` → `'demo@zonakasir.com'`

### 1.8 — Teks Lain
| File | Baris | Lama |
|------|-------|------|
| `app/Console/Commands/CreateUser.php` | 14 | `'Create user for standalone lakasir'` |
| `app/Services/AppUpdateService.php` | 73 | `"User-Agent: LakasirAutoUpdater\r\n"` |
| `app/Services/AppUpdateService.php` | 112 | `storage_path('app/update/lakasir/')` |
| `database/seeders/RetailSeeder.php` | 28 | `'lakasirapp@gmail.com'` |
| `database/seeders/RetailSeeder.php` | 29 | `"Contact the Lakasir owner..."` |
| `resources/views/filament/tenant/pages/update.blade.php` | 5 | `"...keep Lakasir up to date"` |
| `resources/views/donation-banner.blade.php` | 2 | `"...building Lakasir"` |

---

## Fase 2: Brand Color (🟡 Hati-hati)

### 2.1 — Primary Color `#FF6600` (3 lokasi di code)
| File | Baris | Saat ini |
|------|-------|----------|
| `tailwind.config.js` | 28 | `primary: "#FF6600"` |
| `app/Providers/Filament/TenantPanelProvider.php` | 129 | `Color::hex('#FF6600')` |
| `resources/views/filament/tenant/pages/cashier.blade.php` | 228 | `hover:bg-[#ff6611]` |

> 🎨 **Tentukan dulu warna baru zonaKasir** sebelum mengeksekusi fase ini.

### 2.2 — Hardcoded `orange-500` / `orange-600` (24 matches)
Semua di POS v2 UI — belum pakai variable Tailwind, langsung `bg-orange-500`:
- `resources/views/filament/tenant/pages/pos/index.blade.php` — 5 baris
- `resources/views/filament/tenant/pages/pos/components/cart-item.blade.php` — 4 baris
- `resources/views/filament/tenant/pages/pos/components/barcode-scanner.blade.php` — 6 baris
- `resources/views/filament/tenant/pages/pos/components/add-to-cart-button.blade.php` — 9 baris
- `resources/views/livewire/pages/welcome.blade.php` — 1 baris

**Rekomendasi:** Ganti dengan Tailwind variable `bg-zonakasir-primary` setelah Fase 2.3 selesai.

### 2.3 — Tailwind Color Key & CSS Class (38 matches)
`tailwind.config.js` key `lakasir: { primary: "#FF6600" }` dipakai di:
- `resources/css/filament/tenant/theme.css` — 3 baris `@apply bg-lakasir-primary`
- `resources/views/livewire/pages/welcome.blade.php` — 12 class
- `resources/views/livewire/reset-password.blade.php` — 1 class
- `resources/views/donation-banner.blade.php` — 1 class (`dark:bg-lakasir-secondary` juga!)
- `resources/views/filament/tenant/pages/cashier*.blade.php` — 14 class
- `resources/views/filament/tenant/pages/pos/**/*.blade.php` — 4 class

**Opsi:**
- **Opsi A:** Rename key → `zonakasir` + ganti semua class (banyak perubahan)
- **Opsi B:** Biarkan key `lakasir` (ada sisa, minor)
- **Opsi C:** Dual alias (recommended — migrasi bertahap)
  ```js
  colors: {
      zonakasir: { primary: '#WARNA_BARU' },
      lakasir: { primary: '#WARNA_BARU' },
  }
  ```

---

## Fase 3: JavaScript Window Properties (🟡 Hati-hati)

| File | Variabel |
|------|----------|
| `resources/views/filament/tenant/pages/cashier.blade.php:453-454` | `window.lakasirCurrency`, `window.lakasirLocale` |
| `resources/views/filament/tenant/pages/pos/index.blade.php:164-165` | `window.lakasirCurrency`, `window.lakasirLocale` |
| `resources/js/app.js:108-132` | Referensi ke `window.lakasirCurrency`, `window.lakasirLocale` |

**Rekomendasi:** Rename barengan → `window.zonakasirCurrency`, `window.zonakasirLocale`.

---

## Fase 4: Logo & Assets (🟢 Aman)

### File fisik yang perlu diganti
| File | Aksi |
|------|------|
| `public/favicon.ico` | Ganti logo zonaKasir |
| `public/assets/logo/image.png` | Ganti logo zonaKasir |
| `public/images/icons/icon-48x48.png` | Ganti PWA icon |
| `public/images/icons/icon-72x72.png` | Ganti |
| `public/images/icons/icon-96x96.png` | Ganti |
| `public/images/icons/icon-128x128.png` | Ganti |
| `public/images/icons/icon-144x144.png` | Ganti |
| `public/images/icons/icon-152x152.png` | Ganti |
| `public/images/icons/icon-192x192.png` | Ganti |
| `public/images/icons/icon-512x512.png` | Ganti |
| `public/images/icons/splash_screens/*.png` | Ganti (banyak file) |

### Referensi path logo di view
| File | Baris | Path |
|------|-------|------|
| `guest.blade.php` | 9 | `shortcut icon` → `/assets/logo/image.png` |
| `guest.blade.php` | 15 | `og:image` → `/assets/logo/image.png` |
| `guest.blade.php` | 23 | `twitter:image` → path |
| `register.blade.php` | 5 | `<img src="assets/logo/image.png">` |
| `welcome.blade.php` | 99 | `<img src=".../assets/logo/image.png">` |
| `README.md` | 3 | `https://lakasir.com/assets/logo/image.png` |

---

## Fase 5: Infrastruktur (🟡 Docker, CI/CD, Lando)

### Docker Compose
| Item | Detail |
|------|--------|
| Network | `lakasir` → `zonakasir` (5 baris) |
| Volumes | `lakasir-mysql`, `lakasir-redis` → `zonakasir-mysql`, `zonakasir-redis` |
| Env defaults | `DB_DATABASE`, `DB_USERNAME`, `MYSQL_DATABASE`, `MYSQL_USER` |

### Lando
`.lando.yml`:
- `name: lakasir` → `zonakasir`
- Domain `lakasir.lndo.site` → `zonakasir.lndo.site`
- `admin.lakasir.lndo.site` → `admin.zonakasir.lndo.site`

### CI/CD (GitHub Actions)
`.github/workflows/pre-release.yml`:
- Nama workflow
- `MYSQL_DATABASE: lakasir`
- `MYSQL_USER: lakasir`
- `echo "DB_DATABASE=lakasir" >> .env`
- Zip artifact `lakasir-${VERSION}.zip`

---

## Fase 6: Dokumentasi (Aman)

| File | Aksi |
|------|------|
| `README.md` | Semua "Lakasir" → "zonaKasir", logo URL, clone URL, badge URLs |
| `AGENTS.md` | Judul + referensi project |
| `docs/README.md` | Title + deskripsi |
| `docs/guides/QUICK_FIXES.md` | Title |
| `docs/guides/COMPLETION_GUIDE.md` | Title |
| `docs/E2E_TESTING_REPORT.md` | Referensi |
| `docs/reports/AUDIT*.md` | Semua file audit |
| `docs/API_RESPONSE_STANDARD.md` | Jika ada "Lakasir" |
| `.cursor/00-universal-agent-rules.mdc` | "Lakasir Project" → "zonaKasir Project" |
| `laradumps.yaml:6` | Path (minor) |

---

## Fase 7: External Package (⏭️ Skip)

| Item | Alasan |
|------|--------|
| `"lakasir/has-crud-action"` di `composer.json` | **External package** — nama package tidak perlu diubah |
| `use Lakasir\HasCrudAction\...` di `SupplierController.php` | Namespace dari package eksternal |
| `composer.lock` | Auto-generated dari composer.json |

---

## Summary Lengkap Semua File yang Kena

| # | File | Perubahan |
|---|------|-----------|
| 1 | `.env` | APP_NAME, DB_DATABASE, DB_DATABASE_TESTING |
| 2 | `.env.example` | APP_NAME, DB_DATABASE, DB_USERNAME |
| 3 | `.env.testing` | APP_NAME, DB_DATABASE_TESTING |
| 4 | `docker-compose.yml` | Network, volume, env defaults (~15 baris) |
| 5 | `.lando.yml` | Nama project, domain |
| 6 | `.github/workflows/pre-release.yml` | Env CI, zip name |
| 7 | `.github/FUNDING.yml` | GitHub sponsor username |
| 8 | `tailwind.config.js` | Color key + value |
| 9 | `config/updater.php` | GitHub API URL |
| 10 | `app/Services/RegisterTenant.php` | DB prefix `lakasir_` |
| 11 | `app/Services/AppUpdateService.php` | User-Agent, extract path |
| 12 | `app/Notifications/DomainCreated.php` | Email text + salutation |
| 13 | `app/Console/Commands/CreateUser.php` | Description text |
| 14 | `app/Filament/Tenant/Pages/TenantLogin.php` | Demo email |
| 15 | `app/Providers/Filament/TenantPanelProvider.php` | Primary color hex |
| 16 | `database/seeders/RetailSeeder.php` | Contact email, warning text |
| 17 | `database/seeders/UserSeeder.php` | DB prefix parsing |
| 18 | `tests/Pest.php` | Test DB name |
| 19 | `tests/Feature/.../RegisteredUserControllerTest.php` | Test DB name |
| 20 | `resources/views/livewire/pages/welcome.blade.php` | ~30x (teks + Tailwind class) |
| 21 | `resources/views/livewire/components/layouts/guest.blade.php` | 9x meta + logo path |
| 22 | `resources/views/livewire/components/layouts/auth.blade.php` | Brand text |
| 23 | `resources/views/livewire/forms/auth/register.blade.php` | Logo path |
| 24 | `resources/views/livewire/reset-password.blade.php` | Tailwind class |
| 25 | `resources/views/donation-banner.blade.php` | Tailwind class + teks |
| 26 | `resources/views/filament/tenant/pages/update.blade.php` | Teks |
| 27 | `resources/views/filament/tenant/pages/cashier*.blade.php` | ~14 Tailwind class + 2 JS vars |
| 28 | `resources/views/filament/tenant/pages/pos/**/*.blade.php` | ~6 Tailwind class + 2 JS vars + 24 orange-500 |
| 29 | `resources/css/filament/tenant/theme.css` | 3 Tailwind class |
| 30 | `resources/js/app.js` | 2 JS var referensi |
| 31 | `README.md` | ~10x teks + URL |
| 32 | `AGENTS.md` | Referensi project |
| 33 | `.cursor/00-universal-agent-rules.mdc` | 4x teks |
| 34 | `docs/*.md` | ~55 file referensi |
| 35 | `public/assets/logo/image.png` | Ganti file |
| 36 | `public/favicon.ico` | Ganti file |
| 37 | `public/images/icons/*.png` | Ganti file (10+ icon) |
| 38 | `laradumps.yaml` | Path (minor) |

---

## Urutan Eksekusi

```
Prioritas  Fase                    Durasi     Risiko
─────────────────────────────────────────────────────────
🔥 1.      Tentukan warna baru     15 menit   🟢 (sebelum mulai)
🔥 2.      Fase 1 (Branding)       30 menit   🟢 Rendah
🔥 3.      Fase 2.3 (Tailwind)     20 menit   🟡 Sedang
🔥 4.      Fase 2.1-2.2 (Color)    20 menit   🟡 Sedang
🔥 5.      Fase 3 (JS vars)        10 menit   🟡 Sedang
🔥 6.      Fase 4 (Assets)         30 menit   🟢 Rendah
🔥 7.      Fase 6 (Docs)           25 menit   🟢 Rendah
🔥 8.      Fase 5 (Infra)          15 menit   🟡 Sedang
⏸️ 9.      Fase 0.3 (DB prefix)    Tunda      🔴 Tinggi
🔟         Fase 0.1 (DB rename)    10 menit   🔴 Tinggi
1️⃣1️⃣       Fase 0.2 (GitHub URL)   5 menit    🔴 Tinggi
           ──────────────────
           Total:                 ~3 jam
```

---

## Checklist Tracking

- [ ] **Pilih warna baru zonaKasir** (sebelum mulai coding)
- [ ] **Fase 1** — Teks branding: .env, Blade, email, demo, dll
- [ ] **Fase 2.1** — `#FF6600` di 3 lokasi code
- [ ] **Fase 2.2** — `orange-500/600` di POS v2 (24 lokasi)
- [ ] **Fase 2.3** — Tailwind key + 38 class di views
- [ ] **Fase 3** — JS window properties
- [ ] **Fase 4** — Logo, favicon, PWA icons (file fisik + referensi path)
- [ ] **Fase 5** — Docker, Lando, CI/CD
- [ ] **Fase 6** — Semua docs & README
- [ ] **Fase 7** — ⏭️ Composer package (skip)
- [ ] **Fase 0.3** — ⏸️ Tenant DB prefix (tunda)
- [ ] **Fase 0.1** — Rename database setelah migrasi
- [ ] **Fase 0.2** — Update GitHub URLs

---

*Dibuat: June 2026*
*Berdasarkan 2x scan: text grep + deep scan (warna, class, infra, assets).*
