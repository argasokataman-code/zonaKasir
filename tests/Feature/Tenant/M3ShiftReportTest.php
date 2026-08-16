<?php

use App\Models\Tenants\CashDrawer;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Services\Tenants\SellingService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshDatabaseWithTenant;
use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

describe('M3 — Shift & X/Z Report (FR-6.2..6.4)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 50, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->qris = PaymentMethod::where('payment_type', 'qris')->first()
            ?? PaymentMethod::factory()->create(['name' => 'QRIS', 'payment_type' => 'qris']);
        $this->service = app(SellingService::class);
    });

    test('SH-1: open drawer generate shift_no + opening_amount', function () {
        actingAs($this->user)->postJson('/api/transaction/cash-drawer', [
            'opening_balance' => 500000,
        ])->assertStatus(200);

        $drawer = CashDrawer::lastOpened()->first();
        expect($drawer->shift_no)->toMatch('/^SHFT-\d{4}$/');
        expect($drawer->opening_amount)->toBe(500000.0);
    });

    test('SH-2: close shift hitung expected, actual, difference (Z-report)', function () {
        $drawer = CashDrawer::create(['cash' => 500000, 'opened_by' => $this->user->id]);

        // transaksi cash 70.000 selama shift
        $data = [
            'products' => [['product_id' => $this->product->id, 'qty' => 7, 'price' => 70000, 'discount_price' => 0]],
            'payed_money' => 100000,
            'total_price' => 70000,
            'payment_method_id' => $this->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
            'cash_drawer_id' => $drawer->id,
        ];
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $this->service->create($data);

        $resp = actingAs($this->user)->postJson('/api/transaction/cash-drawer/close', [
            'closing_amount' => 570000,
        ])->assertStatus(200);

        $drawer->refresh();
        expect($drawer->closed_by)->toBe($this->user->id);
        expect($drawer->closed_at)->not->toBeNull();
        expect($drawer->expected_total)->toBe(70000.0);
        expect($drawer->actual_total)->toBe(70000.0); // 570000 - 500000
        expect($drawer->difference)->toBe(0.0);
    });

    test('SH-3: selisih kas terdeteksi (actual != expected)', function () {
        $drawer = CashDrawer::create(['cash' => 500000, 'opened_by' => $this->user->id]);

        $data = [
            'products' => [['product_id' => $this->product->id, 'qty' => 7, 'price' => 70000, 'discount_price' => 0]],
            'payed_money' => 100000,
            'total_price' => 70000,
            'payment_method_id' => $this->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
            'cash_drawer_id' => $drawer->id,
        ];
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $this->service->create($data);

        // fisik cuma 560.000 → selisih -10.000
        actingAs($this->user)->postJson('/api/transaction/cash-drawer/close', [
            'closing_amount' => 560000,
        ])->assertStatus(200);

        $drawer->refresh();
        expect($drawer->actual_total)->toBe(60000.0);
        expect($drawer->difference)->toBe(-10000.0);
    });

    test('SH-4: report breakdown per metode bayar dari selling_payments', function () {
        $drawer = CashDrawer::create(['cash' => 500000, 'opened_by' => $this->user->id]);

        $base = [
            'payment_method_id' => $this->cash->id,
            'friend_price' => false,
            'cash_drawer_id' => $drawer->id,
            'table_id' => null,
        ];

        // cash 50rb lunas
        $d1 = array_merge($base, [
            'products' => [['product_id' => $this->product->id, 'qty' => 5, 'price' => 50000, 'discount_price' => 0]],
            'payed_money' => 50000,
            'total_price' => 50000,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ]);
        $d1 = array_merge($d1, $this->service->mapProductRequest($d1));
        $this->service->create($d1);

        // QRIS 30rb (multi-payment partial) — QRIS 30rb + sisa 20rb open
        $d2 = array_merge($base, [
            'products' => [['product_id' => $this->product->id, 'qty' => 5, 'price' => 50000, 'discount_price' => 0]],
            'payed_money' => 0,
            'total_price' => 50000,
            'payments' => [
                ['payment_method_id' => $this->qris->id, 'amount' => 30000],
            ],
        ]);
        $d2['cart_uuid'] = \Illuminate\Support\Str::uuid();
        $d2 = array_merge($d2, $this->service->mapProductRequest($d2));
        $this->service->create($d2);

        $resp = actingAs($this->user)->getJson('/api/transaction/cash-drawer/report?shift_id='.$drawer->id)
            ->assertStatus(200);

        $data = $resp->json('data');
        expect($data['shift_no'])->toBe($drawer->shift_no);
        $byMethod = collect($data['by_method']);
        expect((float) $byMethod->where('payment_method', 'Cash')->first()['total'])->toBe(50000.0);
        expect((float) $byMethod->where('payment_method', 'QRIS')->first()['total'])->toBe(30000.0);
    });
});
