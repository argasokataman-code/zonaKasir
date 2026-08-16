<?php

use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Services\Tenants\SellingService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M1 — Fondasi Aman (race, idempotency, kode unik)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 5,
            'is_non_stock' => false,
            'selling_price' => 10000,
            'initial_price' => 5000,
        ]);
        $this->tableId = DB::table('tables')->insertGetId([
            'number' => 'Meja 1',
            'tenant_id' => 'toko_testing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);
    });

    function baseData($test): array
    {
        return [
            'products' => [[
                'product_id' => $test->product->id,
                'qty' => 1,
                'price' => 10000,
                'discount_price' => 0,
            ]],
            'payed_money' => 10000,
            'total_price' => 10000,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => null,
        ];
    }

    test('RC-1: hanya 1 bill aktif per meja (partial unique index)', function () {
        DB::table('sellings')->insert([
            ['tenant_id' => 'toko_testing', 'code' => 'RC1-A', 'date' => now(), 'payed_money' => 0, 'money_changes' => 0, 'total_price' => 100, 'total_qty' => 1, 'status' => 'open', 'table_id' => $this->tableId],
        ]);

        expect(fn () => DB::table('sellings')->insert([
            ['tenant_id' => 'toko_testing', 'code' => 'RC1-B', 'date' => now(), 'payed_money' => 0, 'money_changes' => 0, 'total_price' => 100, 'total_qty' => 1, 'status' => 'open', 'table_id' => $this->tableId],
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('NC-1: kode SELL unik per tenant (unique constraint)', function () {
        expect(fn () => DB::table('sellings')->insert([
            ['tenant_id' => 'toko_testing', 'code' => 'SELL0001', 'date' => now(), 'payed_money' => 100, 'money_changes' => 0, 'total_price' => 100, 'total_qty' => 1, 'status' => 'paid', 'table_id' => null],
            ['tenant_id' => 'toko_testing', 'code' => 'SELL0001', 'date' => now(), 'payed_money' => 100, 'money_changes' => 0, 'total_price' => 100, 'total_qty' => 1, 'status' => 'paid', 'table_id' => null],
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('RC-4: create dengan cart_uuid sama → selling sama, tidak duplikat (idempotency)', function () {
        $data = baseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'c57f42e4-1111-4b8a-9c1d-222233334444';

        $first = $this->service->create($data);
        $second = $this->service->create($data);

        expect($second->id)->toBe($first->id);
        expect(Selling::count())->toBe(1);
    });

    test('RC-2: oversell ditolak — request qty > stock tersedia', function () {
        $data = baseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['products'][0]['qty'] = 99; // stok cuma 5

        expect(fn () => $this->service->create($data))->toThrow(\RuntimeException::class);
    });

    test('M1: create sukses bikin selling status paid + payment row', function () {
        $data = baseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'c57f42e4-2222-4b8a-9c1d-333344445555';

        $selling = $this->service->create($data);

        expect($selling->status)->toBe('paid');
        expect($selling->payments()->count())->toBe(1);
        expect($selling->payments()->first()->status)->toBe('success');
    });

    test('M1: status open saat payed_money < total (belum lunas)', function () {
        $data = baseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['payed_money'] = 5000; // total 10000
        $data['cart_uuid'] = 'c57f42e4-3333-4b8a-9c1d-444455556666';

        $selling = $this->service->create($data);

        expect($selling->status)->toBe('open');
        expect($selling->payments()->first()->status)->toBe('pending');
    });

    test('M1: payment_method_id nullable masih jalan (backward compat)', function () {
        $data = baseData($this); $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['cart_uuid'] = 'c57f42e4-4444-4b8a-9c1d-555566667777';
        $data['payment_method_id'] = null;

        $selling = $this->service->create($data);

        expect($selling->id)->toBeInt();
    });
});
