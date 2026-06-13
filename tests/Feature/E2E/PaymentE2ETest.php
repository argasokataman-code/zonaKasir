<?php

namespace Tests\Feature\E2E;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\InvoiceService;
use App\Services\PlanAccessService;
use Illuminate\Http\Response;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Payment & Invoice E2E Flow', function () {
    beforeEach(function () {
        $this->admin = User::first();
        $this->tid = $this->admin->tenant_id;
    });

    it('createInvoice generates pending invoice from subscription', function () {
        $plan = Plan::create([
            'name' => 'Starter', 'slug' => 'starter-' . uniqid(), 'price_monthly' => 50000, 'price_yearly' => 500000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $service = new InvoiceService();
        $invoice = $service->createInvoice($subscription, 'bank_transfer', 'Monthly payment');

        expect($invoice)->toBeInstanceOf(Invoice::class);
        expect($invoice->status)->toBe('pending');
        expect($invoice->payment_method)->toBe('bank_transfer');
        expect($invoice->amount)->toBeGreaterThan(0);
        expect($invoice->number)->toStartWith('INV-');
        expect($invoice->tenant_id)->toBe($this->tid);
    });

    it('createInvoice uses yearly price for yearly billing', function () {
        $plan = Plan::create([
            'name' => 'Yearly', 'slug' => 'yearly-' . uniqid(), 'price_monthly' => 100000, 'price_yearly' => 1000000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid,
            'plan_id' => $plan->id,
            'billing_cycle' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $service = new InvoiceService();
        $invoice = $service->createInvoice($subscription, 'manual');

        expect((int) $invoice->amount)->toBe(1000000);
    });

    it('markAsPaid sets status and paid_at', function () {
        $plan = Plan::create([
            'name' => 'PaidTest', 'slug' => 'paidtest-' . uniqid(), 'price_monthly' => 50000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $invoice = (new InvoiceService())->createInvoice($subscription);
        $paid = (new InvoiceService())->markAsPaid($invoice);

        expect($paid->status)->toBe('paid');
        expect($paid->paid_at)->not()->toBeNull();
        expect($paid->paid_at->isToday())->toBeTrue();
    });

    it('markAsPaid transitions trial subscription to active', function () {
        $plan = Plan::create([
            'name' => 'TrialPay', 'slug' => 'trialpay-' . uniqid(), 'price_monthly' => 50000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        expect($subscription->status)->toBe('trialing');

        $invoice = (new InvoiceService())->createInvoice($subscription);
        (new InvoiceService())->markAsPaid($invoice);

        $subscription->refresh();
        expect($subscription->status)->toBe('active');
    });

    it('markAsFailed sets status and appends note', function () {
        $plan = Plan::create([
            'name' => 'FailTest', 'slug' => 'failtest-' . uniqid(), 'price_monthly' => 50000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $invoice = (new InvoiceService())->createInvoice($subscription, 'card', 'Original note');
        $failed = (new InvoiceService())->markAsFailed($invoice, 'Insufficient funds');

        expect($failed->status)->toBe('failed');
        expect($failed->notes)->toContain('Failed: Insufficient funds');
        expect($failed->notes)->toContain('Original note');
    });

    it('processPayment succeeds for pending invoice', function () {
        $plan = Plan::create([
            'name' => 'ProcTest', 'slug' => 'proctest-' . uniqid(), 'price_monthly' => 50000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $invoice = (new InvoiceService())->createInvoice($subscription);
        $result = (new InvoiceService())->processPayment($invoice);

        expect($result->status)->toBe('paid');
    });

    it('processPayment rejects non-pending invoice', function () {
        $plan = Plan::create([
            'name' => 'Reject', 'slug' => 'reject-' . uniqid(), 'price_monthly' => 50000,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $invoice = (new InvoiceService())->createInvoice($subscription);
        (new InvoiceService())->markAsPaid($invoice);
        $invoice->refresh();

        expect(fn () => (new InvoiceService())->processPayment($invoice))
            ->toThrow(\Exception::class, 'not in pending status');
    });

    it('GET /api/billing/invoices returns list', function () {
        $sub = Subscription::create([
            'tenant_id' => $this->tid, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);
        Invoice::create([
            'tenant_id' => $this->tid,
            'subscription_id' => $sub->id,
            'number' => 'INV-TEST-' . uniqid(),
            'amount' => 50000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/billing/invoices');

        expect($response->status())->toBe(Response::HTTP_OK);
    });

    it('POST /api/billing/invoices creates invoice', function () {
        $tid = $this->tid;

        Subscription::where('tenant_id', $tid)->update(['status' => 'expired']);

        $plan = Plan::create([
            'name' => 'InvoiceTest', 'slug' => 'invoicetest-' . uniqid(), 'price_monthly' => 50000,
        ]);
        Subscription::create([
            'tenant_id' => $tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/billing/invoices', ['payment_method' => 'bank_transfer']);

        expect($response->status())->toBe(Response::HTTP_CREATED);
        expect($response->json('data.status'))->toBe('pending');
    });

    it('POST /api/billing/invoices requires auth', function () {
        $response = $this->postJson('/api/billing/invoices');
        expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    });

    it('GET /api/billing/features returns plan features', function () {
        $plan = Plan::create([
            'name' => 'Features', 'slug' => 'features-' . uniqid(), 'price_monthly' => 50000,
            'features' => ['pos', 'report'],
            'max_stores' => 3, 'max_users' => 5,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/billing/features');

        expect($response->status())->toBe(Response::HTTP_OK);
        expect($response->json('data'))->toHaveKeys(['features', 'is_subscription_active', 'limits', 'plan']);
        expect($response->json('data.features'))->toBe(['pos', 'report']);
        expect($response->json('data.is_subscription_active'))->toBeTrue();
    });

    it('full E2E: trial → invoice → pay → active', function () {
        $plan = Plan::create([
            'name' => 'FullE2E', 'slug' => 'fulle2e-' . uniqid(), 'price_monthly' => 150000,
            'features' => ['pos', 'report'],
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $this->tid,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $planAccess = new PlanAccessService();

        expect($planAccess->isOnTrial($this->tid))->toBeTrue();
        expect($planAccess->isSubscriptionActive($this->tid))->toBeTrue();
        expect($planAccess->hasFeature($this->tid, 'pos'))->toBeTrue();
        expect($planAccess->hasFeature($this->tid, 'multi_store'))->toBeFalse();

        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($subscription, 'manual');
        expect($invoice->status)->toBe('pending');

        $paid = $invoiceService->processPayment($invoice);
        expect($paid->status)->toBe('paid');

        $subscription->refresh();
        expect($subscription->status)->toBe('active');
        expect($planAccess->isOnTrial($this->tid))->toBeFalse();
        expect($planAccess->isSubscriptionActive($this->tid))->toBeTrue();
    });

    it('getMaxStores returns plan limit', function () {
        $tid = $this->tid;

        Subscription::where('tenant_id', $tid)->update(['status' => 'expired']);

        $plan = Plan::create([
            'name' => 'MaxStores', 'slug' => 'maxstores-' . uniqid(), 'price_monthly' => 50000, 'max_stores' => 99,
        ]);
        Subscription::create([
            'tenant_id' => $tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->getMaxStores($tid))->toBe(99);
    });

    it('getMaxUsers returns plan limit', function () {
        $plan = Plan::create([
            'name' => 'MaxUsers', 'slug' => 'maxusers-' . uniqid(), 'price_monthly' => 50000, 'max_users' => 999,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->getMaxUsers($this->tid))->toBe(999);
    });

    it('canCreateStore respects max_stores limit', function () {
        $plan = Plan::create([
            'name' => 'StoreLim', 'slug' => 'storelim-' . uniqid(), 'price_monthly' => 50000, 'max_stores' => 2,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->canCreateStore($this->tid, 0))->toBeTrue();
        expect($access->canCreateStore($this->tid, 1))->toBeTrue();
        expect($access->canCreateStore($this->tid, 2))->toBeFalse();
    });

    it('canCreateUser respects max_users limit', function () {
        $plan = Plan::create([
            'name' => 'UserLim', 'slug' => 'userlim-' . uniqid(), 'price_monthly' => 50000, 'max_users' => 3,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->canCreateUser($this->tid, 0))->toBeTrue();
        expect($access->canCreateUser($this->tid, 2))->toBeTrue();
        expect($access->canCreateUser($this->tid, 3))->toBeFalse();
    });

    it('free plan has limited features', function () {
        $plan = Plan::create([
            'name' => 'Free', 'slug' => 'free-' . uniqid(), 'price_monthly' => 0,
            'features' => ['pos', 'report'], 'max_stores' => 1, 'max_users' => 1,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->hasFeature($this->tid, 'pos'))->toBeTrue();
        expect($access->hasFeature($this->tid, 'report'))->toBeTrue();
        expect($access->hasFeature($this->tid, 'multi_store'))->toBeFalse();
    });

    it('enterprise plan has all features', function () {
        $plan = Plan::create([
            'name' => 'Enterprise', 'slug' => 'ent-' . uniqid(), 'price_monthly' => 500000,
            'features' => ['pos', 'report', 'stock_management', 'member_management', 'multi_store', 'api_access'],
            'max_stores' => 99, 'max_users' => 999,
        ]);
        Subscription::create([
            'tenant_id' => $this->tid, 'plan_id' => $plan->id, 'status' => 'active',
            'billing_cycle' => 'monthly', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        $access = new PlanAccessService();
        expect($access->hasFeature($this->tid, 'pos'))->toBeTrue();
        expect($access->hasFeature($this->tid, 'multi_store'))->toBeTrue();
    });

    it('no subscription returns default limits', function () {
        $access = new PlanAccessService();
        expect($access->getMaxStores('nonexistent-tenant'))->toBe(1);
        expect($access->getMaxUsers('nonexistent-tenant'))->toBe(1);
        expect($access->hasFeature('nonexistent-tenant', 'pos'))->toBeFalse();
    });
});
