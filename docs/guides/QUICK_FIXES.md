# Lakasir - Quick Fixes to Production Readiness
## Priority Action List (Updated June 13, 2026)

---

## ✅ COMPLETED ITEMS

### Testing & Performance
- [x] Fix test database connection — MySQL installed, `lakasir_testing` DB created
- [x] Run tests — **236 passed, 0 failed** (duration: ~50s)
- [x] Cache tenant creation in `mockTenant()` — suite from ~43 min to ~50 sec
- [x] Fix test assertions (FileUpload, ProfilePhotoUpload, Supplier, SellingController)

### Audit Fixes (All Done)
- [x] 🔴 Critical: Remove debug dump, fix DB, add permission checks
- [x] 🟠 High: Standardize API responses, complete TODOs, type hints, null checks, transactions
- [x] 🟡 Medium: E2E tests, rate limiting, audit logging

---

## 🔴 NEW SECURITY FINDINGS (From Security Audit — June 13, 2026)

### [15 min] CRITICAL: Add Auth to Web Report PDF Routes
**File:** `routes/tenant.php:46-56`

Four GET routes for PDF report generation have **no authentication middleware**:
```php
Route::get('/member/purchasing-report/generate', PurchasingReportController::class);
Route::get('/member/selling-report/generate', SellingReportController::class);
Route::get('/member/product-report/generate', ProductReportController::class);
Route::get('/member/cashier-report/generate', CashierReportController::class);
```
Anyone who knows the URL can download sensitive business reports as PDFs.

**Fix:** Add `auth` middleware or redirect to API controllers with proper authorization.

---

### [10 min] HIGH: Add Permission Gates to Supplier Routes
**File:** `routes/tenant.php:120`
```php
Route::resource('/supplier', SupplierController::class);
```
All other master routes have `can()` gates. Supplier is the exception — any authenticated user can CRUD suppliers.

**Fix:** Add `.middleware('can:create supplier')`, etc.

---

### [20 min] MEDIUM: Fix Exception Message Leakage in Controllers
**Files:** SellingController, ProductController, MemberController, ProfileController, NotificationController, AboutController, SellingReportController

`$e->getMessage()` is returned directly to API clients, exposing internal state (DB schema, file paths).

**Fix:** Log `$e->getMessage()` via `Log::error()`, return generic messages to users.

---

### [15 min] MEDIUM: Fix Mass Assignment via $request->all()
**Files:** `SupplierController.php:43`, `MemberController.php:36,69`, `AboutController.php:38`

Using `$request->all()` with `$guarded = ['id']` models allows unexpected columns to be set.

**Fix:** Use `$request->validated()` or `$request->only([...])`.

---

### [5 min] MEDIUM: Add Auth to /api/check Endpoint
**File:** `routes/tenant.php:65-66`

Publicly exposes tenant email without authentication.

**Fix:** Add `auth:sanctum` middleware.

---

### [5 min] LOW: Redact Voucher Codes in Logs
**File:** `app/Services/VoucherService.php`

Voucher codes and transaction prices are logged in plaintext.

**Fix:** Use partial masking or log only voucher IDs.

---

## 📊 Testing Status

**Before:** 82 failed, 4 passed  
**After fixes:** 0 failed, 236 passed  
**Duration:** ~50 seconds (optimized from ~43 minutes)

---

**Location:** docs/guides/QUICK_FIXES.md  
**Updated:** June 13, 2026  
**Scope:** Production readiness + security action items
