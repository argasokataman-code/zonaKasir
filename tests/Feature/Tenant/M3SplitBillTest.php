<?php

use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Services\Tenants\SellingService;
use App\Services\Tenants\SplitService;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M3 — Split Bill (FR-3.1..3.7)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->productA = Product::factory()->create([
            'stock' => 50, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->productB = Product::factory()->create([
            'stock' => 50, 'is_non_stock' => false,
            'selling_price' => 20000, 'initial_price' => 10000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->qris = PaymentMethod::where('payment_type', 'qris')->first()
            ?? PaymentMethod::factory()->create(['name' => 'QRIS', 'payment_type' => 'qris']);
        $this->service = app(SellingService::class);
        $this->split = app(SplitService::class);
    });

    function m3Bill($test, float $discount = 0): Selling
    {
        $data = [
            'products' => [
                ['product_id' => $test->productA->id, 'qty' => 2, 'price' => 20000, 'discount_price' => 0],
                ['product_id' => $test->productB->id, 'qty' => 1, 'price' => 20000, 'discount_price' => 0],
            ],
            'payed_money' => 0,
            'total_price' => 40000,
            'discount_price' => $discount,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
            'table_id' => null,
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));

        return $test->service->create($data);
    }

    test('SB-1: pecah 2 bagian — detail tersebar, status pending, Σ total = net bill', function () {
        $selling = m3Bill($this);
        $details = $selling->sellingDetails()->get();

        $groups = $this->split->split($selling, [
            ['label' => 'Tamu 1', 'items' => [['detail_id' => $details[0]->id, 'qty' => 1]]],
            ['label' => 'Tamu 2', 'items' => []], // sisa otomatis masuk
        ]);

        expect($groups)->toHaveCount(2);
        expect($groups[0]->status)->toBe('pending');
        expect($groups[0]->details()->count())->toBe(1);
        expect($groups[1]->details()->count())->toBe(2);
        expect(abs(($groups[0]->total + $groups[1]->total) - 40000))->toBeLessThan(1);
        $selling->refresh();
        expect($selling->status)->toBe('partially_paid');
    });

    test('SB-2: qty pecahan (1 porsi dibagi 2) — FR-3.4', function () {
        $selling = m3Bill($this);
        $detail = $selling->sellingDetails()->where('qty', 2)->first();

        $groups = $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $detail->id, 'qty' => 1.5]]],
            ['label' => 'B', 'items' => []],
        ]);

        expect($groups[0]->details()->count())->toBe(1);
        expect($groups[1]->details()->count())->toBe(2);
    });

    test('SB-3: bayar tiap bagian independen — cash + QRIS (FR-3.2)', function () {
        $selling = m3Bill($this);
        $details = $selling->sellingDetails()->get();

        $groups = $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $details[0]->id, 'qty' => 2]]],
            ['label' => 'B', 'items' => []],
        ]);

        $this->service->addPayment($selling, [
            'payment_method_id' => $this->cash->id,
            'amount' => $groups[0]->total,
        ], null, $groups[0]->id);
        $selling->refresh();
        expect($selling->status)->toBe('partially_paid');

        $this->service->addPayment($selling, [
            'payment_method_id' => $this->qris->id,
            'amount' => $groups[1]->total,
        ], null, $groups[1]->id);

        $selling->refresh();
        $groups[0]->refresh();
        $groups[1]->refresh();
        expect($groups[0]->status)->toBe('paid');
        expect($groups[1]->status)->toBe('paid');
        expect($selling->status)->toBe('paid');
        expect($selling->is_paid)->toBeTrue();
        expect($selling->payments()->count())->toBe(2);
    });

    test('SB-4: overpay per bagian ditolak (FR-4.3 di group)', function () {
        $selling = m3Bill($this);
        $details = $selling->sellingDetails()->get();

        $groups = $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $details[0]->id, 'qty' => 2]]],
            ['label' => 'B', 'items' => []],
        ]);

        expect(fn () => $this->service->addPayment($selling, [
            'payment_method_id' => $this->cash->id,
            'amount' => $groups[0]->total + 1000,
        ], null, $groups[0]->id))->toThrow(\RuntimeException::class, 'Overpayment');
    });

    test('SB-5: split ganda ditolak', function () {
        $selling = m3Bill($this);

        $this->split->split($selling, [
            ['label' => 'A', 'items' => []],
            ['label' => 'B', 'items' => []],
        ]);

        expect(fn () => $this->split->split($selling, [
            ['label' => 'C', 'items' => []],
        ]))->toThrow(\RuntimeException::class, 'already split');
    });

    test('SB-6: detail qty melebihi stok dialokasikan ditolak', function () {
        $selling = m3Bill($this);
        $details = $selling->sellingDetails()->get();

        expect(fn () => $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $details[0]->id, 'qty' => 3]]],
            ['label' => 'B', 'items' => []],
        ]))->toThrow(\RuntimeException::class, 'Invalid qty');
    });

    test('SB-7: diskon global diprorata ke tiap bagian (FR-3.5)', function () {
        $selling = m3Bill($this, 10000); // gross 40000, net 30000
        $details = $selling->sellingDetails()->get();

        $groups = $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $details[0]->id, 'qty' => 2]]],
            ['label' => 'B', 'items' => []],
        ]);

        // diskon 10000 diprorata 50/50 (subtotal sama) → tiap group 5000
        expect($groups[0]->discount_amount)->toBe(5000.0);
        expect($groups[1]->discount_amount)->toBe(5000.0);
        expect($groups[0]->total)->toBe(15000.0);
        expect($groups[1]->total)->toBe(15000.0);
        expect(abs(($groups[0]->total + $groups[1]->total) - 30000))->toBeLessThan(1);
    });

    test('SB-8: bill yg sudah ada payment tidak bisa di-split', function () {
        $data = [
            'products' => [['product_id' => $this->productA->id, 'qty' => 2, 'price' => 20000, 'discount_price' => 0]],
            'payed_money' => 10000,
            'total_price' => 20000,
            'payment_method_id' => $this->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
            'table_id' => null,
        ];
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $selling = $this->service->create($data);

        expect(fn () => $this->split->split($selling, [['label' => 'A', 'items' => []]]))
            ->toThrow(\RuntimeException::class, 'already has payments');
    });

    test('SB-9: payment ke bill split tanpa group ditolak', function () {
        $selling = m3Bill($this);
        $details = $selling->sellingDetails()->get();
        $this->split->split($selling, [
            ['label' => 'A', 'items' => [['detail_id' => $details[0]->id, 'qty' => 2]]],
            ['label' => 'B', 'items' => []],
        ]);

        expect(fn () => $this->service->addPayment($selling, [
            'payment_method_id' => $this->cash->id,
            'amount' => 5000,
        ], null, null))->toThrow(\RuntimeException::class, 'allocate payment to a split group');
    });
});
