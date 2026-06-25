<?php

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\InvoiceService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

/**
 * TDD: Subscription webhook must NOT overwrite 'paid' invoice with 'failed'.
 *
 * Midtrans may send multiple webhooks for same transaction:
 *   1. 'settlement' → invoice marked 'paid', subscription 'active'
 *   2. 'deny'/'expire' (late duplicate) → MUST NOT set 'paid' invoice to 'failed'
 *
 * @see MidtransWebhookController::handleSubscription()
 */

beforeEach(function () {
    $this->tenantId = User::first()->tenant_id;
    config(['midtrans.server_key' => 'test-server-key-hmac']);
});

// ─── Helper ─────────────────────────────────────────────────────────────────

function buildSignature(array $payload): array
{
    $serverKey = config('midtrans.server_key');
    $payload['signature_key'] = hash(
        'sha512',
        ($payload['order_id'] ?? '') .
        ($payload['status_code'] ?? '') .
        ($payload['gross_amount'] ?? '') .
        $serverKey
    );
    return $payload;
}

function makeSubscription(string $tenantId): Subscription
{
    $plan = Plan::create([
        'name' => 'Test Plan',
        'slug' => 'tp-' . uniqid(),
        'price_monthly' => 50000,
        'price_yearly' => 500000,
        'max_stores' => 2,
        'max_users' => 5,
        'features' => ['pos'],
    ]);

    return Subscription::create([
        'tenant_id' => $tenantId,
        'plan_id' => $plan->id,
        'status' => 'expired',
        'billing_cycle' => 'monthly',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);
}

function makeInvoice(Subscription $sub): Invoice
{
    return app(InvoiceService::class)->createInvoice($sub, 'midtrans');
}

// ─── Tests ──────────────────────────────────────────────────────────────────

test('settlement webhook marks invoice as paid and subscription active', function () {
    $sub = makeSubscription($this->tenantId);
    $invoice = makeInvoice($sub);

    $payload = buildSignature([
        'transaction_status' => 'settlement',
        'transaction_id' => 'txn-' . uniqid(),
        'status_code' => '200',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);

    $response = $this->postJson('/api/webhooks/midtrans', $payload);

    expect($response->status())->toBe(200);
    expect($invoice->fresh()->status)->toBe('paid');
    expect($sub->fresh()->status)->toBe('active');
});

test('deny webhook does NOT overwrite already paid invoice', function () {
    // Arrange: invoice is already paid
    $sub = makeSubscription($this->tenantId);
    $sub->update(['status' => 'active']);
    $invoice = makeInvoice($sub);
    $invoice->update(['status' => 'paid', 'paid_at' => now()]);

    // Act: late deny webhook arrives
    $payload = buildSignature([
        'transaction_status' => 'deny',
        'transaction_id' => 'txn-stale-' . uniqid(),
        'status_code' => '201',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);

    $response = $this->postJson('/api/webhooks/midtrans', $payload);

    // Assert: invoice stays paid
    expect($response->status())->toBe(200);
    expect($invoice->fresh()->status)->toBe('paid');
});

test('deny webhook marks pending invoice as failed', function () {
    $sub = makeSubscription($this->tenantId);
    $invoice = makeInvoice($sub);

    $payload = buildSignature([
        'transaction_status' => 'deny',
        'transaction_id' => 'txn-deny-' . uniqid(),
        'status_code' => '201',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);

    $response = $this->postJson('/api/webhooks/midtrans', $payload);

    expect($response->status())->toBe(200);
    expect($invoice->fresh()->status)->toBe('failed');
});

test('expire webhook does NOT overwrite paid invoice', function () {
    $sub = makeSubscription($this->tenantId);
    $inspaid = makeInvoice($sub);
    $inspaid->update(['status' => 'paid', 'paid_at' => now()]);

    $payload = buildSignature([
        'transaction_status' => 'expire',
        'transaction_id' => 'txn-stale-' . uniqid(),
        'status_code' => '201',
        'gross_amount' => (string) $inspaid->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);

    $response = $this->postJson('/api/webhooks/midtrans', $payload);

    expect($response->status())->toBe(200);
    expect($inspaid->fresh()->status)->toBe('paid');
});

test('multiple webhooks: settlement then deny keeps paid', function () {
    // Real-world scenario: Midtrans sends settlement, then deny for same sub
    $sub = makeSubscription($this->tenantId);
    $invoice = makeInvoice($sub);

    // 1st webhook: settlement
    $settlePayload = buildSignature([
        'transaction_status' => 'settlement',
        'transaction_id' => 'txn-real-' . uniqid(),
        'status_code' => '200',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);
    $this->postJson('/api/webhooks/midtrans', $settlePayload);
    expect($invoice->fresh()->status)->toBe('paid');

    // 2nd webhook: deny (stale)
    $denyPayload = buildSignature([
        'transaction_status' => 'deny',
        'transaction_id' => 'txn-stale-' . uniqid(),
        'status_code' => '201',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);
    $response = $this->postJson('/api/webhooks/midtrans', $denyPayload);

    expect($response->status())->toBe(200);
    expect($invoice->fresh()->status)->toBe('paid');
});

test('settlement webhook with target_plan_id updates subscription plan', function () {
    $sub = makeSubscription($this->tenantId);
    $invoice = makeInvoice($sub);

    // Set a target_plan different from current sub plan
    $newPlan = Plan::create([
        'name' => 'Upgraded Plan',
        'slug' => 'up-' . uniqid(),
        'price_monthly' => 100000,
        'price_yearly' => 1000000,
        'max_stores' => 10,
    ]);
    $invoice->update(['target_plan_id' => $newPlan->id]);

    $payload = buildSignature([
        'transaction_status' => 'settlement',
        'transaction_id' => 'txn-upgrade-' . uniqid(),
        'status_code' => '200',
        'gross_amount' => (string) $invoice->amount,
        'order_id' => 'SUB-' . $sub->id . '-' . time() . '-' . rand(1000, 9999),
        'payment_type' => 'bank_transfer',
    ]);

    $this->postJson('/api/webhooks/midtrans', $payload);

    expect($invoice->fresh()->status)->toBe('paid');
    expect($sub->fresh()->status)->toBe('active');
    expect((int) $sub->fresh()->plan_id)->toBe($newPlan->id);
});
