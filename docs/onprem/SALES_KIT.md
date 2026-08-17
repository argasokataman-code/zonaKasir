# Sales Kit — Onprem Kafe (J4)

> Template penawaran & demo untuk jual zonaKasir onprem ke kafe.

## 1. Template Penawaran Email

```
Subject: zonaKasir — POS Kafe Offline, Tanpa Langganan Bulanan

Halo [Nama Kafe],

Kami dari zonaKasir — POS yang dirancang khusus untuk kafe di Bali.

Kenapa zonaKasir?
✅ Jalan tanpa internet (server di kafe Anda)
✅ Tidak ada biaya bulanan — bayar sekali, milik selamanya
✅ QRIS terintegrasi, split bill, kitchen display
✅ Struk digital shareable ke WhatsApp customer
✅ Menu digital via QR di meja

Paket:
- Kafe Lite: Rp 1.5-2 juta (sekali bayar)
  1 server, 3 user, open bill + table + struk, update 1 tahun
- Kafe Pro: Rp 3-4 juta (sekali bayar)
  1 server, 10 user, + split bill + KDS + X/Z report, update 3 tahun

Managed maintenance (opsional): Rp 250-500 ribu/bulan
→ Kami yang urus server: update, backup, monitoring, troubleshooting remote

Mau demo? Kami bisa datang ke kafe Anda atau via video call.

Terima kasih,
[Tim zonaKasir]
```

## 2. Demo Kafe Checklist

Sebelum demo ke client:

- [ ] Siapkan laptop/PC dengan zonaKasir running
- [ ] Siapkan test products (3-5 menu kafe)
- [ ] Siapkan mock transactions (cash + QRIS)
- [ ] Demo flow:
  1. Login → lihat dashboard
  2. Open bill → tambah item → bayar cash
  3. Open bill → tambah item → bayar QRIS (snap)
  4. Split bill → bayar campur
  5. Cetak struk (preview thermal)
  6. Share struk digital (QR + link)
  7. QR menu digital (lihat dari HP)
  8. Marketing content (best seller + caption)
  9. Laporan penjualan harian
- [ ] Jelaskan offline mode: cabut koneksi internet, ulangi transaksi
- [ ] Jelaskan perbedaan Lite vs Pro
- [ ] Berikan pricing sheet

## 3. Perbedaan Lite vs Pro (Quick Reference)

| Fitur | Lite | Pro |
|-------|------|-----|
| Open bill | ✅ | ✅ |
| Table management | ✅ | ✅ |
| Struk (print + digital) | ✅ | ✅ |
| Split bill | ❌ | ✅ |
| Kitchen display (KDS) | ❌ | ✅ |
| Shift & X/Z report | ❌ | ✅ |
| Jumlah user | 3 | 10 |
| Update | 1 tahun | 3 tahun |
| Harga | Rp 1.5-2 jt | Rp 3-4 jt |

## 4. FAQ Client

**Q: Apakah perlu internet?**
A: Untuk kasir, meja, struk, laporan — TIDAK perlu internet. Untuk struk digital shareable, QR menu, dan konten marketing — PERLU internet.

**Q: Bagaimana update?**
A: Ketik `php artisan app:update` di terminal. Atau pilih managed maintenance, kami yang urus.

**Q: Bagaimana backup data?**
A: Otomatis harian ke server (jam 04:00). Bisa juga manual: `php artisan backup:database`.

**Q: Bisa dipindah ke PC lain?**
A: Ya. Copy folder project + database, jalankan di PC baru.

**Q: Apakah ada garansi?**
A: Ya, 1 tahun untuk Lite, 3 tahun untuk Pro. Termasuk update + support.
