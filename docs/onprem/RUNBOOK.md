# Runbook On-Premise — zonaKasir

> **Audience:** tim internal (sales & teknis). Buat jualan, pasang, pantau, update client on-prem.
> **Referensi:** `docs/planning/ONPREM_DEPLOYMENT_PLAN.md` (deploy), `docs/planning/ONPREM_LICENSE_PLAN.md` (license+monitoring).

---

## 1. Alur Jualan (Sales)

```
Prospek → Konsultasi (kebutuhan, jenis usaha) → Penawaran (paket+lisensi)
  → Deal → Order: install di server client → Generate license → Serah terima
  → Daftar di dashboard /admin → Heartbeat mulai → Support (opsional)
```

### Yang perlu disepakati sebelum install
- [ ] Nama customer (slug untuk lisensi)
- [ ] Domain / alamat akses (licence terikat ke domain ini)
- [ ] Durasi update+support (paket)
- [ ] Harga & metode pembayaran

---

## 2. Instalasi di Server Client

Prasyarat server client: **PHP ^8.4, PostgreSQL 15+, Composer, Node 18+**.

```bash
git clone <repo> /var/www/zonaKasir
cd /var/www/zonaKasir
composer install --no-dev --optimize-autoloader
cp .env.example .env
# edit .env: DB_*, APP_URL, ONPREM_MODE=true
#   MIDTRANS/FLIP/FIREBASE boleh kosong (tidak dipakai on-prem)
#   ONPREM_LICENSE_KEY=<isi dari langkah 3>
#   ONPREM_PUBLIC_KEY=<publik key kita>
#   ONPREM_HEARTBEAT_URL=<url pusat>/api/v1/onprem/heartbeat
#   ONPREM_HEARTBEAT_TOKEN=<shared secret>
php artisan key:generate

createdb zonakasir
php artisan migrate
php artisan migrate --path=database/migrations/tenant --seed
php artisan onprem:bootstrap        # tenant + owner + subscription permanen

php artisan filament:assets
php artisan livewire:publish --assets
npm ci && npm run build
```

> **JANGAN** pasang cron `billing:check`. `schedule:work` opsional (task lain).

---

## 3. Generate License (Sisi Pusat)

Pertama kali: generate keypair sekali, simpan **private key rahasia** (JANGAN commit):

```bash
php artisan onprem:keypair    # output public + private key
```

Per client:

```bash
php artisan onprem:license --customer="Toko Budi" --domain="tokobudi.zona.id" --days=365
# output: ZONA-TOKO-BUDI-4f3a...c21b
```

Kirim **lisensi + public key** ke tim install → masuk `.env` client (`ONPREM_LICENSE_KEY`, `ONPREM_PUBLIC_KEY`).

---

## 4. Verifikasi & Heartbeat

**Di client:**
```bash
php artisan onprem:verify        # validasi lokal — warning only, kasir tetap jalan
```
- `onprem:heartbeat` otomatis tiap 24 jam (silent fail, gak ganggu kasir)

**Di pusat (kamu):** dashboard `/admin` → **Onprem Instance**
- 🟢 Online (≤2 hari) · 🟡 Ragu (2–7 hari) · 🔴 Hilang (>7 hari)

**Kalau 🔴 hilang:** tanya client (server mati? domain pindah? server ganti?). Hukuman pelanggaran = stop update & support, **bukan** kunci kasir.

---

## 5. Update Client

1. `php artisan onprem:sign-update <file.zip>` (butuh `ONPREM_PRIVATE_KEY`)
2. Kirim ZIP bertanda tangan ke client
3. Client update via `app:update` (GitHub) atau manual — file unsigned/palsu **ditolak otomatis**

---

## 6. Perpanjangan Lisensi

`--days=365` default; sebelum expire, generate ulang dengan `--days=` baru (mis. +365). Kirim key baru, client ganti `.env`. Kasir tidak pernah mati walau lisensi lewat — hanya banner peringatan di dashboard client.

---

## 7. Checklist Serah Terima (Client)

- [ ] Login email/password owner (`onprem:bootstrap`) — Google login sengaja dimatikan di on-prem
- [ ] Kasir jalan, struk cetak oke
- [ ] Produk & stok terisi
- [ ] Lisensi valid (`onprem:verify`)
- [ ] Heartbeat pertama masuk (dashboard pusat 🟢)
- [ ] Training kasir (jika paket support)
- [ ] Backup DB pertama dijadwalkan (rekomendasi: pg_dump harian)

---

## 8. Harga & Paket (Template — sesuaikan)

| Paket | Isi | Harga |
|---|---|---|
| Basic | Lisensi 1 tahun + update 1 tahun | Rp ___ |
| Pro | Lisensi + update 3 tahun + prioritas support | Rp ___ |
| Support tahunan | Perpanjangan update + support | Rp ___/thn |
| Jasa install | Setup server + serah terima | Rp ___ |
| Training | Pelatihan kasir (per sesi) | Rp ___ |

---

## 9. Yang TIDAK Aktif di On-Prem (info ke client)

- Admin landlord `/admin` (kelola tenant/plan/invoice) — internal pusat
- Register self-serve + trial 7 hari + Google auto-provision
- Google login (pakai email/password manual)
- Midtrans (renewal) & Flip (withdrawal)
- Kupon perpanjangan trial
- Semua fitur kasir TETAP aktif penuh
