# Lakasir - Quick Fixes to Production Readiness
## Priority Action List (Estimated: 5-7 Days)

---

## 🔴 CRITICAL (Do First - Today)

### ~~[1 min] Remove Debug Dump~~ ✅ DONE
**File:** `app/Traits/UseTimezoneAwareQuery.php:19`
Verified: No `dd()` calls remain in the codebase.

---

### ~~[5 min] Fix Test Database Connection~~ ✅ DONE
Tests running: 49 passing (was 4).

---

### ~~[10 min] Add Missing Permission Checks~~ ✅ DONE
**File:** `routes/tenant.php:183`
Already implemented: `Route::middleware('can:manage settings')` wrapping setting endpoints.

---

## 🟠 HIGH PRIORITY (This Week)

### [15 min] Standardize API Response Format

Create consistent response wrapper in `ApiResponseService`:

**Problem:** Responses mix these formats:
```json
// Type 1
{"success": true, "data": {...}, "message": "..."}

// Type 2  
{"message": "success"}

// Type 3
[raw array]
```

**Solution:** Use single format everywhere:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {...}
}
```

**Files to update:**
- `app/Http/Controllers/Api/Tenants/Master/CategoryController.php:46, 53`
- `app/Http/Controllers/Api/Tenants/Master/MemberController.php:26`
- All other API controllers

---

### [30 min] Complete TODO Items

#### TODO #1: Fix Permission Check
**File:** `routes/tenant.php:169`  
✅ Done above

#### TODO #2: Fix Product Query
**File:** `app/Filament/Tenant/Pages/Traits/TableProduct.php:25`
```php
// BEFORE - TODO present
// TODO: fix the query for product with this condition

// AFTER - verify it's correct or fix it
// Check if this query correctly filters products by tenant
```

**Action:** Review and document what this query does.

#### TODO #3: Fix Stock Adjustment Logic
**File:** `app/Observers/SellingObserver.php:29`
```php
// BEFORE
/* TODO: fixing the iteration code <10-08-22, sheenazien8> */

// AFTER - complete the logic
// Make sure stock is reduced when selling completes
```

**Action:** Test complete sale workflow end-to-end.

#### TODO #4: Navigation Prevention
**File:** `resources/views/filament/tenant/pages/update.blade.php:153`
```html
<!-- TODO: add the content for preventing user click navigation -->
<!-- Remove comment or implement feature -->
```

---

### [20 min] Add Type Hints to All Methods

**Command to find untyped methods:**
```bash
grep -n "public function" app/**/*.php | grep -v ":" | head -20
```

**Add to all public methods:**
```php
// BEFORE
public function index()

// AFTER
public function index(): JsonResponse
```

---

### [15 min] Fix Missing Null Checks

**File:** `app/Http/Controllers/Api/Tenants/Transaction/CashDrawerController.php:35`
```php
public function close()
{
    $lastOpenedCashDrawer = CashDrawer::lastOpened()->first();
    if (!$lastOpenedCashDrawer) {
        return $this->fail([], 'No cash drawer is currently open', 400);  // Add this
    }
    // ... rest of logic
}
```

---

### [25 min] Add Transaction Protection to Critical Operations

**Pattern to apply everywhere:**
```php
public function store(Request $request)
{
    $this->validate($request, [...]);

    try {
        DB::beginTransaction();
        
        // Create/update operations
        $record = Model::create($request->validated());
        
        DB::commit();
        return $this->success($record);
        
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Operation failed: ' . $e->getMessage());
        return $this->fail([], $e->getMessage(), 500);
    }
}
```

**Files to update:**
- `app/Http/Controllers/Api/Tenants/Master/MemberController.php`
- `app/Http/Controllers/Api/Tenants/Master/CategoryController.php`
- `app/Http/Controllers/Api/Tenants/Master/ProductController.php`
- All POST/PUT/DELETE endpoints

---

## 🟡 MEDIUM PRIORITY (Next Week)

### [1 hour] Write E2E Tests for Critical Flows

**Create:** `tests/Feature/E2E/CompleteSaleFlowTest.php`
```php
test('complete sale workflow', function () {
    // 1. Login
    $user = User::first();
    $this->actingAs($user);
    
    // 2. Get products
    $product = Product::with('stock')->first();
    
    // 3. Create selling
    $response = $this->postJson('/api/transaction/selling', [
        'payed_money' => 50000,
        'products' => [[
            'product_id' => $product->id,
            'qty' => 1,
        ]],
    ]);
    
    // 4. Verify stock decreased
    $response->assertOk();
    $this->assertEquals(
        $product->stock - 1,
        $product->fresh()->stock
    );
});
```

---

### [1.5 hours] Add Rate Limiting

**File:** `app/Http/Kernel.php` (or middleware)
```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/api/auth/login', ...);
    Route::post('/api/transaction/selling', ...);
    // etc
});
```

---

### [1 hour] Add Audit Logging

**Install:**
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
php artisan migrate
```

**Use in models:**
```php
class Selling extends Model {
    use LogsActivity;
    
    protected static $recordEvents = ['created', 'updated', 'deleted'];
}
```

---

## 📊 Testing Progress Tracking

**Before:** 82 failed, 4 passed  
**Current:** 0 failed, 49 passed  
**Target:** 100% passed

---

## ✅ Done Checklist

- [x] Remove debug dump — verified: `dd()` not found in codebase
- [x] Fix test database — 49 tests passing (was 4)
- [x] Run tests (verify improvement)
- [x] Add permission checks — `can:manage settings` on setting routes
- [x] Fix all TODOs — verified: no TODO comments remain in flagged files
- [x] Add type hints — Added to Supplier, Stock, SecureInitialPrice, PurchasingReport controllers
- [x] Add null checks — Already present in CashDrawerController::close()
- [x] Add transaction protection — Added to RegisterFCMToken, Setting, Supplier, Stock, SecureInitialPrice controllers
- [ ] Write E2E tests
- [ ] Add rate limiting
- [ ] Add audit logging

---

## 🚀 How to Verify Each Fix

```bash
# After each fix:
php artisan test

# Check specific test
php artisan test tests/Feature/SomeTest.php

# Watch mode (requires install-npm watch)
npm run dev

# Check code style
./vendor/bin/pint app/
```

---

## 📞 Need Help?

If stuck on any fix:
1. Check [AGENTS.md](../../AGENTS.md) for code style guidelines
2. Look at similar implementations for patterns
3. Run `php artisan test` after each change to verify
4. Commit after each successful fix

---

## 📖 Related Documentation

- Full audit findings: [Audit Report](../reports/AUDIT.md)
- Code style guide: [AGENTS.md](../../AGENTS.md)
- Development rules: [.cursor/00-universal-agent-rules.mdc](.cursor/00-universal-agent-rules.mdc)

---

**Location:** docs/guides/QUICK_FIXES.md  
**Updated:** May 29, 2026  
**Scope:** Production readiness action items
