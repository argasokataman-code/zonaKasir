<?php

use App\Features\Discount;
use App\Features\Member;
use App\Features\ProductExpired;
use App\Features\ProductImport;
use App\Features\Purchasing;
use App\Features\Supplier;
use App\Features\Voucher;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Pennant\Feature;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Plan Feature Resolve E2E', function () {
    beforeEach(function () {
        $this->admin = User::first();
        $this->tid = $this->admin->tenant_id;
    });

    it('feature() returns true when plan includes the feature', function () {
        $plan = Plan::create([
            'name' => 'Purchasing Plan',
            'slug' => 'purchasing-plan-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['supplier', 'purchasing'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        expect(Feature::active(Purchasing::class))->toBeTrue();
        expect(Feature::active(Supplier::class))->toBeTrue();
    });

    it('feature() returns false when plan excludes the feature', function () {
        $plan = Plan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic-plan-' . uniqid(),
            'price_monthly' => 25000,
            'features' => ['supplier'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        expect(Feature::active(Supplier::class))->toBeTrue();
        expect(Feature::active(Purchasing::class))->toBeFalse();
        expect(Feature::active(Voucher::class))->toBeFalse();
    });

    it('feature() returns false when no active subscription', function () {
        Subscription::where('tenant_id', $this->tid)->delete();

        Auth::login($this->admin);

        expect(Feature::active(Purchasing::class))->toBeFalse();
        expect(Feature::active(Supplier::class))->toBeFalse();
        expect(Feature::active(Member::class))->toBeFalse();
    });

    it('feature() returns false when user not authenticated', function () {
        expect(Feature::active(Purchasing::class))->toBeFalse();
    });

    it('feature() respects different plans for different tenants', function () {
        $planA = Plan::create([
            'name' => 'Tenant A Plan',
            'slug' => 'tenanta-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['purchasing'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $planA->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);
        expect(Feature::active(Purchasing::class))->toBeTrue();
        expect(Feature::active(Voucher::class))->toBeFalse();

        // Create second tenant with different plan
        $tenantBId = 'tenant-b-' . uniqid();
        $userB = User::factory()->create([
            'tenant_id' => $tenantBId,
            'email' => 'tenantb_' . uniqid() . '@test.com',
            'is_owner' => true,
        ]);
        Subscription::create([
            'tenant_id' => $tenantBId,
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        Auth::login($userB);
        expect(Feature::active(Purchasing::class))->toBeFalse();
        expect(Feature::active(Voucher::class))->toBeFalse();
    });

    it('hardcoded features (Discount, ProductImport, ProductExpired) still return false regardless of plan', function () {
        $plan = Plan::create([
            'name' => 'Full Plan',
            'slug' => 'full-plan-' . uniqid(),
            'price_monthly' => 100000,
            'features' => ['discount', 'product_import', 'product_expired'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        expect(Feature::active(Discount::class))->toBeFalse();
        expect(Feature::active(ProductImport::class))->toBeFalse();
        expect(Feature::active(ProductExpired::class))->toBeFalse();
    });

    it('feature() respects slug mapping for kebab-case features', function () {
        $plan = Plan::create([
            'name' => 'All Product Features',
            'slug' => 'all-products-' . uniqid(),
            'price_monthly' => 100000,
            'features' => [
                'product_stock', 'product_sku', 'product_barcode',
                'product_type', 'product_initial_price',
                'print_selling_a5', 'selling_tax',
                'payment_shortcut_button', 'total_revenue_in_selling_table',
            ],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        expect(Feature::active(\App\Features\ProductStock::class))->toBeTrue();
        expect(Feature::active(\App\Features\ProductSku::class))->toBeTrue();
        expect(Feature::active(\App\Features\ProductBarcode::class))->toBeTrue();
        expect(Feature::active(\App\Features\ProductType::class))->toBeTrue();
        expect(Feature::active(\App\Features\ProductInitialPrice::class))->toBeTrue();
        expect(Feature::active(\App\Features\PrintSellingA5::class))->toBeTrue();
        expect(Feature::active(\App\Features\SellingTax::class))->toBeTrue();
        expect(Feature::active(\App\Features\PaymentShortcutButton::class))->toBeTrue();
        expect(Feature::active(\App\Features\TotalRevenueInSellingTable::class))->toBeTrue();
    });

    it('feature() uses tenant_id scope (not global cache)', function () {
        // Create tenant A with purchasing feature
        $planA = Plan::create([
            'name' => 'A Plan',
            'slug' => 'a-plan-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['purchasing'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $planA->id,
            'status' => 'active',
        ]);

        // tenant A sees purchasing as true
        Auth::login($this->admin);
        expect(Feature::active(Purchasing::class))->toBeTrue();

        // Switch to tenant B — purchasing should be false
        $tenantBId = 'tenant-b-scope-' . uniqid();
        $userB = User::factory()->create([
            'tenant_id' => $tenantBId,
            'email' => 'tenantscope_' . uniqid() . '@test.com',
            'is_owner' => true,
        ]);
        Subscription::create([
            'tenant_id' => $tenantBId,
            'plan_id' => null,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        Auth::login($userB);
        // Should be false because tenantB has no plan (trial with null plan_id)
        expect(Feature::active(Purchasing::class))->toBeFalse();

        // Switch BACK to tenant A — should still be true
        Auth::login($this->admin);
        expect(Feature::active(Purchasing::class))->toBeTrue();
    });

    it('FeatureAccessService.hasFeature and feature() return same result', function () {
        $plan = Plan::create([
            'name' => 'Consistent Plan',
            'slug' => 'consistent-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['member', 'voucher'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        $service = app(\App\Services\PlanAccessService::class);
        expect($service->hasFeature($this->tid, 'member'))->toBeTrue();
        expect(Feature::active(Member::class))->toBeTrue();

        expect($service->hasFeature($this->tid, 'voucher'))->toBeTrue();
        expect(Feature::active(Voucher::class))->toBeTrue();

        expect($service->hasFeature($this->tid, 'purchasing'))->toBeFalse();
        expect(Feature::active(Purchasing::class))->toBeFalse();
    });

    it('PlanAccessService.getPlan returns plan directly matching feature()', function () {
        $plan = Plan::create([
            'name' => 'GetPlan Test',
            'slug' => 'getplan-' . uniqid(),
            'price_monthly' => 50000,
            'features' => ['supplier', 'receivable'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $service = app(\App\Services\PlanAccessService::class);
        $foundPlan = $service->getPlan($this->tid);

        expect($foundPlan)->not()->toBeNull();
        expect($foundPlan->features)->toContain('supplier');
        expect($foundPlan->features)->toContain('receivable');

        Auth::login($this->admin);
        expect(Feature::active(Supplier::class))->toBeTrue();
        expect(Feature::active(\App\Features\Receivable::class))->toBeTrue();
    });

    it('feature() returns correct result for single-feature plan', function () {
        $plan = Plan::create([
            'name' => 'Supplier Only',
            'slug' => 'supplier-only-' . uniqid(),
            'price_monthly' => 10000,
            'features' => ['supplier'],
        ]);
        Subscription::where('tenant_id', $this->tid)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        Auth::login($this->admin);

        expect(Feature::active(Supplier::class))->toBeTrue();
        expect(Feature::active(Member::class))->toBeFalse();
        expect(Feature::active(Voucher::class))->toBeFalse();
        expect(Feature::active(Purchasing::class))->toBeFalse();
        expect(Feature::active(\App\Features\Role::class))->toBeFalse();
        expect(Feature::active(\App\Features\Permission::class))->toBeFalse();
    });
});
