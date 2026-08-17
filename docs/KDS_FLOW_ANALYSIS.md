# Kitchen Display System (KDS) — E2E Flow & Analysis

> Status: Draft
> Date: 2026-08-17
> Scope: Current implementation vs ideal flow, UX gaps, role separation

---

## 1. Current State (Implemented)

### 1.1 Database

```
selling_details table:
- kitchen_status: NULL (pending) | 'in_progress' | 'done'
```

### 1.2 Code

- `KdsService::orders()` — query sellings with `kitchen_status IS NULL`
- `KitchenDisplay::startCooking()` — set `kitchen_status = 'in_progress'`
- `KitchenDisplay::markDone()` — set `kitchen_status = 'done'`
- View: `wire:poll.5s` (auto-refresh every 5 seconds)

### 1.3 Current UX Flow

```
1. Cashier create selling → selling_details created (kitchen_status = NULL)
2. KDS shows items WHERE kitchen_status IS NULL
3. Kitchen staff clicks Cook → status = 'in_progress' → ITEM DISAPPEARS
4. Kitchen staff clicks Done → status = 'done' → ITEM DISAPPEARS
```

**Problem:** Item hilang setelah klik Cook — tidak ada visual feedback untuk status "Cooking".

---

## 2. User Personas & Roles

### 2.1 Personas

| Persona | Peran | Device | Menu yang dibutuhkan |
|---------|------|--------|---------------------|
| **Cashier (Wayan)** | Proses pembayaran | PC/Tablet depan | POS, Selling History, Reports |
| **Kitchen/Barista (Putu)** | Terima & proses order | Layar dapur/counter | KDS only |
| **Owner (Pak Ketut)** | Monitor penjualan | HP/Laptop | Dashboard, Reports |

### 2.2 Current Problem

- **1 akun** (`admin@test.com`) bisa akses SEMUA menu (POS + KDS + Reports)
- Tidak ada role separation
- Kitchen staff bisa bayar, cashier bisa masak — tidak realistis

### 2.3 Required Roles

| Role | Permissions | Visible Menus |
|------|------------|---------------|
| **Admin** | All | All |
| **Cashier** | `create selling`, `read selling`, `read product` | POS, Selling History, Reports |
| **Kitchen** | `update selling` (kitchen status only) | KDS only |
| **Waiter** | `create selling`, `read table` | POS, Tables |

---

## 3. E2E Flow — Ideal (F&B Dine-In)

### 3.1 Complete Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        F&B DINE-IN FLOW                         │
└─────────────────────────────────────────────────────────────────┘

1. TAMU DATANG
   ↓
2. WAITER: Pilih meja → buka bill baru
   - Meja status: kosong → terisi (derived)
   - selling.status = 'open'
   - selling.table_id = meja
   ↓
3. WAITER: Tambah item pesanan
   - selling_details rows created (kitchen_status = NULL)
   - total_price updated
   ↓
4. ORDER MASUK KDS (OTOMATIS)
   - KDS query: WHERE kitchen_status IS NULL
   - Tampil: meja + item + qty
   ↓
5. KITCHEN/Barista: Proses order
   - Klik Cook → kitchen_status = 'in_progress'
   - Klik Done → kitchen_status = 'done'
   ↓
6. TAMU TAMBAH PESANAN (opsional)
   - Waiter tambah item ke bill YANG SAMA
   - Item baru masuk KDS (kitchen_status = NULL)
   - Item lama tetap di KDS (status berbeda)
   ↓
7. TAMU MAU BAYAR
   - Cashier: lihat bill, proses bayar
   - Bisa split bill / multi-payment
   - selling.status = 'paid'
   ↓
8. STRUK
   - Struk thermal print (optional)
   - Struk digital shareable (optional)
   ↓
9. MEJA KOSONG
   - selling.status = 'paid' → meja otomatis kosong
```

### 3.2 KDS Status Flow

```
                    ┌──────────────┐
                    │   PENDING    │
                    │ (kitchen_    │
                    │  status=NULL)│
                    └──────┬───────┘
                           │
                    Klik "Cook"
                           │
                    ┌──────▼───────┐
                    │   COOKING    │
                    │ (in_progress)│
                    └──────┬───────┘
                           │
                    Klik "Done"
                           │
                    ┌──────▼───────┐
                    │    DONE      │
                    │ (done)       │
                    └──────┬───────┘
                           │
                    Auto-hide after
                    30 detik / refresh
                           │
                    ┌──────▼───────┐
                    │   HIDDEN     │
                    │ (tidak ada   │
                    │  di KDS)     │
                    └──────────────┘
```

### 3.3 KDS Data Model

```
selling_details:
  id: INT (PK)
  selling_id: INT (FK → sellings)
  product_id: INT (FK → products)
  qty: INT
  price: DECIMAL
  kitchen_status: NULL | 'in_progress' | 'done'
  created_at: TIMESTAMP
  updated_at: TIMESTAMP

sellings:
  id: INT (PK)
  code: VARCHAR (SELL0001, etc)
  table_id: INT (FK → tables, nullable)
  status: 'open' | 'partially_paid' | 'paid' | 'cancelled'
  total_price: DECIMAL
  created_at: TIMESTAMP

tables:
  id: INT (PK)
  number: VARCHAR
  capacity: INT (nullable)
  zone: VARCHAR (indoor/outdoor, nullable)
```

---

## 4. KDS UX Design Options

### 4.1 Option A: Single List + Badge (Current + Improvement)

```
┌─────────────────────────────────────────────────────┐
│  KITCHEN DISPLAY                                    │
├─────────────────────────────────────────────────────┤
│                                                     │
│  SELL0001 — Table 5 — 2 min ago                     │
│  ┌─────────────────────────────────────────────┐    │
│  │ Nasi Goreng      x1  [🔴 Pending]           │    │
│  │ Es Teh           x2  [🟡 Cooking]           │    │
│  │ Mie Ayam         x1  [🟢 Done]              │    │
│  └─────────────────────────────────────────────┘    │
│                                                     │
│  SELL0002 — Takeaway — 5 min ago                    │
│  ┌─────────────────────────────────────────────┐    │
│  │ Kopi Susu        x3  [🔴 Pending]           │    │
│  └─────────────────────────────────────────────┘    │
│                                                     │
└─────────────────────────────────────────────────────┘

Status badges:
- 🔴 Pending (NULL) — button [Cook]
- 🟡 Cooking (in_progress) — button [Done]
- 🟢 Done (done) — no button, auto-hide after 30s
```

**Pros:** Simple, minimal change, item tidak hilang tiba-tiba
**Cons:** Banyak scroll kalau order rame

### 4.2 Option B: 3 Columns (Kanban-style)

```
┌──────────────────┬──────────────────┬──────────────────┐
│    PENDING       │    COOKING       │     DONE         │
├──────────────────┼──────────────────┼──────────────────┤
│                  │                  │                  │
│ SELL0001         │ SELL0003         │ SELL0002         │
│ Table 5          │ Table 2          │ Table 8          │
│ ──────────────── │ ──────────────── │ ──────────────── │
│ Nasi Goreng x1   │ Mie Ayam x2      │ Kopi Susu x3    │
│ [Cook]           │ [Done]           │ ✓ Done           │
│                  │                  │                  │
│ SELL0004         │                  │                  │
│ Takeaway         │                  │                  │
│ ──────────────── │                  │                  │
│ Es Teh x2        │                  │                  │
│ [Cook]           │                  │                  │
│                  │                  │                  │
└──────────────────┴──────────────────┴──────────────────┘

Auto-move: Pending → Cooking → Done → Hidden
```

**Pros:** Visual workflow, mudah di-monitor
**Cons:** Lebar layar butuh, lebih kompleks

### 4.3 Option C: Current (Hilang setelah Cook)

```
Status: ❌ TIDAK DISARANKAN
- User bingung item hilang
- Tidak ada feedback
- Tidak bisa lihat progress
```

---

## 5. Recommended Implementation

### 5.1 Phase 1: Fix Current UX (Quick)

1. **Change query:** Show items WHERE `kitchen_status IN (NULL, 'in_progress')`
   - NULL = Pending (button Cook)
   - in_progress = Cooking (button Done)
   - done = Hidden (tidak tampil)

2. **Add visual indicator:**
   - Pending: default styling + button [Cook]
   - Cooking: orange badge "Cooking" + button [Done]
   - Done: hilang dari KDS

3. **Keep polling:** `wire:poll.5s` (sudah ada)

### 5.2 Phase 2: Role Separation

1. Create roles: `admin`, `cashier`, `kitchen`, `waiter`
2. Create users:
   - `admin@test.com` → admin role
   - `cashier@test.com` → cashier role
   - `kitchen@test.com` → kitchen role
3. Kitchen user only sees KDS menu
4. Cashier user only sees POS + Selling History

### 5.3 Phase 3: Advanced KDS (Optional)

1. Sound notification when new order arrives
2. Priority levels (VIP, rush)
3. Time tracking (how long order takes)
4. KDS for specific station (food vs drink)

---

## 6. Integration Points

### 6.1 POS → KDS

```
Cashier create selling
  → SellingService::create()
  → selling_details rows created (kitchen_status = NULL)
  → KDS auto-detect (polling)
  → Order appears in KDS
```

### 6.2 KDS → Cashier

```
Kitchen mark item done
  → kitchen_status = 'done'
  → Cashier can see status (if they check)
  → All items done → order ready for payment
```

### 6.3 Table → KDS

```
Waiter assign table to selling
  → selling.table_id = table.id
  → KDS shows table number
  → Kitchen knows where to deliver
```

### 6.4 Payment → KDS

```
Cashier mark selling as 'paid'
  → selling.status = 'paid'
  → KDS should NOT show paid orders
  → Current: already filtered (kitchen_status IS NULL)
```

---

## 7. Open Questions

1. **Who assigns table?** Waiter or Cashier?
2. **Can kitchen cancel items?** Or only cashier?
3. **What if order is wrong?** How does kitchen communicate back?
4. **Multiple kitchens?** (food station vs drink station)
5. **KDS display hardware?** TV, tablet, or dedicated screen?

---

## 8. Related Docs

- `docs/planning/CAFE_FNB_PRD.md` — FR-5.1 to FR-5.5 (KDS requirements)
- `app/Filament/Tenant/Pages/KitchenDisplay.php` — Current implementation
- `app/Services/Tenants/KdsService.php` — KDS business logic
- `resources/views/filament/tenant/pages/kitchen-display.blade.php` — KDS view
