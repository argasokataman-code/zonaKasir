# PRD — zonaKasir untuk Niche Kafe & Restoran (F&B Bali)

| | |
|---|---|
| Status | Draft |
| Versi | 1.0 |
| Tanggal | 2026-08-16 |
| Scope | Fitur kafe/resto F&B: open bill, table management, split bill, multi-payment, KDS, X/Z report |
| Target segmen | Kafe & resto lokal Bali — 10–30 meja, banyak kursi, padat di peak hours, kasir gak ribet |
| Dasar | Riset pasar kafe Bali + gap analysis terhadap kode zonaKasir sekarang |

---

## 1. Ringkasan Eksekutif

zonaKasir saat ini adalah POS generic retail+F&B (warisan Lakasir). Fitur kafe yang ada cuma **label meja** (nomor tanpa status) — tidak ada konsep open bill, split bill, ataupun manajemen meja. Untuk bersaing di niche **kafe/resto F&B**, zonaKasir perlu fondasi operasional kafe yang benar.

Niche yang dipilih: **kafe kecil-menengah di Bali** yang punya banyak kursi & meja, ingin operasional cepat, dan "gak ribet" — berlawanan dengan kompetitor enterprise (ESB, Oracle) yang kompleks, dan SaaS besar (Moka, Pawoon, Kaching) yang mahal.

**Keputusan kunci PRD ini:** 4 fondasi teknis (multi-payment, stock lock, idempotency, table status+recovery) harus didesain benar sejak awal. Salah desain = bug uang/stok yang fatal di produksi.

**Diferensiasi utama (baru):** zonaKasir bukan cuma POS operasional — ia **mengubah data penjualan jadi konten marketing otomatis** (struk digital shareable, best-seller auto-post, grafik jam sepi/ramai, auto-caption, QR menu + Menu Hits, kartu member). Media sosial sekarang mengangkat toko; zonaKasir memberi kafe bahan konten gratis dari data yang sudah dikumpulkan. Ini yang kompetitor (Moka/Pawoon/Kaching) tidak punya.

**Diferensiasi kedua — On-Premise:** zonaKasir bisa dijual onprem (server di dalam kafe, jalan offline penuh utk kasir). Pembeda dari kompetitor yang cloud-only. **Jujur soal internet:** fitur marketing (struk digital shareable, auto-post, dll) **butuh internet** — tidak diklaim offline. Maintenance server client bisa **di-manage oleh kami dengan charge tambahan**. License + heartbeat + update signed sudah implemented.

**Timeline:** 6 milestone, ~7–9 minggu kerja 1 dev. M1 (fondasi uang/stok aman) = gate kritis wajib lulus sebelum lanjut. Kafe siap jual pilot di M4 (~5–6 minggu), onprem komersial di M6 (~2 bulan).

---

## 2. Persona & User Stories

### 2.1 Persona

| Persona | Peran | Kebutuhan inti |
|---|---|---|
| **Owner** (Pak Ketut) | Pemilik kafe, 2-3 outlet | Lihat laporan real-time dari HP, tahu menu terlaris, cek selisih kas per shift, gak mau bocor |
| **Kasir** (Wayan) | Pegawai depan, kadang rangkap waiter | Proses bayar cepat saat antre, QRIS lancar, struk auto, gak ribet |
| **Waiter** (Gede) | Catat pesanan per meja, antar order ke dapur | Pesan cepat per meja, tambah order tanpa bikin bill baru, status meja jelas |
| **Dapur/Barista** (Putu) | Terima order dari depan | Lihat order masuk jelas, tandai selesai, tanpa berteriak |

### 2.2 User Stories

- **US-01 (Owner):** "Sebagai owner, aku bisa lihat penjualan per hari, per metode bayar, dan menu terlaris dari HP, sehingga aku tahu performa kafe tanpa buka laptop."
- **US-02 (Owner):** "Sebagai owner, aku bisa lihat selisih kas per shift per kasir, sehingga aku tahu kalau ada kebocoran."
- **US-03 (Kasir):** "Sebagai kasir, aku bisa proses pembayaran cash+QRIS campur dalam satu tagihan, sehingga tamu yang bayar setengah tunai tetap cepat."
- **US-04 (Kasir):** "Sebagai kasir, aku bisa pecah tagihan (split bill) untuk tamu yang mau bayar sendiri-sendiri."
- **US-05 (Waiter):** "Sebagai waiter, aku bisa buka bill di meja, tambah pesanan sebelum bayar, dan pindah meja kalau tamu pindah."
- **US-06 (Waiter):** "Sebagai waiter, aku bisa melihat meja mana yang kosong/terisi dari satu layar, sehingga gak perlu keliling cek meja."
- **US-07 (Dapur):** "Sebagai barista, aku bisa lihat order masuk di layar dapur, tandai selesai, sehingga gak ada order kelewat."
- **US-08 (Kasir):** "Sebagai kasir, kalau jaringan putus, aku tetap bisa kasih transaksi dan ter-sync otomatis saat online."
- **US-09 (Owner):** "Sebagai owner, aku bisa share struk digital ke pelanggan setelah bayar, sehingga tiap transaksi jadi promosi gratis di media sosial."
- **US-10 (Owner):** "Sebagai owner, aku bisa dapat konten 'menu terlaris hari ini' siap-post, sehingga kafe kami aktif di IG tanpa repot bikin konten."
- **US-11 (Owner):** "Sebagai owner, aku bisa lihat jam sepi/ramai dari data penjualan, sehingga aku promosi di jam sepi biar gak nganggur."
- **US-12 (Kasir):** "Sebagai kasir, aku bisa print QR menu yang nunjukin menu hits, sehingga tamu gampang milih dan pesan lebih banyak."

---

## 3. Flow End-to-End (Target)

```
1. Tamu duduk di meja (denah meja tampil kosong/terisi)
2. Waiter pilih meja → buka bill → tambah item pesanan
3. Order masuk KDS (layar dapur) → barista tandai selesai
4. Tamu tambah pesanan → waiter tambah item ke bill yang SAMA
5. Tamu minta bayar → kasir: split bill / bayar campur cash+QRIS
6. Struk thermal print + struk digital (opsional)
7. Kasir tutup shift → X/Z report, cek selisih kas
8. Owner lihat laporan real-time dari HP
```

### 3.1 Alur per status bill

| Status | Deskripsi | Boleh apa |
|---|---|---|
| `open` | Bill dibuat, belum ada pembayaran | Tambah item, pindah meja, hold, cancel |
| `partially_paid` | Sebagian sudah dibayar (DP / split belum lunas) | Tambah pembayaran, cancel (dengan refund track) |
| `paid` | Lunas | Hanya view + refund (jika didukung) |
| `cancelled` | Dibatalkan / auto-release | Tidak ada operasi |

---

## 4. Requirement Fungsional (detail)

### 4.1 Open Bill (fondasi)

**FR-1.1** Kasir/waiter bisa buka bill baru dengan memilih meja (wajib untuk F&B dine-in). System generate `selling.status = 'open'`.

**FR-1.2** Satu meja hanya boleh punya **satu bill aktif** (`open` atau `partially_paid`). Constraint di level DB (partial unique index), bukan hanya validasi app.

**FR-1.3** Item bisa ditambahkan ke bill yang masih `open`/`partially_paid`. Setiap tambahan item menambah `total_price` bill, tidak membuat selling baru.

**FR-1.4** Bill bisa di-hold (ditinggalkan sementara) tanpa bayar. Meja tetap tercatat terisi.

**FR-1.5** Bill bisa di-cancel manual (tamu walk-out / salah buka). Cancel mencatat alasan + user, dan **stock yang sudah ter-reduce dikembalikan** (reverse).

**FR-1.6** Bill idle otomatis di-cancel (auto-release meja) setelah batas waktu konfigurasi (default 12 jam).

### 4.2 Table Management (denah & status)

**FR-2.1** Status meja **diturunkan (derived)** dari data selling, tidak disimpan sebagai kolom — menghindari desync.

| Status | Definisi (query) |
|---|---|
| Kosong | Tidak ada selling `open`/`partially_paid` dengan `table_id` = meja tsb |
| Terisi | Ada selling `open`/`partially_paid` |

**FR-2.2** Tampilan denah meja (grid) yang menunjukkan status kosong/terisi warna-warni, klik meja → buka bill.

**FR-2.3** Meja punya atribut tambahan: `name/number`, `capacity` (jumlah kursi), `location/zone` (indoor/outdoor), `sort_order`. (Meng-upgrade `Table` model yang sekarang cuma `number`.)

**FR-2.4** Pindah meja: memindahkan bill dari meja A ke meja B. Meja A otomatis jadi kosong, meja B otomatis terisi (transaksional).

**FR-2.5** Gabung meja (merge): menggabungkan bill meja A + B menjadi satu tagihan di meja A. Meja B kosong. (Batch besar / acara.)

**FR-2.6** Meja tidak bisa dihapus jika ada bill aktif yang mengacu padanya.

### 4.3 Split Bill

**FR-3.1** Satu bill bisa dipecah menjadi beberapa bagian tagihan, masing-masing dengan subset item.

**FR-3.2** Setiap bagian bisa dibayar **independen** (metode bayar berbeda per bagian).

**FR-3.3** Validasi keras: **jumlah total semua bagian wajib sama dengan total bill** (selisih < Rp 1 toleransi pembulatan). Selisih eksplisit disimpan agar audit jelas.

**FR-3.4** Item qty pecahan diperbolehkan di F&B (1 porsi dibagi 2) — qty `double` dengan presisi.

**FR-3.5** Diskon, pajak, dan voucher dihitung **per bagian** (proporsional terhadap subtotal bagian), bukan dihitung di total lalu dipecah — agar adil dan audit-able.

**FR-3.6** Status per bagian: `pending` / `paid`. Bill lunas ketika semua bagian lunas.

**FR-3.7** Refund / pembatalan porsi yang sudah dibayar tercatat ke bagian asal (harus ada payment tracking per bagian).

### 4.4 Multi-Payment (per transaksi)

**FR-4.1** Satu bill bisa menerima **banyak pembayaran** (contoh: cash Rp 50.000 + QRIS Rp 30.000 untuk total Rp 80.000).

**FR-4.2** Setiap pembayaran dicatat sebagai baris di `selling_payments` (bukan 1 kolom di selling).

**FR-4.3** Over-payment dilarang: `total_paid > total_price` ditolak (kecuali mode deposit/tip dikonfigurasi eksplisit).

**FR-4.4** Pembayaran QRIS/online lewat Midtrans Snap; cash langsung; kartu tercatat manual (EDC eksternal) sebagai `is_cash=false`.

**FR-4.5** Status pembayaran: `pending` → `success`/`failed`/`expired`. Hanya `success` yang dihitung sebagai pelunasan.

### 4.5 Kitchen Display System (KDS) — fase 2

**FR-5.1** Order baru (per item atau per bill) muncul di layar dapur terpisah.

**FR-5.2** Barista/chef menandai item `in-progress` → `done`. Status bisa dilihat dari layar kasir/waiter.

**FR-5.3** Tambahan item pada bill yang sudah dikirim ke dapur → item baru masuk KDS, tidak mengubah status item lama.

**FR-5.4** Pembatalan item yang sudah di-KDS menampilkan peringatan di layar dapur.

**FR-5.5** KDS punya nada/notifikasi visual saat order masuk (peak hours).

### 4.6 X/Z Report & Shift — fase 2

**FR-6.1** Kasir membuka shift (open cash drawer) dan menutup shift (tutup kas).

**FR-6.2** X-report: laporan sementara tanpa menutup shift (transaksi selama shift).

**FR-6.3** Z-report: laporan final saat tutup shift + mencatat **selisih kas** (expected cash vs actual cash yang diinput).

**FR-6.4** Report per kasir per shift: total penjualan, per metode bayar, jumlah transaksi, selisih.

**FR-6.5** Shift otomatis ditutup jika melewati batas waktu (mis. pukul 06:00) dengan peringatan.

**FR-6.6** Transaksi yang dibuat setelah shift "ditutup otomatis" masuk ke shift baru.

### 4.7 PWA / Offline (non-regresi, verifikasi)

**FR-7.1** Transaksi offline (cash-only) tetap bisa dibuat saat jaringan putus, tersimpan lokal, ter-sync saat online. **Perlu diverifikasi anti-duplikat setelah idempotency key diterapkan.**

### 4.8 Marketing Otomatis dari Data POS (diferensiasi utama)

> Visi: **"bukan cuma kasir, tapi yang bikin kafe naik."** Semua fitur ini generate dari data yang SUDAH dikumpulkan POS (penjualan, menu terlaris, jam ramai, member) — tanpa beban kerja tambahan buat kafe. Kompetitor (Moka/Pawoon/Kaching) fokus operasional, mereka gak mikir marketing — ini jadi pembeda kita.

**FR-8.1 — Struk digital shareable (prioritas tertinggi)**
Setiap transaksi selesai → generate link/QR struk digital → pelanggan bisa buka + share ke IG Story ("Baru aja di sini"). **Setiap transaksi = 1 potensi share gratis.** Struk digital berisi: nama kafe, item, total, link profile IG kafe, QR follow.

**FR-8.2 — "Best Seller Hari Ini" auto-post**
Dari data `TodaysBestSellingProduct` (sudah ada di dashboard) → generate kartu gambar + caption siap-pakai → kasir tinggal simpan/screenshot buat posting. Nol effort, konten harian otomatis.

**FR-8.3 — Grafik "Jam Sepi / Jam Ramai"**
Dari data transaksi per jam → visual "jam berapa lagi santai / jam berapa rame" → kafe share untuk menarik pengunjung di jam sepi (promosi off-peak).

**FR-8.4 — Auto-caption copywriting**
Generate caption siap-tempel dari data real tiap hari (contoh: "Menu yang paling laku hari ini: 32 cappuccino!"). Template caption + data real tersedia di satu tempat, kasir tinggal salin.

**FR-8.5 — QR menu digital + badge "Menu Hits"**
Table QR → menu online. Item dengan badge "🔥 Favorit" (dari `selling_details` frekuensi). QRIS scan → langsung buka menu, bukan cuma bayar. Menu selalu update dari data penjualan real.

**FR-8.6 — Kartu "Terima kasih" member**
Dari data member + frekuensi kunjungan → kartu apresiasi ("Sudah 10x ke sini bulan ini, balik lagi ya!"). Build loyalitas tanpa program loyalty kompleks.

| Fitur | Sumber data existing | Effort | Impact |
|---|---|---|---|
| Struk digital shareable | `sellings` + `selling_details` | 🟡 sedang | 🔥🔥🔥 |
| Best seller auto-post | `TodaysBestSellingProduct` | 🟢 kecil | 🔥🔥 |
| Grafik jam sepi/ramai | `sellings.date` | 🟢 kecil | 🔥 |
| Auto-caption | data penjualan | 🟢 kecil | 🔥 |
| QR menu + Menu Hits | `selling_details` | 🟡 sedang | 🔥🔥 |
| Kartu member | `members` + `sellings` | 🟡 sedang | 🔥 |

**Non-goals (jangan dibangun sekarang):** upload otomatis ke IG/TikTok via API (rate limit + approval risk), scheduling post otomatis (bikin ketergantungan), AI-generated konten (mahal + gak stabil). Fokus: **generate materi siap-pakai**, publish manual kasir.

### 4.9 On-Premise (paket kafe) — pembeda kedua

> Status existing: license per domain (RSA), heartbeat monitoring, update signed — **sudah implemented** (`docs/onprem/RUNBOOK.md`, `ONPREM_LICENSE_PLAN.md`). Yang di bawah = packaging untuk niche kafe + kejelasan offline/internet + model maintenance.

**FR-9.1 — Onprem jalan offline (core kasir)**
Kasir, produk, stok, open bill, split bill, struk, laporan — **jalan penuh tanpa internet** (server di dalam kafe, LAN). Ini argumen jual terkuat utk kafe Bali (koneksi kadang jelek).

**FR-9.2 — Fitur marketing butuh internet (jelas sejak awal)**
Fitur section 4.8 (struk digital shareable, best-seller auto-post, grafik jam ramai, QR menu, auto-caption, kartu member) **memerlukan koneksi internet** karena: generate link/QR publik, akses data real-time, share ke IG. **Jangan diklaim offline** — di materi jualan & UI wajib ada keterangan "fitur ini butuh internet".

| Mode | Yang jalan | Yang butuh internet |
|---|---|---|
| **Onprem + internet** | Semua (kasir + marketing) | — |
| **Onprem offline** | Kasir, meja, split bill, struk, laporan | Struk digital shareable, best-seller auto-post, QR menu, grafik jam ramai, auto-caption |

**FR-9.3 — Maintenance server (layanan berbayar opsional)**
Client onprem bisa pilih:
- **Self-managed:** client urus sendiri server-nya (update via `app:update`, backup sendiri)
- **Managed by kami (+charge):** kami yang maintain server client — update, backup otomatis, monitoring heartbeat, troubleshooting remote. **Charge tambahan** (mis. Rp 250–500 rb/bln tergantung paket).

**FR-9.4 — Remote assist**
Untuk troubleshooting tanpa dateng ke lokasi: tunnel sederhana (tailscale / reverse tunnel) — session dibuka atas persetujuan client, untuk managed support.

**FR-9.5 — Backup otomatis client**
Cron `pg_dump` harian ke disk lokal (self-managed) atau ke tempat kami (managed). Kafe gak mau mikirin ini — harus default jalan.

**FR-9.6 — Paket onprem kafe (usulan)**

| Paket | Isi | Cocok utk |
|---|---|---|
| **Kafe Lite** | 1 server, 3 user, update 1 thn, fitur kafe dasar (open bill, table, struk) | Kafe kecil, 1-2 kasir |
| **Kafe Pro** | 1 server, 10 user, update 3 thn, + split bill, KDS, X/Z report (fitur kafe lengkap) | Kafe rame, banyak meja, multi-staff |

Fitur kafe di-gate paket: **Lite = open bill + table + struk; Pro = split bill + KDS + shift/X/Z**. Ini bikin Pro jelas nilainya (bukan cuma beda jumlah user).

**FR-9.7 — Pricing onprem kafe (usulan, belum disepakati)**

| Paket | Sekali bayar | + Managed/update |
|---|---|---|
| Kafe Lite | Rp 1.5–2 jt | update 1 thn termasuk; managed +Rp 250 rb/bln |
| Kafe Pro | Rp 3–4 jt | update 3 thn termasuk; managed +Rp 500 rb/bln |

Efektif: Lite 2 jt ÷ 24 bln ≈ Rp 83 rb/bln (di bawah Simplus Rp 149 rb/bln). Pro 3.5 jt ÷ 36 bln ≈ Rp 97 rb/bln. Balik modal cepat utk kafe berencana 2+ tahun.

**FR-9.8 — Segmentasi jual (anti kanibal)**

| Kondisi kafe | Rekomendasi |
|---|---|
| 1 tablet, 1 kasir, gak ada PC, gak mau urus server | **SaaS** (langganan) |
| Punya PC/server bekas, 2+ device, komplain internet/langganan | **Onprem** |
| 2+ outlet, butuh data gabungan pusat | **SaaS** (cloud) |

**FR-9.9 — Risiko onprem kafe**

| Risiko | Mitigasi |
|---|---|
| Kafe gak bisa update sendiri | `app:update` sekali-klik; paket update tahunan; managed option |
| Server client rusak / data hilang | Backup otomatis harian + RUNBOOK recovery |
| Support membebani tim kecil | Paket support berbayar, SLA jelas |
| License dipakai di banyak kafe (bajak) | License per domain + heartbeat detect anomaly |
| SaaS vs onprem kanibal | Segmentasi FR-9.8 |

---

## 5. Requirement Non-Fungsional

| Kode | Kebutuhan | Detail |
|---|---|---|
| NFR-1 | **Integritas data uang** | Tidak boleh ada transaksi dobel, stok minus, atau overpay dalam kondisi apapun, termasuk race condition |
| NFR-2 | **Atomicity** | Setiap operasi yang menyentuh stok + selling + payment dalam 1 `DB::transaction` |
| NFR-3 | **Idempotency** | Submit & webhook aman dari retry ganda (unique key) |
| NFR-4 | **Performance peak hours** | Input order < 1 detik, submit payment < 1.5 detik pada 30 kasir aktif (Laravel + Postgres) |
| NFR-5 | **Multi-device** | Waiter tablet + kasir PC + KDS dapat bekerja bersamaan pada bill yang sama (last-write-wins dengan konflik yang dikelola) |
| NFR-6 | **Audit trail** | Setiap cancel/refund/force-close mencatat user + timestamp + alasan |
| NFR-7 | **Offline resilience** | Mode offline tidak menghilangkan data; sync tanpa duplikat. **Catatan:** fitur marketing (4.8) butuh internet — bukan bagian offline |
| NFR-8 | **Security** | Webhook divalidasi signature; idempotency key tidak bisa ditebak (UUID) |
| NFR-9 | **Timezone** | Semua timestamp UTC di DB, tampil lokal sesuai profil tenant |
| NFR-10 | **Multi-tenant** | Semua tabel baru ikut konvensi tenant (kolom `tenant_id`, query scope) |
| NFR-11 | **Onprem offline core** | Kasir/meja/split/struk/laporan jalan tanpa internet; fitur marketing butuh internet (FR-9.2) |

---

## 6. Skenario Negatif, Edge Case & Race Condition (detail)

> Daftar ini wajib jadi basis **test cases** dan **acceptance criteria**. Setiap item harus punya test yang memastikan perilaku benar. Total: **79 skenario** (RC 1–15, TB 1–14, SB 1–14, PM 1–12, SH 1–6, PR 1–6, ST 1–3, NC 1–2, VO 1–2, AU 1–3, MB 1–2).

### 6.1 Race Condition — order/payment barengan

| ID | Skenario | Dampak jika salah | Strategi |
|---|---|---|---|
| RC-1 | 2 waiter buka bill di meja yang sama bersamaan | 2 bill aktif 1 meja → tamu kena 2x | Partial unique index `(table_id) WHERE status IN ('open','partially_paid')` |
| RC-2 | 2 kasir jual produk stok terakhir bersamaan | **Oversell, stok minus** | `SELECT ... FOR UPDATE` pada produk dalam 1 transaction; validasi stok di dalam lock |
| RC-3 | 2 device submit pembayaran bill sama bersamaan | Double charge | `lockForUpdate()` pada selling; verifikasi belum lunas sebelum insert payment |
| RC-4 | Retry submit (jaringan putus, user klik 2x) | Selling + stok dobel | `idempotency_key` unique (UUID dari cart) |
| RC-5 | Scanner barcode dobel-scan (double trigger) | Qty dobel | Debounce scanner (min interval per input, mis. 500ms) |
| RC-6 | Kasir A logout di tengah, kasir B login device sama | Cart A nyangkut | Cart di-clear saat logout; validasi cart milik user aktif |
| RC-7 | Dua kasir cetak struk bill yang sama | Struk dobel, buang kertas | Lock print per bill + tombol disabled selama print |
| RC-8 | Waiter tambah item ke bill yang SEDANG dibayar kasir | Total berubah setelah payment → selisih | Lock bill saat payment; tambah item ditolak jika bill `paid`/sedang pay |
| RC-9 | 2 waiter tambah item ke bill yang SAMA bersamaan | Lost update: 1 item hilang | `lockForUpdate` bill saat append item; retry otomatis |
| RC-10 | Split bill sedang diproses + tambah item bersamaan | Bagian tidak sinkron dengan total | Lock bill selama operasi split; append item menunggu |
| RC-11 | Cancel bill vs bayar bill bersamaan | Payment masuk bill yang sudah di-cancel | Lock bill; jika sudah `cancelled` → payment ditolak |
| RC-12 | Pindah meja vs buka bill meja tujuan bersamaan | 2 bill di meja tujuan | Lock baris meja tujuan saat pindah (serialisasi) |
| RC-13 | Idempotency key sama dipakai lintas tenant | Cross-tenant data bocor | Unique key di-scope per tenant: `(tenant_id, idempotency_key)` |
| RC-14 | 2 transaksi lock produk dalam urutan berbeda | Deadlock | Urutan lock konsisten (by product id asc) |
| RC-15 | Submit payment + add item bill bersamaan, keduanya retry | Kondisi double-submit compound | Idempotency per operasi (submit = cart_uuid, add = bill+item_uuid) |

### 6.2 Open bill & meja

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| TB-1 | Tamu walk-out tanpa bayar | Meja terkunci selamanya | Auto-release meja idle (timer) + tombol "force close / meja kosong" |
| TB-2 | Tamu pindah meja, waiter lupa update | Order di meja salah | Fitur pindah meja (order ikut); meja lama auto-jadi kosong (transaksional) |
| TB-3 | Rombongan gabung meja | 2 meja, 1 tagihan — status meja ganda | Merge bill: meja kedua di-mark kosong, order pindah |
| TB-4 | Waiter salah pilih meja saat buka bill | Order di meja salah, tamu kebingungan | Konfirmasi meja di struk/KDS + bisa pindah meja sebelum bayar |
| TB-5 | Hapus meja padahal ada bill aktif | Order yatim, meja hilang | Blokir hapus meja kalau ada bill aktif (constraint/validasi) |
| TB-6 | Server restart di tengah open bill | Status meja nggantung | Recovery on boot: bill open stale > timeout → auto-cancel |
| TB-7 | Hold bill lupa di-resume | Meja ke-lock | Daftar "bill tertahan" + reminder; meja idle timer |
| TB-8 | Bill dibuat **tanpa meja** (takeaway) | Meja virtual abal-abal muncul | `table_id` nullable; meja tak terpengaruh bill takeaway |
| TB-9 | Kapasitas meja 4 diisi 6 tamu | Meja sesak, layanan lambat | Warning saat pilih meja (optional, bukan blokir) |
| TB-10 | Meja di-reserve (dipesan) tapi belum ada bill | Bill tidak tahu meja di-reserve | Kolom/status `reserved` pada meja (derived dari booking) |
| TB-11 | Crash di tengah merge bill | Status meja setengah-pindah | Merge bill dalam 1 `DB::transaction` (atomic) |
| TB-12 | Nomor meja duplikat (2 meja "Meja 1") | Waiter/kasir salah pilih | Unique constraint `(tenant_id, name)` |
| TB-13 | Pindah meja saat bill sudah `partially_paid` | Payment nyangkut di meja lama | Pindah meja hanya update `table_id`; payment tetap ikut bill |
| TB-14 | Bill open di meja, meja diubah zone/lokasi | Laporan zone jadi salah | `table_id` referensial; zone dibaca saat query (bukan di-snapshot) |

### 6.3 Split bill

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| SB-1 | Jumlah split ≠ total (pembulatan) | Kas bocor / gak balance | Validasi split: total split wajib = total tagihan (atau simpan selisih eksplisit) |
| SB-2 | Item qty pecahan (1 porsi dibagi 2) | Qty desimal gak bisa | Izinkan qty desimal di F&B, atau item ditambah 2x |
| SB-3 | Diskon/voucher/tax dibagi proporsional | Perhitungan gak adil | Tax/diskon dihitung per-split, bukan total lalu dipecah |
| SB-4 | 1 split bayar cash, sisanya QRIS | 2 metode 1 bill | Selling harus dukung multi-payment (`selling_payments`) |
| SB-5 | Satu orang bayar, sisanya hold | Partial paid | Status bill `partially_paid` + list sisa bayar per bagian |
| SB-6 | Refund setelah split | Balik ke siapa? | Refund tercatat ke split asal (payment tracking per bagian) |
| SB-7 | Bagian bernilai 0 (dibayarin teman) | Split kosong | Tolak split 0 / izinkan dengan label (keputusan konfigurasi) |
| SB-8 | Split saat bill masih `open` (belum bayar) | Bagian tak jelas | Diizinkan; bagian dipakai untuk membayar bertahap |
| SB-9 | Split dibuat, **lalu item DITAMBAH** ke bill | Item baru masuk bagian mana? | Aturan: item baru masuk ke bagian aktif terakhir / wajib pilih bagian |
| SB-10 | Item yang sama dipecah qty (1.5 + 0.5) dengan harga beda (promo/varian) | Harga tidak konsisten | Snapshot harga per `selling_detail`; qty dihitung per bagian |
| SB-11 | Refund sebagian setelah split lunas | Sisa dana milik siapa | Refund proporsional ke bagian; sisa tercatat eksplisit |
| SB-12 | Tax dihitung 2x saat split (double tax bug) | Tamu kena tax ganda | Hitung tax per bagian dari subtotal bagian, verifikasi total = tagihan |
| SB-13 | Voucher hanya berlaku 1x tapi item terbagi ke beberapa bagian | Voucher dipakai berulang | Voucher diikat ke bill (1x), distribusi proporsional ke bagian |
| SB-14 | Pembulatan 2 bagian (selisih Rp 50) | Tidak ada yang mau nanggung | Selisih pembulatan dilempar ke bagian terakhir / dicatat rounding |

### 6.4 Payment & webhook

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| PM-1 | Callback QRIS telat (delay) | Kasir tunggu, status pending | Timeout di UI + polling status; status jelas "menunggu" |
| PM-2 | Callback Midtrans datang 2x | Stok/selling dobel | Idempotent webhook handler (cek `midtrans_ref` sudah ada) |
| PM-3 | Callback sukses TAPI app timeout | Dana masuk, selling belum dibuat | Reconciliation: cari payment yatim vs order |
| PM-4 | Kasir sudah pegang cash tapi belum submit | Meja gak kebayar | Bill tetap `open`; meja tetap terisi; no stale state |
| PM-5 | Overpay (bayar lebih dari tagihan) | Kas bocor / dana tak jelas | Ditolak (kecuali tip mode eksplisit) |
| PM-6 | Payment method di-deactivate saat ada bill pending | Bill existing rusak | Payment method soft-delete; record lama tetap valid |
| PM-7 | Webhook sukses tapi update status selling gagal (crash antara) | Bill `partially_paid` padahal dana masuk | Reconciliation job + retry update status |
| PM-8 | Payment method dihapus total (hard delete) | History payment putus | Soft delete / `ON DELETE SET NULL` |
| PM-9 | Nilai desimal (Rp 1.000,5) | Selisih pembulatan | Bulatkan ke rupiah penuh; aturan rounding konsisten (banker's/floor) |
| PM-10 | Token Midtrans expired, kasir ulang | Dobel charge kalau token lama masih hidup | Snap token baru; jangan re-pay token lama |
| PM-11 | Tamu bayar lebih dari tagihan (uang pas Rp 100rb utk tagihan Rp 95rb) | Salah jadi overpay | Flag `is_tip`/catatan kelebihan; bukan overpay error |
| PM-12 | Refund full setelah bill `paid` | Stok & status bill | Bill balik `partially_paid`/`open`; stok dikembalikan |

### 6.5 Shift & laporan

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| SH-1 | Kasir buka shift, lupa tutup | Semua transaksi masuk shift yang sama | Auto-close shift harian + warning |
| SH-2 | Tutup shift di tengah transaksi | Selling baru masuk shift salah | Kunci shift saat ada bill `open` / tandai waktu submit |
| SH-3 | Selisih cash drawer tidak balance | Owner curiga bocor | Report selisih per kasir per shift (X/Z) + catatan koreksi |
| SH-4 | 2 device buka shift sama bersamaan | Shift ganda, laporan terpecah | Unique active shift per kasir/device |
| SH-5 | Transfer antar kasir (serah terima) | Selisih tak bisa dianalisis | Catat transfer + session handover |
| SH-6 | Transaksi tepat jam 00:00 | Masuk shift yang mana? | Aturan: shift mengikuti waktu submit, auto-close boundary |

### 6.6 Printer & offline

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| PR-1 | WebUSB printer tidak terhubung (bukan Chrome) | Gak bisa struk | Fallback struk PDF (invoice A5 sudah ada) |
| PR-2 | Offline pending sale, device mati sebelum sync | Transaksi hilang | Simpan pending sale persist lokal; sync ulang on reconnect, idempotent |
| PR-3 | Struk print dobel (klik 2x) | Buang kertas | Disable tombol saat print in-progress |
| PR-4 | Printer config di localStorage, ganti browser/device | Config hilang | Config printer tersimpan per tenant (DB), bukan hanya localStorage |
| PR-5 | Struk print bill yang sedang di-update (item baru ditambah) | Struk isi lama | Snapshot struk saat print; label "struk ulang" |
| PR-6 | Browser cache dibersihkan user | Printer config hilang | Sama PR-4: simpan di DB |

### 6.7 Stok & produk (kafe khusus)

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| ST-1 | Stock opname berjalan BERSAMAAN dengan penjualan | Selisih stok tak terjelaskan | Opname lock per produk; selisih dicatat timestamp |
| ST-2 | Item di bill open mengalami kedaluwarsa (expired) sebelum bayar | Tamu dapat produk basi | Validasi expired saat submit bayar; item di-flag |
| ST-3 | Produk dihapus/disabled padahal masih di bill open | Bill gak bisa diproses | Soft delete; bill open masih bisa bayar item yang ada |

### 6.8 Kode transaksi & voucher

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| NC-1 | 2 transaksi bersamaan generate kode `SELL` (sekarang pakai `Selling::count()`) | **Kode duplikat** | Ganti ke sequence/DB auto-increment atau `lockForUpdate` saat generate kode |
| VO-1 | Voucher quota dipakai 2 bill bersamaan | Kuota bocor | `lockForUpdate` pada voucher saat claim |
| VO-2 | Voucher kedaluwarsa di tengah bill open | Diskon berubah saat bayar | Snapshot diskon voucher saat bill dibuat / validasi saat bayar |

### 6.9 Audit & cancel/refund

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| AU-1 | Bill di-cancel padahal sudah ada payment success | Dana hilang / tak terlacak | Auto-jadi refund; tercatat ke payment asal |
| AU-2 | 2 kasir cancel bill yang sama bersamaan | Stok dikembalikan 2x (over-restock) | `lockForUpdate` pada selling saat cancel; status check `cancelled` |
| AU-3 | Timezone tenant berubah di tengah hari | Laporan shift kacau | Semua timestamp UTC; shift boundary pakai zona tenant |

### 6.10 Member & piutang (F&B)

| ID | Skenario | Dampak jika salah | Mitigasi |
|---|---|---|---|
| MB-1 | Credit (piutang) dipakai + split bill | Receivable jadi berantakan | Credit hanya di bagian tunggal / receivable per bagian |
| MB-2 | Member dihapus padahal ada bill open | Bill kehilangan relasi | Soft delete member; bill open tetap jalan |

---

## 7. Desain Teknis

### 7.1 Skema Database

**Tabel baru: `selling_payments`**
```sql
CREATE TABLE selling_payments (
    id                BIGSERIAL PRIMARY KEY,
    tenant_id         UUID NOT NULL,
    selling_id        BIGINT NOT NULL REFERENCES sellings(id),
    payment_method_id BIGINT NULL REFERENCES payment_methods(id),
    amount            DOUBLE PRECISION NOT NULL,
    is_cash           BOOLEAN NOT NULL DEFAULT true,
    status            VARCHAR(20) NOT NULL DEFAULT 'pending'
                      CHECK (status IN ('pending','success','failed','expired')),
    idempotency_key   UUID NOT NULL,
    midtrans_ref      VARCHAR(64) NULL,
    payment_date      TIMESTAMP NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    CONSTRAINT selling_payments_idempotency_unique UNIQUE (idempotency_key),
    CONSTRAINT selling_payments_midtrans_ref_unique UNIQUE (midtrans_ref)
);
CREATE INDEX selling_payments_selling_idx ON selling_payments (selling_id);
CREATE INDEX selling_payments_tenant_idx ON selling_payments (tenant_id);
```

**Alter `sellings`**
```sql
ALTER TABLE sellings
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'paid'
        CHECK (status IN ('open','partially_paid','paid','cancelled')),
    ADD COLUMN cart_uuid UUID NULL,          -- idempotency sumber
    ADD COLUMN cancelled_by BIGINT NULL,     -- audit
    ADD COLUMN cancelled_at TIMESTAMP NULL,
    ADD COLUMN cancel_reason VARCHAR(255) NULL,
    ALTER COLUMN payment_method_id DROP NOT NULL;  -- jadi nullable (multi-payment)

-- Anti 2 bill aktif 1 meja
CREATE UNIQUE INDEX selling_one_open_per_table
    ON sellings (tenant_id, table_id)
    WHERE status IN ('open','partially_paid')
    AND table_id IS NOT NULL;
```

> Catatan migration: `is_paid` lama dipertahankan utk backward compat; nilai baru dihitung dari `status` dan `selling_payments`. Data existing dimigrasi: `is_paid=true` → `status='paid'`, `is_paid=false` → `status='cancelled'` (karena tanpa `status` sebelumnya = transaksi final).

**Alter `tables`** (meja)
```sql
ALTER TABLE tables
    ADD COLUMN capacity INT NULL,
    ADD COLUMN zone VARCHAR(50) NULL,        -- indoor/outdoor
    ADD COLUMN sort_order INT DEFAULT 0;
```

**Alter `selling_details`**
```sql
-- split bill per bagian butuh relasi bagian
CREATE TABLE selling_split_groups (
    id             BIGSERIAL PRIMARY KEY,
    tenant_id      UUID NOT NULL,
    selling_id     BIGINT NOT NULL REFERENCES sellings(id),
    label          VARCHAR(50) NULL,         -- "Bagian 1", "Tamu A"
    status         VARCHAR(20) NOT NULL DEFAULT 'pending'
                   CHECK (status IN ('pending','paid')),
    subtotal       DOUBLE PRECISION NOT NULL,
    tax_amount     DOUBLE PRECISION NOT NULL DEFAULT 0,
    discount_amount DOUBLE PRECISION NOT NULL DEFAULT 0,
    total          DOUBLE PRECISION NOT NULL,
    paid_total     DOUBLE PRECISION NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NULL, updated_at TIMESTAMP NULL
);
CREATE INDEX selling_split_groups_selling_idx ON selling_split_groups (selling_id);

-- item masuk ke bagian tertentu
ALTER TABLE selling_details ADD COLUMN split_group_id BIGINT NULL REFERENCES selling_split_groups(id);
-- payment terhubung ke bagian (untuk refund track)
ALTER TABLE selling_payments ADD COLUMN split_group_id BIGINT NULL REFERENCES selling_split_groups(id);
```

### 7.2 Locking & Transaction Strategy

```
┌─────────────────────────────────────────────────────────────┐
│  SUBMIT PAYMENT (anti double-charge)                        │
│  DB::transaction {                                          │
│    selling = Selling::whereKey(id)->lockForUpdate()          │
│    if selling.status == 'cancelled' → throw                  │
│    paid = selling.payments.sum(success)                      │
│    if paid + amount > selling.total_price + ε → throw        │
│    SellingPayment::create(idempotency_key, amount, ...)      │
│      // unique constraint → retry dobel → DuplicateKeyError  │
│      // catch → return transaksi yang sudah ada (idempotent) │
│    selling.status = (paid + amount >= total) ? 'paid'        │
│                                      : 'partially_paid'      │
│  }                                                           │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  CREATE SELLING + STOCK (anti oversell)                      │
│  DB::transaction {                                          │
│    products = Product::whereIn(id, ids)->lockForUpdate()     │
│    foreach detail: validate stock (di dalam lock)            │
│    code = nextSequence()   // DB sequence, BUKAN count()+1   │
│    selling = Selling::create(..., code) // status=open/paid  │
│    SellingCreated::dispatch(...)  // reduce stock (1 tx)     │
│  }  // commit → atomic                                      │
└─────────────────────────────────────────────────────────────┘

Catatan NC-1 (kode duplikat):
- Existing `SellingObserver` generate kode `SELL` + `Selling::count()`
  → 2 transaksi bersamaan bisa dapat kode SAMA.
- Wajib ganti: (a) Postgres sequence / auto-increment, atau
  (b) `Selling::lockForUpdate()->max('code')` di dalam transaction,
  atau (c) `Str::ulid()` — pastikan unique di DB level.
```

**Aturan locking:**
1. Selalu lock **row yang paling kecil cakupannya** dulu (produk → selling) untuk menghindari deadlock.
2. Urutan lock produk **diurutkan** (mis. by id) agar 2 transaksi yang nabrak produk sama tidak deadlock.
3. `lockForUpdate()` hanya di dalam `DB::transaction`. Jangan validasi stok di luar lock (race).

### 7.3 Idempotency Design

| Entitas | Key | Contoh |
|---|---|---|
| Submit cart → selling | `cart_uuid` (dibuat saat cart pertama dibuat, persist) | `9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d` |
| Payment cash | `cart_uuid + "-" + urutan_payment` | `...-1`, `...-2` |
| Payment QRIS/webhook | `"midtrans:" + transaction_id` | `midtrans:5a2c...` |

**Behavior retry:**
```
POST /submit (idempotency_key = cart_uuid)
  jika selling sudah ada dengan cart_uuid → return selling tsb (200, idempotent)
  jika belum → create

POST /webhook/midtrans (idempotency_key = "midtrans:"+transaction_id)
  jika payment sudah ada → return OK (200), TIDAK double create
  jika belum → create + update status selling
```

### 7.4 Recovery & Cleanup

- **Scheduler (tiap 5 menit):** `selling open/partially_paid WHERE updated_at < now()-12h` → `status='cancelled'`, `cancel_reason='auto-release idle'`, dan **kembalikan stok** (reverse stock untuk item yang belum dibatalkan).
- **On boot** (Vercel cold start / setelah migrate): sama, panggil routine yang sama.
- **Force close manual:** kasir/owner bisa cancel bill aktif kapan pun, dengan alasan wajib.
- **Partial paid yang di-cancel:** tercatat payment yang sudah masuk (audit), stok item dikembalikan, selisih terlaporkan.

### 7.5 Audit Trail

Kolom `cancel_reason`, `cancelled_by`, `cancelled_at` pada `sellings`. Untuk refund item: tabel/flag `is_refunded` pada `selling_details` + user/timestamp. Semua cancel/refund/force-close **wajib** mencatat user + waktu + alasan.

---

## 8. Dampak ke Kode Existing

| Area | Dampak | Detail |
|---|---|---|
| `SellingService::create` | Refactor | Bungkus dengan transaction + lock stok; masukkan idempotency |
| `PaymentHandler` (Cashier) | Refactor besar | Dari 1 metode → `selling_payments` multi |
| `SellingObserver` | Refactor | `is_paid` → `status`; code generation tetap |
| `ReceivableService` | Adaptasi | Credit tetap jalan sebagai 1 payment method; pastikan kompatibel dengan `status` |
| `SellingResource` (list) | Adaptasi | List filter `status`; tambah aksi cancel |
| `TableResource` | Upgrade | Tambah capacity/zone/sort; blokir hapus saat ada bill |
| `Cashier` page | Refactor besar | Modal split bill, multi-payment, denah meja |
| `StockOpname`, `Reports` | Verifikasi | Pastikan query memakai `status='paid'` bukan `is_paid` |
| Webhook Midtrans/Flip | Idempotent | Wrap dengan idempotency key |
| PWA sync (`SyncController`) | Idempotent | Sync offline tidak boleh duplikat |

---

## 9. Acceptance Criteria (per fitur)

### AC-1: Open bill & meja
- [ ] Membuka 2 bill di meja yang sama **bersamaan** → hanya 1 yang berhasil (RC-1)
- [ ] Tambah item ke bill `open` menambah `total_price`, tidak buat selling baru (FR-1.3)
- [ ] Cancel bill mengembalikan stok (FR-1.5)
- [ ] Bill idle > 12 jam auto-cancel, meja jadi kosong (FR-1.6, TB-1)
- [ ] Hapus meja dengan bill aktif → ditolak (FR-2.6, TB-5)

### AC-2: Split bill
- [ ] Split bill 3 bagian, bayar beda metode → semua lunas, total pas (FR-3.1–3.3)
- [ ] Split dengan selisih pembulatan → tercatat eksplisit, tidak hilang (SB-1)
- [ ] Diskon/tax proporsional per bagian (FR-3.5, SB-3)
- [ ] 1 bagian lunas, 2 pending → bill `partially_paid` (SB-5)

### AC-3: Multi-payment
- [ ] Cash + QRIS campur dalam 1 bill → 2 baris `selling_payments`, lunas (FR-4.1)
- [ ] Overpay → ditolak (FR-4.3, PM-5)
- [ ] Webhook Midtrans 2x → 1x diproses (PM-2)
- [ ] Submit retry 2x → 1 selling dibuat (RC-4)

### AC-4: Stok & race
- [ ] 2 kasir jual item stok 1 bersamaan → 1 sukses, 1 gagal, stok tidak minus (RC-2)
- [ ] 2 device submit payment bill sama → 1 double-charge (RC-3)

### AC-5: Recovery
- [ ] Server restart tengah open bill → setelah boot, bill stale auto-cancel (TB-6)
- [ ] Force close manual tercatat audit (FR-6/NFR-6)

### AC-6: Race & concurrency lanjutan
- [ ] 2 waiter tambah item ke bill sama bersamaan → tidak ada item hilang (RC-9)
- [ ] Waiter tambah item saat bill sedang dibayar → ditolak / konsisten (RC-8)
- [ ] 2 kasir cancel bill sama bersamaan → stok balik 1x, bukan 2x (AU-2)
- [ ] 2 transaksi bersamaan → kode `SELL` unik, tidak duplikat (NC-1)
- [ ] Voucher quota dipakai 2 bill bersamaan → hanya 1 sukses (VO-1)
- [ ] Idempotency key tidak bocor lintas tenant (RC-13)
- [ ] Voucher kedaluwarsa di tengah bill open → harga tidak berubah diam-diam (VO-2)
- [ ] Refund bill yang sudah paid → status + stok kembali konsisten (PM-12)

### AC-7: On-premise kafe
- [ ] Kasir/mekja/split bill/struk/laporan jalan **tanpa internet** di server client (FR-9.1)
- [ ] Fitur marketing (struk digital shareable, auto-post) **butuh internet** — ditandai jelas di UI (FR-9.2)
- [ ] License per domain valid; heartbeat terpantau dari pusat; warning-only bukan blokir (FR-9.6, existing)
- [ ] Managed maintenance: update + backup otomatis + remote assist jalan dengan charge (FR-9.3–9.5)
- [ ] Paket Lite vs Pro membedakan fitur kafe (open bill vs split bill+KDS+X/Z) (FR-9.6)
- [ ] Backup otomatis harian berhasil di-restore (FR-9.5)

---

## 10. Prioritas & Roadmap

```
PRIORITAS 1 (fondasi — WAJIB sebelum jual):
  Phase A: Migration multi-payment + selling.status + partial unique index
  Phase B: Refactor SellingService (lock stok + transaction + idempotency)
  Phase C: PaymentHandler multi-payment (ganti 1-metode)
  Phase D: Table derived status + capacity/zone + idle recovery

PRIORITAS 2 (fitur kafe):
  Phase E: Split bill (di atas selling_payments)
  Phase F: KDS (kitchen display)
  Phase G: X/Z report & shift management

PRIORITAS 3 (diferensiasi):
  Phase H: Marketing otomatis dari data POS:
          H1. Best seller auto-post + auto-caption (effort kecil, cepat)
          H2. Grafik jam sepi/ramai
          H3. Struk digital shareable (impact terbesar, tiap transaksi = share)
          H4. QR menu digital + badge Menu Hits
          H5. Kartu member apresiasi
  Phase I: Multi-bahasa struk (ID/EN) — wisatawan asing
  Phase J: On-prem kafe (fundasi sudah implemented — packaging + jualan):
          J1. Paket Kafe Lite / Pro + pricing (FR-9.6, FR-9.7)
          J2. Keterangan "butuh internet" utk fitur marketing (FR-9.2)
          J3. Maintenance managed (+charge) + remote assist + backup (FR-9.3–9.5)
          J4. Sales kit: demo kafe, template penawaran, brosur kafe
```

**Catatan Phase H:** H1–H2 (konten otomatis) bisa ditarik ke PRIORITAS 2 sebagai "quick win" karena effort kecil tapi langsung jadi alasan kafe pilih kita. H3 (struk digital shareable) butuh generate link/QR + template IG story — effort sedang, impact terbesar, urutkan segera setelah fondasi stabil.

**Catatan Phase J:** onprem sudah punya license + heartbeat + update signed (implemented). Yang belum: packaging kafe, pricing, managed maintenance, sales kit. Fitur marketing (Phase H) **butuh internet** — di onprem offline fitur itu tidak jalan, wajib dikomunikasikan ke client (FR-9.2).

### 10.1 Milestone & Timeline

> Estimasi waktu dev **1 orang** (bukan tim). Angka relatif, sesuaikan ketersediaan.

| Milestone | Isi (fase) | Estimasi | Hasil/Exit criterion |
|---|---|---|---|
| **M1 — Fondasi Aman** | Phase A+B: migration multi-payment + status, SellingService lock stok + transaction + idempotency, fix kode `SELL` (NC-1) | 1–1.5 minggu | Uang/stok aman dari race. Test RC-2/RC-3/RC-4/NC-1 pass. **Gate: gak boleh lanjut sebelum ini** |
| **M2 — Multi-Payment + Table** | Phase C+D: PaymentHandler multi-payment, table status derived + capacity/zone + idle recovery | 1–1.5 minggu | Cash+QRIS campur 1 bill; denah meja kosong/terisi; auto-release idle |
| **M3 — Kafe Operasional** | Phase E+G: split bill + X/Z report & shift | 1.5–2 minggu | Split bill multi metode; shift + selisih kas |
| **M4 — Kafe Siap Jual (Alpha)** | Phase F (KDS) + Phase H1–H2 (best-seller auto-post, grafik jam) + Phase J1–J2 (paket onprem kafe, keterangan internet) | 1.5–2 minggu | **Bisa jual ke 1-2 kafe pilot.** KDS order dapur; konten harian otomatis |
| **M5 — Diferensiasi Lengkap** | Phase H3–H5 (struk digital shareable, QR menu, kartu member) + Phase I (multi-bahasa) | 1–1.5 minggu | Struk shareable live; QR menu; struk ID/EN |
| **M6 — Onprem Kafe Komersial** | Phase J3–J4 (managed maintenance, remote assist, backup, sales kit) | 1 minggu | Bisa jual onprem komersial: packaging + pricing + managed ops |

**Total estimasi:** ~7–9 minggu kerja 1 dev (≈ 2 bulan).

```
M1 ──── M2 ──── M3 ──── M4 ──── M5 ──── M6
│      │       │       │       │       │
fondasi multi  kafe    siap   difer-  onprem
aman    pay+   opera-  jual   ensiasi komersial
        table  sional  (pilot) lengkap
        ↑              ↑
      gate: M1         gate: M3
      WAJIB lulus      wajib lulus
      sebelum lanjut   sebelum pilot
```

**Pilot (setelah M4):** 1–2 kafe Bali dipasang, 2–4 minggu monitoring (bug, feedback, data real), fix + polish → baru scale jualan M5/M6.

**Note:** M1 adalah gate paling kritis — jangan lewat sebelum uang/stok aman. Pilot kafe lebih baik telat 1 minggu tapi fondasi solid daripada cepat tapi data bocor.

**Batasan scope (tidak termasuk):**
- Delivery integration (GoFood/GrabFood) — fase lanjut
- Reservasi meja — fase lanjut
- Loyalty program kompleks — fase lanjut
- Inventory bahan baku (recipe/BOM) — fase lanjut

---

## 11. Risiko & Mitigasi

| Risiko | Severity | Mitigasi |
|---|---|---|
| Migration `is_paid` → `status` merusak data existing | 🔴 | Dry-run migration di staging; backfill + verifikasi; rollback plan |
| Lock stok memperlambat peak hours | 🟡 | Lock scope kecil (row-level), urutkan by id, benchmark NFR-4 |
| Deadlock 2 transaksi nabrak produk | 🟡 | Urutan lock konsisten (by product id) |
| Kode `SELL` duplikat (existing: `SellingObserver` pakai `count()`) | 🔴 | Ganti ke DB sequence / lock saat generate (NC-1) |
| Split bill kompleksitas tinggi (diskon/tax/refund) | 🟡 | Dimulai dari split sederhana (tanpa refund), refund di fase terpisah |
| Webhook idempotency bocor (key collision / lintas tenant) | 🔴 | UUID v4; unique `(tenant_id, key)` di DB (defense in depth) |
| Cancel ganda → over-restock | 🔴 | `lockForUpdate` bill saat cancel + status check (AU-2) |
| Tambah item saat bill dibayar → selisih total | 🔴 | Lock bill saat payment (RC-8) |
| KDS menambah beban dev (realtime) | 🟡 | Phase 2; polling sederhana dulu (Laravel Reverb/broadcast setelah stabil) |
| Multi-device konflik last-write-wins | 🟡 | Timestamp + peringatan konflik di UI; lock meja utk operasi kritis |
| Struk digital shareable disalahgunakan (spam/URL predict) | 🟡 | Token acak (bukan sequential); optional expiry; hanya dari transaksi valid |
| Konten auto-post jadi datar/membosankan | 🟢 | Template variasi + data real berbeda tiap hari |
| QR menu out of sync dengan harga produk | 🟡 | QR menu baca live dari data POS (bukan snapshot statis) |

---

## 12. Referensi

- Kode existing: `app/Services/Tenants/SellingService.php`, `app/Services/Tenants/ReceivableService.php`, `app/Filament/Tenant/Pages/Cashier.php`, `app/Observers/SellingObserver.php`, `app/Models/Tenants/Selling.php`, `TableResource`
- Migrasi: `database/migrations/tenant/*sellings*`, `*stocks*`, `*tables*`
- Riset pasar: kafe Bali (Dranik Ubud, Rerespace), kompetitor (Kaching, GoePOS, Kasheer, Simplus, Finipos, ESB, Moka/Pawoon)
- Data marketing: `TodaysBestSellingProduct` (dashboard), `selling_details`, `sellings.date`, `members`, `PaymentMethodChart`
- Onprem (di branch `main`): `docs/onprem/BROCHURE.md`, `docs/onprem/RUNBOOK.md`, `docs/planning/ONPREM_DEPLOYMENT_PLAN.md`, `docs/planning/ONPREM_LICENSE_PLAN.md`
- Atlas: `BUG-001`–`BUG-003`, `DEC-001`, `NEG-002`, `TASK-001`–`007`, `BUS-001`–`003` (konteks Vercel/deploy)
