<?php

use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Services\Tenants\SellingService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M2 — Multi-Payment (FR-4.1, FR-4.3)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 50,
            'is_non_stock' => false,
            'selling_price' => 10000,
            'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->qris = PaymentMethod::where('payment_type', 'qris')->first()
            ?? PaymentMethod::factory()->create(['name' => 'QRIS', 'payment_type' => 'qris']);
        $this->service = app(SellingService::class);
    });

    function m2BaseData($test): array
    {
        return [
            'products' => [[
                'product_id' => $test->product->id,
                'qty' => 8, // total 80.000
                'price' => 80000, // subtotal: selling_price × qty (semantics Cashier)
                'discount_price' => 0,
            ]],
            'payed_money' => 0,
            'total_price' => 80000,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => null,
        ];
    }

    test('PM-1: cash 50rb + QRIS 30rb lunas — 2 payment row, status paid', function () {
        $data = m2BaseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'd1111111-1111-4b8a-9c1d-000000000001';
        $data['payments'] = [
            ['payment_method_id' => $this->cash->id, 'amount' => 50000],
            ['payment_method_id' => $this->qris->id, 'amount' => 30000],
        ];

        $selling = $this->service->create($data);

        expect($selling->payments()->count())->toBe(2);
        expect($selling->payments()->where('status', 'success')->count())->toBe(2);
        expect($selling->totalPaid())->toBe(80000.0);
        expect($selling->status)->toBe('paid');
        expect($selling->is_paid)->toBeTrue();
    });

    test('PM-2: cash 30rb dulu → status partially_paid; tambah 50rb → paid', function () {
        $data = m2BaseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'd1111111-2222-4b8a-9c1d-000000000002';
        $data['payments'] = [
            ['payment_method_id' => $this->cash->id, 'amount' => 30000],
        ];

        $selling = $this->service->create($data);
        expect($selling->status)->toBe('partially_paid');

        $this->service->addPayment($selling, [
            'payment_method_id' => $this->qris->id,
            'amount' => 50000,
        ], 'd1111111-2222-4b8a-9c1d-000000000002');

        $selling->refresh();
        expect($selling->payments()->count())->toBe(2);
        expect($selling->status)->toBe('paid');
        expect($selling->is_paid)->toBeTrue();
    });

    test('PM-3: overpay ditolak — cash 100rb utk total 80rb (FR-4.3)', function () {
        $data = m2BaseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'd1111111-3333-4b8a-9c1d-000000000003';
        $data['payments'] = [
            ['payment_method_id' => $this->cash->id, 'amount' => 100000],
        ];

        expect(fn () => $this->service->create($data))
            ->toThrow(\RuntimeException::class, 'Overpayment');
    });

    test('PM-4: overpay di addPayment kedua ditolak — 50rb + 50rb utk total 80rb', function () {
        $data = m2BaseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'd1111111-4444-4b8a-9c1d-000000000004';
        $data['payments'] = [
            ['payment_method_id' => $this->cash->id, 'amount' => 50000],
        ];

        $selling = $this->service->create($data);

        expect(fn () => $this->service->addPayment($selling, [
            'payment_method_id' => $this->qris->id,
            'amount' => 50000,
        ], 'd1111111-4444-4b8a-9c1d-000000000004'))->toThrow(\RuntimeException::class, 'Overpayment');

        $selling->refresh();
        expect($selling->payments()->count())->toBe(1);
        expect($selling->status)->toBe('partially_paid');
    });

    test('PM-5: payment_method_id nullable backward compat — payment row status pending', function () {
        $data = m2BaseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'd1111111-5555-4b8a-9c1d-000000000005';
        $data['payments'] = [
            ['payment_method_id' => null, 'amount' => 40000],
        ];

        $selling = $this->service->create($data);

        expect($selling->payments()->count())->toBe(1);
        expect($selling->payments()->first()->payment_method_id)->toBeNull();
    });
});
