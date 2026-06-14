<?php

use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use Illuminate\Support\Facades\Config;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    Config::set('midtrans.server_key', 'test-server-key');
});

test('webhook handler returns 200 on valid signature', function () {
    $tenantId = 'toko_testing';

    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $selling = Selling::factory()->create(['tenant_id' => $tenantId]);
    $paymentMethod = PaymentMethod::firstOrCreate(
        ['name' => 'GoPay', 'tenant_id' => $tenantId],
        ['is_cash' => false, 'is_wallet' => true, 'icon' => 'gopay.png', 'tenant_id' => $tenantId]
    );
    $selling->update(['payment_method_id' => $paymentMethod->id]);

    MidtransPayment::create([
        'selling_id' => $selling->id,
        'tenant_id' => $tenantId,
        'order_id' => 'T1-test123',
        'gross_amount' => 100000,
        'status' => 'pending',
    ]);

    $about = About::first();
    $serverKey = 'test-server-key';
    $grossAmount = '100000.00';
    $orderId = 'T1-test123';
    $statusCode = '200';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

    $notification = [
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => $grossAmount,
        'order_id' => $orderId,
        'signature_key' => $signatureKey,
        'transaction_id' => 'txn-test-123',
        'payment_type' => 'gopay',
    ];

    $response = $this->postJson('/api/webhooks/midtrans', $notification);

    expect($response->status())->toBe(200);
});

test('webhook handler rejects invalid IP', function () {
    $serverKey = 'test-server-key';
    $grossAmount = '100000.00';
    $orderId = 'T1-test123';
    $statusCode = '200';
    $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

    $notification = [
        'transaction_status' => 'settlement',
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'order_id' => $orderId,
        'signature_key' => $signatureKey,
    ];

    Config::set('midtrans.webhook_ip_whitelist', ['10.0.0.1']);

    $response = $this->withHeaders([
        'REMOTE_ADDR' => '192.168.1.1',
    ])->postJson('/api/webhooks/midtrans', $notification);

    expect($response->status())->toBe(403);
});
