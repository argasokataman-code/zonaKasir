# Audit Issue Verification Tests
**Purpose:** Quick commands to verify each of the 20 audit issues  
**Run:** Each section independently to confirm issue

---

## 🔴 CRITICAL ISSUES

### Issue #1: Test Database Connection Broken
```bash
# Test current state
php artisan test --filter=TestName 2>&1 | grep -i "access denied\|sqlstate"

# Expected output:
# SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'

# Verification checklist:
# [ ] Check .env has DB_DATABASE_TESTING set
# [ ] Check .env.example for DB_USERNAME
# [ ] Verify config/database.php 'testing' connection exists
```

### Issue #2: Debug Code (dd/dump in UseTimezoneAwareQuery)
```bash
# Find all dd() and dump() statements
grep -rn "dd(\|dump(" app/ --include="*.php" | grep -v "vendor\|test"

# Expected results:
# app/Traits/UseTimezoneAwareQuery.php:15:        dump($start, $end);
# app/Traits/UseTimezoneAwareQuery.php:19:        dd($startDate, $endDate);

# Quick fix verification:
grep -n "dd(\|dump(" app/Traits/UseTimezoneAwareQuery.php
# After fix: No results should appear
```

---

## 🟠 HIGH PRIORITY ISSUES

### Issue #3/9: Missing Permission Checks on Setting Routes
```bash
# Check route definitions for permission middleware
grep -A 2 "setting\|Route::" routes/tenant.php | grep -B 2 "show\|store"

# Verification:
# Look for: ->can('manage settings')
# Expected: MISSING (this is the issue)

# Test API access:
curl -H "Authorization: Bearer $CASHIER_TOKEN" \
     http://localhost:8000/api/setting/currency
# Expected response: 200 OK (BUG - should be 403)
# After fix: 403 Forbidden
```

### Issue #4: Incomplete Update Blade Template
```bash
# Find TODO in Blade template
grep -n "TODO" resources/views/filament/tenant/pages/update.blade.php

# Expected:
# 153:      <!-- TODO: add the content for preventing user click navigation -->

# Verification: File should not have TODO comments before production
grep -rn "TODO:" resources/views/ --include="*.php"
```

### Issue #5/18: Broken Iteration Code in SellingObserver
```bash
# Check for TODO and inefficient queries
grep -A 10 "creating(" app/Observers/SellingObserver.php | grep -E "TODO|Selling::all"

# Expected:
# /* TODO: fixing the iteration code <10-08-22, sheenazien8> */
# $sellings = Selling::all();

# Verify fix: Should use Selling::count() instead
# After fix: No Selling::all() call
```

### Issue #6: Incomplete Product Query
```bash
# Find incomplete TODO in TableProduct trait
grep -B 5 -A 10 "TODO: fix the query for product" app/Filament/Tenant/Pages/Traits/TableProduct.php

# Expected:
# // TODO: fix the query for product with this condition

# Check if query is actually broken:
php artisan tinker
> \App\Models\Tenants\Product::query()->where([...])->limit(1)->get()
# Should return products with correct visibility rules
```

### Issue #7: Tech Debt Migration
```bash
# Find migration with TODO
grep -B 2 -A 2 "TODO.*delete.*future" database/migrations/tenant/*.php

# Expected:
# // TODO: delete this in future, and update all of double to decimal like this

# Check actual column type:
SHOW COLUMNS FROM sellings WHERE Field = 'tax_price';
# Expected: DECIMAL(15,2) or DOUBLE (issue if DOUBLE)
```

### Issue #10: Inconsistent API Response Formats
```bash
# Test different endpoint response formats
echo "=== Dashboard (buildResponse + setData) ===" 
curl -s http://localhost:8000/api/dashboard/total-revenue | jq .

echo "=== Settings (buildResponse + setMessage) ===" 
curl -s http://localhost:8000/api/setting/all | jq .

echo "=== Members (success helper) ===" 
curl -s http://localhost:8000/api/master/member | jq .

# Check if response structures differ
# Expected: { data: ..., message: ... } consistently
# Actual: Mixed formats (3+ patterns)
```

### Issue #11: Missing Unique Validation on Category Name
```bash
# Create two categories with same name
curl -X POST http://localhost:8000/api/master/category \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Electronics"}'

curl -X POST http://localhost:8000/api/master/category \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Electronics"}'

# Expected: Second request gets 422 Unprocessable Entity (after fix)
# Actual (BUG): Second request returns 200 OK
```

### Issue #12: Permission Checks Incomplete
```bash
# Check GeneralSetting.php for feature flag without RBAC
grep -B 2 -A 5 "saveFeature" app/Filament/Tenant/Pages/GeneralSetting.php

# Expected:
# if (can('access feature flag')) {

# Issue: No check for hasPermission('enable feature')
# Fix should also check: && $user->hasPermissionTo('enable ' . $featureName)
```

---

## 📋 MEDIUM PRIORITY ISSUES

### Issue #13: Weak Error Handling
```bash
# Find generic Exception throws
grep -n "throw new Exception" app/Services/VoucherService.php

# Expected:
# 36:        throw new Exception('You can\'t use calculate before assign the voucher code');
# 53:        throw new Exception('You can\'t use calculate before assign the voucher code');

# Fix: Create custom VoucherException class
# Verify: No generic Exception in services/
grep -rn "throw new Exception" app/Services/ --include="*.php" | wc -l
# Should be: 0 (after fix)
```

### Issue #14: Missing Null Checks
```bash
# Check CashDrawerController for incomplete null handling
grep -A 15 "public function close()" app/Http/Controllers/Api/Tenants/Transaction/CashDrawerController.php

# Expected: Check if code after update is complete
# Should have return statement after $lastOpenedCashDrawer->update([...])
```

### Issue #15: No Transaction Protection
```bash
# Check MemberController for DB transaction
grep -B 2 -A 5 "public function store" app/Http/Controllers/Api/Tenants/Master/MemberController.php

# Expected:
# try {
#     DB::beginTransaction();
#     ...
#     DB::commit();
# } catch ...

# Actual: No try/catch
# Fix: Wrap in transaction like ProfileController
```

### Issue #16: Wrong HTTP Status Code
```bash
# Check middleware status code
grep -n "response()->json" app/Http/Middleware/EnsureEmailIsVerified.php

# Expected:
# return response()->json(['message' => '...'], 409);

# Issue: Should be 403 or 401
# grep result shows: ], 409);
# After fix: ], 403);
```

### Issue #17: No Rate Limiting
```bash
# Check Livewire config
grep -n "middleware" config/livewire.php | grep -i throttle

# Expected:
# 'middleware' => null,  // Should be 'throttle:60,1'

# Check auth config
grep "throttle" config/auth.php

# Spam test (simulates DOS):
for i in {1..100}; do
  curl -s http://localhost:8000/api/member &
done
# Should get 429 Too Many Requests after limit
# Actual (BUG): All 100 requests succeed
```

### Issue #19: No Idempotency Keys
```bash
# Check if payment endpoint has idempotency key handling
grep -rn "idempotency\|Idempotency\|X-Idempotency" app/ --include="*.php"

# Expected: Some reference (after fix)
# Actual: Empty result (not implemented)

# Test payment duplicate:
# 1. Submit payment with Idempotency-Key header
# 2. Retry with same header
# 3. Should return cached response (not duplicate charge)
```

### Issue #20: Missing Soft Delete Handling
```bash
# Check User model for SoftDeletes
grep -n "SoftDeletes" app/Models/Tenants/User.php

# Expected:
# class User extends ... { use ..., SoftDeletes;

# Check if queries scope out soft-deletes:
grep -rn "User::where\|Member::where\|Product::where" app/Http/Controllers/ \
  | grep -v "whereNull.*deleted_at\|withoutTrashed\|onlyTrashed" \
  | wc -l

# If count > 0: queries don't scope soft deletes (BUG)
# After fix: All queries should use appropriate scope
```

---

## 🧪 AUTOMATED VERIFICATION SCRIPT

```bash
#!/bin/bash
# save as: scripts/verify-audit.sh
# run: chmod +x scripts/verify-audit.sh && ./scripts/verify-audit.sh

echo "=== LAKASIR AUDIT VERIFICATION SUITE ==="
echo ""

# Issue 1: Test DB
echo "Issue #1: Test Database Connection"
php artisan test --filter=Auth 2>&1 | head -3 | grep -q "Access denied" && echo "✅ CONFIRMED: Test DB broken" || echo "⚠️  UNCLEAR"
echo ""

# Issue 2: Debug Code
echo "Issue #2: Debug Code (dd/dump)"
grep -n "dd(\|dump(" app/Traits/UseTimezoneAwareQuery.php 2>/dev/null && echo "✅ CONFIRMED: Debug code found" || echo "❌ FIXED"
echo ""

# Issue 3: Setting Routes
echo "Issue #3: Unprotected Setting Routes"
grep "setting\|show\|store" routes/tenant.php | grep -q "can(" || echo "✅ CONFIRMED: No can() check"
echo ""

# Issue 5: Selling Observer TODO
echo "Issue #5: Broken Iteration Code"
grep -q "TODO.*fixing.*iteration" app/Observers/SellingObserver.php && echo "✅ CONFIRMED: TODO found" || echo "❌ FIXED"
echo ""

# Issue 11: Unique validation
echo "Issue #11: Missing Unique Name Validation"
grep -A 2 "public function update" app/Http/Controllers/Api/Tenants/Master/CategoryController.php | grep -q "unique" || echo "✅ CONFIRMED: No unique check"
echo ""

# Issue 16: Status code
echo "Issue #16: Wrong HTTP Status Code"
grep -q "409" app/Http/Middleware/EnsureEmailIsVerified.php && echo "✅ CONFIRMED: Status 409 found" || echo "❌ FIXED"
echo ""

# Issue 17: Rate limiting
echo "Issue #17: No Rate Limiting"
grep -q "middleware.*null" config/livewire.php && echo "✅ CONFIRMED: Rate limiting disabled" || echo "❌ FIXED"
echo ""

echo "=== VERIFICATION COMPLETE ==="
```

---

## 📊 VERIFICATION CHECKLIST

Use this before production deployment:

```
CRITICAL (Must Fix):
[ ] Issue #1 - Test DB Connection working (all tests pass)
[ ] Issue #2 - No dd() or dump() in production code

HIGH (Should Fix):
[ ] Issue #3/9 - All routes have permission checks
[ ] Issue #4 - No TODO comments in views
[ ] Issue #5 - Selling observer code fixed
[ ] Issue #6 - Product query logic complete
[ ] Issue #7 - Migration tech debt noted
[ ] Issue #10 - API responses consistent
[ ] Issue #11 - Category names unique per tenant
[ ] Issue #12 - Feature flags with RBAC

MEDIUM (Good to Fix):
[ ] Issue #13 - No generic Exception throws
[ ] Issue #14 - All null checks complete
[ ] Issue #15 - All data mutations in transactions
[ ] Issue #16 - HTTP status codes correct
[ ] Issue #17 - Rate limiting enabled
[ ] Issue #19 - Idempotency keys on payments
[ ] Issue #20 - Soft deletes properly scoped
```

---

**Generated:** 2026-05-29  
**Usage:** Run individual sections to verify each issue  
**Location:** docs/reports/AUDIT_VERIFICATION_TESTS.md
