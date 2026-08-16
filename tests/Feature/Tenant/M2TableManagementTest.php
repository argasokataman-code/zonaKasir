<?php

use App\Console\Commands\CancelStaleOpenBills;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Table;
use App\Services\Tenants\SellingService;
use Illuminate\Support\Facades\DB;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M2 — Table Management & Idle Recovery (FR-2, FR-1.6)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 50,
            'is_non_stock' => false,
            'selling_price' => 10000,
            'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);

        $this->tableA = Table::query()->create(['number' => 'A1', 'zone' => 'indoor', 'capacity' => 4]);
        $this->tableB = Table::query()->create(['number' => 'A2', 'zone' => 'indoor', 'capacity' => 4]);
    });

    function m2TableBaseData($test, int $qty = 1, ?int $tableId = null): array
    {
        return [
            'products' => [[
                'product_id' => $test->product->id,
                'qty' => $qty,
                'price' => 10000 * $qty,
                'discount_price' => 0,
            ]],
            'payed_money' => 10000 * $qty,
            'total_price' => 10000 * $qty,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => null,
            'table_id' => $tableId,
        ];
    }

    test('TB-1: status meja derived — terisi saat ada bill open', function () {
        $data = m2TableBaseData($this, 2, $this->tableA->id);
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['payed_money'] = 10000; // partial → open
        $data['cart_uuid'] = 'fb11aaaa-0001-4b8a-9c1d-000000000001';
        $this->service->create($data);

        $this->tableA->refresh();
        $this->tableB->refresh();

        expect($this->tableA->activeSelling())->not->toBeNull();
        expect($this->tableB->activeSelling())->toBeNull();
    });

    test('TB-2: pindah meja — bill dari A ke B, A kosong B terisi (FR-2.4)', function () {
        $data = m2TableBaseData($this, 1, $this->tableA->id);
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['payed_money'] = 5000;
        $data['cart_uuid'] = 'fb11aaaa-0002-4b8a-9c1d-000000000002';
        $selling = $this->service->create($data);

        $this->service->moveTable($selling->id, $this->tableB->id);

        $this->tableA->refresh();
        $this->tableB->refresh();
        expect($this->tableA->activeSelling())->toBeNull();
        expect($this->tableB->activeSelling())->not->toBeNull();
    });

    test('TB-3: pindah ke meja yang sudah terisi ditolak', function () {
        $d1 = m2TableBaseData($this, 1, $this->tableA->id);
        $d1 = array_merge($d1, $this->service->mapProductRequest($d1));
        $d1['payed_money'] = 5000;
        $d1['cart_uuid'] = 'fb11aaaa-0003-4b8a-9c1d-000000000003';
        $this->service->create($d1);

        $d2 = m2TableBaseData($this, 1, $this->tableB->id);
        $d2 = array_merge($d2, $this->service->mapProductRequest($d2));
        $d2['payed_money'] = 5000;
        $d2['cart_uuid'] = 'fb11aaaa-0004-4b8a-9c1d-000000000004';
        $sellingB = $this->service->create($d2);

        expect(fn () => $this->service->moveTable($sellingB->id, $this->tableA->id))
            ->toThrow(\RuntimeException::class, 'already has an active bill');
    });

    test('TB-4: gabung bill B ke A — item pindah, total dihitung ulang, B cancelled', function () {
        $d1 = m2TableBaseData($this, 1, $this->tableA->id);
        $d1 = array_merge($d1, $this->service->mapProductRequest($d1));
        $d1['payed_money'] = 5000;
        $d1['cart_uuid'] = 'fb11aaaa-0005-4b8a-9c1d-000000000005';
        $sellingA = $this->service->create($d1);

        $d2 = m2TableBaseData($this, 3, $this->tableB->id);
        $d2 = array_merge($d2, $this->service->mapProductRequest($d2));
        $d2['payed_money'] = 5000;
        $d2['cart_uuid'] = 'fb11aaaa-0006-4b8a-9c1d-000000000006';
        $sellingB = $this->service->create($d2);

        $this->service->mergeBill($sellingB->id, $sellingA->id);

        $sellingA->refresh();
        $sellingB->refresh();
        expect($sellingA->sellingDetails()->count())->toBe(2);
        expect($sellingA->total_price)->toBe(40000.0);
        expect($sellingB->status)->toBe('cancelled');
    });

    test('TB-5: meja dengan bill aktif tidak bisa dihapus (FR-2.6)', function () {
        $data = m2TableBaseData($this, 1, $this->tableA->id);
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['payed_money'] = 5000;
        $data['cart_uuid'] = 'fb11aaaa-0007-4b8a-9c1d-000000000007';
        $this->service->create($data);

        expect(fn () => $this->tableA->delete())
            ->toThrow(\RuntimeException::class, 'active bill');
    });

    test('TB-6: idle bill auto-cancel + stock kembali (FR-1.6)', function () {
        $stockBefore = $this->product->stockLatestCalculateIn()?->sum('stock');

        $data = m2TableBaseData($this, 5, $this->tableA->id);
        $data = array_merge($data, $this->service->mapProductRequest($data));
        $data['payed_money'] = 10000; // partial
        $data['cart_uuid'] = 'fb11aaaa-0008-4b8a-9c1d-000000000008';
        $this->service->create($data);

        // paksa created_at & updated_at lampau (cutoff pakai updated_at)
        DB::table('sellings')->where('cart_uuid', 'fb11aaaa-0008-4b8a-9c1d-000000000008')
            ->update(['created_at' => now()->subHours(24), 'updated_at' => now()->subHours(24)]);

        $this->artisan('selling:cancel-stale', ['--hours' => 12, '--tenant' => 'toko_testing'])->assertSuccessful();

        $selling = Selling::where('cart_uuid', 'fb11aaaa-0008-4b8a-9c1d-000000000008')->first();
        expect($selling->status)->toBe('cancelled');
        expect($selling->cancel_reason)->toBe('idle timeout (auto)');
        expect($selling->cancelled_at)->not->toBeNull();

        $this->product->refresh();
        expect($this->product->stockLatestCalculateIn()?->sum('stock'))->toBe($stockBefore);
    });
});
