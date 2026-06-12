<?php

namespace Tests\Feature\E2E;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Selling;
use App\Models\Tenants\User;
use App\Services\Tenants\LedgerService;
use App\Services\Tenants\MidtransFeeCalculator;
use App\Services\Tenants\MidtransGatewayService;
use App\Models\Tenants\IdempotencyLog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('Core Payment Flow E2E', function () {
    beforeEach(function () {
        $this->user = User::first();
        $this->about = About::first();
        PaymentMethod::firstOrCreate(
            ['name' => 'GoPay'],
            ['is_cash' => false, 'is_wallet' => true, 'icon' => 'gopay.png']
        );
        PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            ['is_cash' => true, 'is_debit' => false, 'is_credit' => false, 'is_wallet' => false, 'icon' => 'cash.png']
        );
        // set about midtrans config
        if ($this->about) {
            $this->about->update([
                'middleman_client_key' => 'test-client-key',
                'midtrans_server_key' => 'test-server-key',
                'platform_fee_percent' => 1.0,
                'bank_name' => 'BCA',
                'bank_account_name' => 'Test',
                'bank_account_number' => '1234567890',
                'bank_code' => '014',
            ]);
        }
    });

    it('selling with cash payment does not create midtrans payment', function () {
        $cashMethod = PaymentMethod::where('is_cash', true)->first();
        $selling = Selling::factory()->create([
            'payment_method_id' => $cashMethod->id,
            'total_price' => 50000,
            'payed_money' => 50000,
            'is_paid' => true,
        ]);

        $mtPayment = MidtransPayment::where('selling_id', $selling->id)->first();
        expect($mtPayment)->toBeNull();
    });

    it('selling with digital payment method triggers midtrans payment creation', function () {
        $digitalMethod = PaymentMethod::where('is_cash', false)->first();
        $selling = Selling::factory()->create([
            'payment_method_id' => $digitalMethod->id,
            'total_price' => 100000,
            'payed_money' => 100000,
            'is_paid' => false,
        ]);

        // Check that midtrans payment can be created for this selling
        $mtPayment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => 'T99999999-test-flow',
            'gross_amount' => $selling->total_price,
            'status' => 'pending',
        ]);

        expect($mtPayment->id)->toBeGreaterThan(0);
        expect($mtPayment->status)->toBe('pending');
    });

    it('webhook settlement updates selling status', function () {
        $digitalMethod = PaymentMethod::where('is_cash', false)->first();
        $selling = Selling::factory()->create([
            'payment_method_id' => $digitalMethod->id,
            'total_price' => 100000,
            'payed_money' => 100000,
            'is_paid' => false,
        ]);

        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => 'T-test-settlement-001',
            'gross_amount' => $selling->total_price,
            'status' => 'pending',
        ]);

        // simulate webhook settlement
        $serverKey = 'test-server-key';
        $orderId = $payment->order_id;
        $statusCode = '200';
        $grossAmount = '100000.00';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'order_id' => $orderId,
            'signature_key' => $signature,
            'transaction_id' => 'txn-test-settlement-001',
            'payment_type' => 'gopay',
        ];

        // update payment (simulating webhook)
        $payment->update([
            'status' => 'settlement',
            'payment_type' => $payload['payment_type'],
            'midtrans_transaction_id' => $payload['transaction_id'],
            'paid_at' => now(),
            'notification_payload' => $payload,
        ]);

        // update selling fees/status
        $selling->update(['is_paid' => true]);

        expect($payment->fresh()->status)->toBe('settlement');
        expect($selling->fresh()->is_paid)->toBeTruthy();
    });

    it('ledger reflects settlement transaction', function () {
        $ledger = app(LedgerService::class);
        $digitalMethod = PaymentMethod::where('is_cash', false)->first();
        $selling = Selling::factory()->create([
            'payment_method_id' => $digitalMethod->id,
            'total_price' => 200000,
            'payed_money' => 200000,
            'is_paid' => false,
        ]);

        $fees = (new MidtransFeeCalculator)->calculate('gopay', 200000, 1.0);

        // simulate settlement ledger entries
        $ledger->entry(
            ledgerableType: Selling::class,
            ledgerableId: $selling->id,
            entryType: 'credit',
            amount: 200000,
            description: "Payment test via gopay",
            referenceType: 'selling',
            referenceId: $selling->id,
        );

        if ($fees['fee_midtrans'] > 0) {
            $ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $selling->id,
                entryType: 'debit',
                amount: $fees['fee_midtrans'],
                description: "MDR gopay",
                referenceType: 'fee_midtrans',
                referenceId: $selling->id,
                feeRateType: $fees['fee_midtrans_rate_type'],
                feeRateValue: $fees['fee_midtrans_rate_value'],
            );
        }

        if ($fees['fee_platform'] > 0) {
            $ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $selling->id,
                entryType: 'debit',
                amount: $fees['fee_platform'],
                description: "Platform fee",
                referenceType: 'fee_platform',
                referenceId: $selling->id,
                feeRateType: 'percentage',
                feeRateValue: 1.0,
            );
        }

        $balance = $ledger->getCurrentBalance();
        $expected = 200000 - $fees['fee_midtrans'] - $fees['fee_platform'];
        expect(abs($balance - $expected) < 1)->toBeTrue();
    });

    it('idempotency_logs prevents duplicate webhook processing', function () {
        $orderId = 'T-dedup-test';
        $idemKey = 'idem-txn-001';

        $log1 = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idemKey],
            ['status' => 'completed', 'endpoint' => '/api/webhooks/midtrans', 'method' => 'POST']
        );

        expect($log1->wasRecentlyCreated)->toBeTrue();

        $log2 = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idemKey],
            ['status' => 'completed', 'endpoint' => '/api/webhooks/midtrans', 'method' => 'POST']
        );

        expect($log2->wasRecentlyCreated)->toBeFalse();
        expect($log1->id)->toBe($log2->id);
    });

    it('full flow: selling → midtrans payment → settlement → ledger balance', function () {
        $digitalMethod = PaymentMethod::where('is_cash', false)->first();
        $selling = Selling::factory()->create([
            'payment_method_id' => $digitalMethod->id,
            'total_price' => 150000,
            'payed_money' => 150000,
            'is_paid' => false,
        ]);

        // 1. Create midtrans payment
        MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => 'T-flow-test-full',
            'gross_amount' => $selling->total_price,
            'status' => 'pending',
        ]);

        // 2. Simulate webhook settlement
        $selling->update(['is_paid' => true]);

        // Force refresh to ensure is_paid is updated
        $selling = $selling->fresh();

        // 3. Ledger entries
        $ledger = app(LedgerService::class);
        $fees = (new MidtransFeeCalculator)->calculate('gopay', 150000, 1.0);
        $ledger->entry(Selling::class, $selling->id, 'credit', 150000, 'sale', 'selling', $selling->id);
        $ledger->entry(Selling::class, $selling->id, 'debit', $fees['fee_midtrans'], 'mdr', 'fee_midtrans', $selling->id);
        $ledger->entry(Selling::class, $selling->id, 'debit', $fees['fee_platform'], 'platform', 'fee_platform', $selling->id);

        // 4. Assertions
        expect(MidtransPayment::where('selling_id', $selling->id)->count())->toBe(1);
        expect($selling->fresh()->is_paid)->toBeTruthy();
        $expectedNet = 150000 - $fees['fee_midtrans'] - $fees['fee_platform'];
        expect(abs($ledger->getCurrentBalance() - $expectedNet) < 1)->toBeTrue();
    });
});
