# Niche Navigation Matrix

> Daftar lengkap menu/navigasi per business type.
> File: `docs/niche-navigation.md`
> Config: `config/niches.php`

## Legend

- ✅ = Visible
- ❌ = Hidden
- 🔒 = Requires plan feature flag
- 🔑 = Requires permission

---

## F&B (Cafe, Restaurant, Warung)

| Menu | Keterangan | Feature Gate |
|------|-----------|--------------|
| ✅ Dashboard | Ringkasan penjualan | — |
| ✅ POS (Cashier) | Transaksi utama | 🔑 `create selling` |
| ✅ Kitchen Display | Display order ke dapur | 🔑 `update selling` |
| ❌ ~~Konten Hari Ini~~ | Marketing content — gak relevan F&B | — |
| ✅ Selling History | Riwayat transaksi | — |
| ✅ Supplier | Data supplier | 🔒 `supplier` |
| ✅ Member | Data member/pelanggan | 🔒 `member` |
| ✅ Payment Method | Pengaturan metode bayar | 🔒 `payment_method` |
| ✅ Receivable | Piutang usaha | 🔒 `receivable` |
| **Inventory** | | |
| ✅ Purchasing | Pembelian stok | 🔒 `purchasing` |
| ✅ Stock Opname | Opname stok | 🔒 `stock_opname` |
| ✅ Products | Daftar produk/menu | — |
| ✅ Categories | Kategori menu | — |
| ✅ Tables | Manajemen meja | — |
| **User** | | |
| ✅ User | Manajemen user | 🔒 `user` |
| ✅ Role | Manajemen role | 🔒 `role` |
| ✅ Permission | Manajemen permission | 🔒 `permission` |
| **Report** | | |
| ✅ Report | Laporan penjualan, produk, kasir, pembelian | — |
| **General** | | |
| ✅ Voucher | Voucher diskon | 🔒 `voucher` |
| ✅ Settlement Reports | Laporan settlement | — |
| ✅ Manage Withdrawals | Kelola withdrawal | — |
| ✅ Request Withdrawal | Request withdrawal | — |
| ✅ Subscription | Kelola langganan | — |
| **Setting** | | |
| ✅ General Setting | Pengaturan umum toko | — |
| ✅ Printer | Pengaturan printer | — |

---

## Retail (Toko, Minimarket)

| Menu | Keterangan | Feature Gate |
|------|-----------|--------------|
| ✅ Dashboard | Ringkasan penjualan | — |
| ✅ POS (Cashier) | Transaksi utama | 🔑 `create selling` |
| ❌ ~~Kitchen Display~~ | Gak relevan retail | — |
| ✅ Konten Hari Ini | Marketing content / konten sosmed | 🔑 `read selling` |
| ✅ Selling History | Riwayat transaksi | — |
| ✅ Supplier | Data supplier | 🔒 `supplier` |
| ✅ Member | Data member/pelanggan | 🔒 `member` |
| ✅ Payment Method | Pengaturan metode bayar | 🔒 `payment_method` |
| ✅ Receivable | Piutang usaha | 🔒 `receivable` |
| **Inventory** | | |
| ✅ Purchasing | Pembelian stok | 🔒 `purchasing` |
| ✅ Stock Opname | Opname stok | 🔒 `stock_opname` |
| ✅ Products | Daftar produk | — |
| ✅ Categories | Kategori produk | — |
| ❌ ~~Tables~~ | Gak relevan retail | — |
| **User** | | |
| ✅ User | Manajemen user | 🔒 `user` |
| ✅ Role | Manajemen role | 🔒 `role` |
| ✅ Permission | Manajemen permission | 🔒 `permission` |
| **Report** | | |
| ✅ Report | Laporan penjualan, produk, kasir, pembelian | — |
| **General** | | |
| ✅ Voucher | Voucher diskon | 🔒 `voucher` |
| ✅ Settlement Reports | Laporan settlement | — |
| ✅ Manage Withdrawals | Kelola withdrawal | — |
| ✅ Request Withdrawal | Request withdrawal | — |
| ✅ Subscription | Kelola langganan | — |
| **Setting** | | |
| ✅ General Setting | Pengaturan umum toko | — |
| ✅ Printer | Pengaturan printer | — |

---

## Wholesale (Grosir)

Sama dengan Retail — tidak ada Kitchen Display dan Tables.

---

## Fashion (Toko Fashion)

Sama dengan Retail — tidak ada Kitchen Display dan Tables.

---

## Pharmacy (Apotek/Klinik)

Sama dengan Retail — tidak ada Kitchen Display dan Tables.

---

## Other (Lainnya)

| Menu | Keterangan |
|------|-----------|
| ❌ ~~Kitchen Display~~ | Hidden |
| ❌ ~~Tables~~ | Hidden |
| ❌ ~~Konten Hari Ini~~ | Hidden |
| Sisanya | Sama dengan Retail |

---

## Perbedaan Kunci Antar Niche

| Fitur | F&B | Retail | Wholesale | Fashion | Pharmacy | Other |
|-------|-----|--------|-----------|---------|----------|-------|
| Kitchen Display | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Tables | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Marketing Content | ❌ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Split Bill | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Stock Opname | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Voucher | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

## Default Categories per Niche

| Niche | Categories |
|-------|-----------|
| F&B | Makanan, Minuman, Snack, Topping, Paket |
| Retail | Elektronik, Pakaian, Aksesoris, Perlengkapan Rumah |
| Wholesale | Sembako, Minuman, Produk Rumah Tangga |
| Fashion | Pria, Wanita, Anak, Aksesoris |
| Pharmacy | Obat, Vitamin, Alat Kesehatan, Kecantikan |
| Other | Umum |

## Default Colors per Niche

| Niche | Primary Color |
|-------|-------------|
| F&B | `#E65100` (Orange) |
| Retail | `#1565C0` (Blue) |
| Wholesale | `#2E7D32` (Green) |
| Fashion | `#AD1457` (Pink) |
| Pharmacy | `#00838F` (Teal) |
| Other | `#FF6600` (Orange) |

---

## Technical Notes

- Business type stored in `abouts.business_type`
- Legacy values mapped: `cafe` → `fnb`, `restaurant` → `fnb`, `warung` → `fnb`
- Navigation filtering done in `TenantPanelProvider::generateNavigationItem()` via `nicheKey` param
- Config in `config/niches.php`
- Business type cached in session by `TenantIsolationMiddleware` + `TenantLoginResponse`
- Filament custom navigation builder bypasses `isVisible()` filter — niche hidden items return `null`
