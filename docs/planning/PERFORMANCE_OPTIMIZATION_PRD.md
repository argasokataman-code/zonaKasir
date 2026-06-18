# PRD: zonaKasir POS Performance Optimization

> **Status:** Draft
> **Created:** 2026-06-18
> **Author:** Engineering
> **Priority:** Critical — Client reported major UX degradation
> **Target:** All POS transactions < 300ms response time

---

## 1. Problem Statement

Client mengeluhkan POS mode PWA performanya sangat lambat. Saat memilih produk di cashier page, terdapat delay beberapa detik sebelum cart update. Dengan 100+ produk dan 10 concurrent users, aplikasi menjadi unusable.

### Current State (Measured)

| Metric | Value | Target |
|--------|-------|--------|
| Cashier page load (mount) | ~146ms local, ~2-3s staging | < 500ms |
| Cart action (add/reduce) | ~2-3s (before fix: 210 queries) | < 300ms |
| Checkout (selling) | ~500ms+ | < 500ms |
| PWA sync endpoint | ~1-2s | < 1s |
| Livewire payload size | ~80KB+ per response | < 20KB |
| Concurrent user capacity | 5-10 before degradation | 50+ stable |

### Root Causes Identified

1. **`SELECT *` abuse** — 19 dari 23 critical queries gak specify columns
2. **N+1 query patterns** — accessor fresh-query per product di blade loop
3. **Livewire payload bloat** — semua public property serialized ke client
4. **Eager load wasted** — relationship di-eager-load tapi accessor bikin fresh query
5. **Missing composite indexes** — stocks table tanpa composite index
6. **Widget anti-patterns** — load ALL data ke PHP lalu filter

---

## 2. Scope

### In Scope

| Area | Files Affected | Impact |
|------|---------------|--------|
| **Cashier critical path** | `Cashier.php`, `CartInteraction.php`, `cashier.blade.php` | POS core UX |
| **Product query optimization** | `Cashier.php`, `SyncController.php` | Data loading |
| **Selling checkout** | `SellingService.php`, `PaymentHandler.php` | Transaction flow |
| **Livewire payload** | `Cashier.php` public properties | Network transfer |
| **Widget optimization** | `LowStockProducts.php`, `SalesChart.php` | Dashboard perf |
| **Database indexes** | New migration for composite indexes | Query planning |
| **Cache layer** | `Setting::get()` cache, product accessor cache | Repeated queries |

### NOT In Scope

| Area | Reason |
|------|--------|
| Migration to Inertia/Volt | Too large scope, separate initiative |
| Redis cache layer | Current scale doesn't justify infra complexity |
| WebSocket real-time stock | Feature request, not performance fix |
| Image CDN optimization | Separate concern |
| Mobile app rewrite | Out of scope |

---

## 3. Technical Plan

### Phase 1: SELECT * Elimination (Low Risk, High Impact)

**Goal:** Semua critical queries specify columns explicitly.

#### 3.1.1 Cashier `loadProducts()`

```php
// BEFORE
$this->products = $query->with(['stocks' => ...])->get();

// AFTER
$this->products = $query
    ->select('id', 'name', 'sku', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
    ->with(['stocks' => fn($q) => $q->select('product_id', 'stock', 'type')
        ->where('is_ready', 1)->where('type', 'in')])
    ->get();
```

**Files:** `app/Filament/Tenant/Pages/Cashier.php`

#### 3.1.2 Cashier `mount()` queries

| Query | Current | Target |
|-------|---------|--------|
| `About::first()` | `*` | `shop_name, business_type, shop_location` |
| `Setting::get()` × 2 | `*` | Cache in memory (settings rarely change) |
| `Profile::get()` | `*` | `timezone, locale` |
| `CartItem::with('product')` | `*` | `id, product_id, qty, price, discount_price, price_unit_id` + product: `id, name, selling_price` |
| `Voucher::query()` | `*` | `id, code, type, nominal, minimal_buying` |
| `Category::all()` | `*` | `id, name` |

**Files:** `app/Filament/Tenant/Pages/Cashier.php`

#### 3.1.3 `refreshCart()` queries

```php
// BEFORE
CartItem::with('product', 'priceUnit')->...

// AFTER
CartItem::select('id', 'product_id', 'qty', 'price', 'discount_price', 'price_unit_id')
    ->with([
        'product:id,name,sku,selling_price,is_non_stock,hero_images',
        'priceUnit:id,selling_price',
    ])
    ->...
```

**Files:** `app/Filament/Tenant/Pages/Cashier.php`

#### 3.1.4 SyncController bulk sync

```php
// BEFORE
$products->get()->append(['selling_price_calculate', 'stock_calculate']);

// AFTER
$products->select('id', 'name', 'sku', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
    ->with(['category:id,name', 'primaryBarcode:product_id,code'])
    ->get()
    ->map(fn($p) => [
        'id' => $p->id,
        'name' => $p->name,
        // ... explicit mapping only needed fields
    ]);
```

**Files:** `app/Http/Controllers/Api/Tenants/SyncController.php`

#### 3.1.5 SellingService bulk load

```php
// BEFORE — inside ->each() loop
Product::find($product['product_id']);     // N+1
PriceUnit::whereId($price_unit_id)->first(); // N+1

// AFTER — bulk load before loop
$productIds = collect($data['products'])->pluck('product_id');
$priceUnitIds = collect($data['products'])->pluck('price_unit_id')->filter();
$productsMap = Product::whereIn('id', $productIds)->get()->keyBy('id');
$priceUnitsMap = PriceUnit::whereIn('id', $priceUnitIds)->get()->keyBy('id');

// Inside loop — no query
$modelProduct = $productsMap->get($product['product_id']);
```

**Files:** `app/Services/Tenants/SellingService.php`

---

### Phase 2: N+1 Elimination (Medium Risk, High Impact)

**Goal:** Zero N+1 queries di critical paths.

#### 3.2.1 Blade product loop accessor fix

**Problem:** `$product->stockCalculate` dan `$product->sellingPriceCalculate` panggil `$this->stocks()` fresh query per product.

**Fix:** Modify accessors to use eager-loaded relationship when available.

```php
// Product.php — stockCalculate accessor
public function stockCalculate(): Attribute
{
    return Attribute::make(
        get: function () {
            // Use eager-loaded if available
            if ($this->relationLoaded('stocks')) {
                return $this->stocks->where('type', 'in')->sum('stock');
            }
            return $this->stockLatestCalculateIn()->sum('stock');
        },
    );
}
```

**Impact:** 100 products → 200 queries reduced to 1 query (eager load).

**Files:** `app/Models/Tenants/Product.php`

#### 3.2.2 SyncController computed attributes

**Problem:** `->append(['selling_price_calculate', 'stock_calculate'])` triggers fresh query per product.

**Fix:** Use same eager-load approach, or compute in map() after eager load.

**Files:** `app/Http/Controllers/Api/Tenants/SyncController.php`

#### 3.2.3 AdjustProduct cache (DONE)

```php
// Already fixed — cacheStockLatest() runs 1 query, 3 accessors use cache
```

**Status:** ✅ Implemented

---

### Phase 3: Livewire Payload Optimization (Low Risk, Medium Impact)

**Goal:** Reduce Livewire response size dari ~80KB ke < 20KB.

#### 3.3.1 Cast properties to minimal data

```php
// BEFORE — Livewire serializes full Eloquent models
public ?Collection $products;

// AFTER — Cast to array with only needed fields
// Or use Livewire #[Computed] to avoid serialization
```

#### 3.3.2 Remove unnecessary public properties

| Property | Serialized? | Action |
|----------|------------|--------|
| `$about` | Full model | Keep (small, needed) |
| `$categories` | Full collection | Already OK (small) |
| `$members` | Full collection | Keep (small, needed for form) |
| `$tableOption` | Full collection | Keep (small) |
| `$paymentMethods` | Array | Keep (small) |
| `$products` | **FULL collection** | ⚠️ Select only: `id, name, sku, selling_price, is_non_stock, category_id, hero_images` |
| `$cartItems` | **Full + product** | ⚠️ Select only needed fields |

**Estimated impact:** Payload ~80KB → ~15KB per response.

---

### Phase 4: Database Indexes (Low Risk, Medium Impact)

**Goal:** Composite indexes untuk hot query paths.

#### 3.4.1 New migration: composite indexes

```php
// stocks: stockLatestCalculateIn() hot path
$table->index(['product_id', 'type', 'is_ready', 'stock', 'created_at'], 'idx_stocks_calc');

// cart_items: scopeCashier()
$table->index(['user_id', 'tenant_id'], 'idx_cart_cashier');

// sellings: report queries
$table->index(['tenant_id', 'created_at'], 'idx_sellings_report');

// products: category filter + show
$table->index(['tenant_id', 'category_id', 'show'], 'idx_products_category');
```

**Note:** Current scale (18 stocks rows, small cart) doesn't require this immediately. Ship after Phase 1-3 to validate need.

---

### Phase 5: Widget Optimization (Low Risk, Low Impact)

#### 3.5.1 LowStockProducts: DB filter, not PHP filter

```php
// BEFORE — loads ALL products, filters in PHP
Product::with('stocks')->get()->filter(fn($p) => ...);

// AFTER — filter at DB level
Product::whereHas('stocks', fn($q) => 
    $q->where('type', 'in')->where('stock', '>', 0)->where('stock', '<=', 5)
)->with(['stocks' => fn($q) => $q->where('type', 'in')])
->take(10)->get();
```

#### 3.5.2 SalesChart: 1 query, not 7

```php
// BEFORE — 7 separate queries in loop
for ($i = 6; $i >= 0; $i--) {
    Selling::query()->select(...)->whereBetween(...)->first();
}

// AFTER — 1 grouped query
Selling::query()
    ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COALESCE(SUM(total_price - tax_price - total_discount_per_item - discount_price - total_cost), 0) as net_revenue'),
        DB::raw('COUNT(*) as total')
    )
    ->isPaid()
    ->whereBetween('created_at', [$startUtc->copy()->subDays(6)->startOfDay(), $endUtc])
    ->groupBy(DB::raw('DATE(created_at)'))
    ->get();
```

---

### Phase 6: Caching (Low Risk, Medium Impact)

#### 3.6.1 Settings cache

```php
// BEFORE — 2 DB queries per request
Setting::get('default_tax', 0);
Setting::get('currency', 'IDR');

// AFTER — cache in memory (settings change rarely)
private static array $settingsCache = [];

public static function cachedGet(string $key, mixed $default = null): mixed
{
    if (isset(static::$settingsCache[$key])) return static::$settingsCache[$key];
    static::$settingsCache[$key] = static::get($key, $default);
    return static::$settingsCache[$key];
}
```

#### 3.6.2 Product accessor cache (DONE)

```php
// Already fixed — cacheStockLatest() prevents repeated queries
```

**Status:** ✅ Implemented

---

## 4. Implementation Order

```
Phase 1: SELECT * Elimination     → Sprint 1 (1-2 days)
Phase 2: N+1 Elimination         → Sprint 1 (same, overlaps with Phase 1)
Phase 3: Livewire Payload        → Sprint 1 (after Phase 1)
Phase 6: Caching                 → Sprint 1 (quick wins, after Phase 1)
Phase 5: Widget Optimization     → Sprint 2 (1 day)
Phase 4: Database Indexes        → Sprint 2 (after validating need)
```

**Total estimated effort:** 3-4 sprints (1-2 weeks)

---

## 5. Testing Strategy

### 5.1 Query Count Verification

```php
// Test: mount() query count
DB::enableQueryLog();
$cashier->mount();
$queries = count(DB::getQueryLog());
expect($queries)->toBeLessThan(15); // Currently 13, target < 10

// Test: refreshCart() query count  
DB::flushQueryLog();
$cashier->refreshCart();
$queries = count(DB::getQueryLog());
expect($queries)->toBeLessThanOrEqual(3);

// Test: AdjustProduct per product
DB::flushQueryLog();
RecalculateEvent::dispatch(collect([$product]), []);
$queries = count(DB::getQueryLog());
expect($queries)->toBeLessThanOrEqual(2); // cacheStockLatest + save
```

### 5.2 Payload Size Verification

```php
// Test: Livewire products payload
$json = json_encode($products->toArray());
expect(strlen($json))->toBeLessThan(20 * 1024); // < 20KB
```

### 5.3 Manual Smoke Test

| Action | Before | Target |
|--------|--------|--------|
| Open cashier page | 2-3s | < 500ms |
| Click add to cart | 2-3s | < 300ms |
| Click reduce from cart | 2-3s | < 300ms |
| Checkout (3 products) | 500ms | < 500ms |
| PWA sync (100 products) | 1-2s | < 1s |

### 5.4 Load Test Simulation

```bash
# k6 load test script (500 concurrent users)
k6 run --vus 500 --duration 60s load-test.js

# Accept criteria:
# - p95 response < 500ms
# - p99 response < 1000ms
# - Error rate < 1%
# - MySQL connections < 50 concurrent
```

---

## 6. Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking existing query behavior | Medium | High | Test suite + manual smoke test per phase |
| Livewire state mismatch after payload change | Medium | High | Phase 3 behind feature flag, A/B test |
| Index migration slow on large tables | Low | Low | staging test first, online DDL |
| Settings cache stale | Low | Low | Clear cache on settings update |

---

## 7. Success Criteria

| Metric | Before | After | Status |
|--------|--------|-------|--------|
| Cart action response time | 2-3s | < 300ms | ⏳ |
| DB queries per cart action | 210 | < 5 | ⏳ |
| Livewire payload size | 80KB | < 20KB | ⏳ |
| Cashier page load | 2-3s | < 500ms | ⏳ |
| Concurrent users (stable) | 5-10 | 50+ | ⏳ |
| SELECT * in critical path | 19 | 0 | ⏳ |
| N+1 patterns in POS | 5 | 0 | ⏳ |

---

## 8. Previous Fixes (Already Implemented)

| Fix | Date | Commit | Impact |
|-----|------|--------|--------|
| `refreshCart()` replaces `mount()` on cart actions | 2026-06-18 | `c0d72705` | 210 → 3 queries per click |
| `cacheStockLatest()` in AdjustProduct | 2026-06-18 | `9f741284` | 5 → 1 query per product |
| Eager load stocks in `loadProducts()` | 2026-06-18 | `c0d72705` | N+1 on products grid |
| Fix `stock_calculate` in `getOfflineSyncData` | 2026-06-18 | `79cd5ff1` | Removed accessor from SQL select |
| Fix `barcode` column in `getOfflineSyncData` | 2026-06-18 | `543cfb4b` | Use relationship instead of dropped column |

---

## Appendix A: Query Count Before/After (All Fixes)

| Action | Before (Original) | After Phase 1-3 | Reduction |
|--------|-------------------|-----------------|-----------|
| Cashier mount | ~13 queries | ~8 queries | 38% |
| Add to cart | ~210 queries | ~3 queries | 98.6% |
| Checkout (3 products) | ~26 queries | ~8 queries | 69% |
| PWA sync (100 products) | ~200 queries | ~10 queries | 95% |
| Blade render (100 products) | ~200 queries | ~1 query | 99.5% |

## Appendix B: Files Modified

| File | Phase | Changes |
|------|-------|---------|
| `app/Filament/Tenant/Pages/Cashier.php` | 1,3 | select cols, refreshCart, payload |
| `app/Filament/Tenant/Pages/Traits/CartInteraction.php` | 1 | mount → refreshCart |
| `app/Models/Tenants/Product.php` | 2,6 | accessor cache, eager-load awareness |
| `app/Listeners/AdjustProduct.php` | 2 | cacheStockLatest |
| `app/Services/Tenants/SellingService.php` | 1 | bulk load before loop |
| `app/Http/Controllers/Api/Tenants/SyncController.php` | 1 | select cols, explicit mapping |
| `app/Filament/Tenant/Widgets/LowStockProducts.php` | 5 | DB filter |
| `app/Filament/Tenant/Widgets/SalesChart.php` | 5 | 1 grouped query |
| `database/migrations/tenant/xxxx_composite_indexes.php` | 4 | New migration |
