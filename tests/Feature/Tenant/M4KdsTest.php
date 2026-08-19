<?php

use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use App\Services\Tenants\KdsService;
use App\Services\Tenants\SellingService;
use Tests\RefreshDatabaseWithTenant;
use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

describe('M4 — Kitchen Display System (FR-5.1..5.5)', function () {
    beforeEach(function () {
        \Laravel\Pennant\Feature::activate(\App\Features\ProductStock::class);

        $this->product = Product::factory()->create([
            'stock' => 50, 'is_non_stock' => false,
            'selling_price' => 10000, 'initial_price' => 5000,
        ]);
        $this->cash = PaymentMethod::where('name', 'Cash')->first();
        $this->service = app(SellingService::class);
        $this->kds = app(KdsService::class);
    });

    function kdsBill($test, string $status = 'open'): Selling
    {
        $data = [
            'products' => [
                ['product_id' => $test->product->id, 'qty' => 2, 'price' => 20000, 'discount_price' => 0],
            ],
            'payed_money' => 0,
            'total_price' => 20000,
            'discount_price' => 0,
            'payment_method_id' => $test->cash->id,
            'friend_price' => false,
            'cart_uuid' => \Illuminate\Support\Str::uuid(),
        ];
        $data = array_merge($data, $test->service->mapProductRequest($data));

        $selling = $test->service->create($data);

        if ($status === 'partially_paid') {
            $test->service->addPayment($selling, [
                'payment_method_id' => $test->cash->id,
                'amount' => 10000,
            ]);
        } elseif ($status === 'paid') {
            $test->service->addPayment($selling, [
                'payment_method_id' => $test->cash->id,
                'amount' => 20000,
            ]);
        }

        return $selling->refresh();
    }

    test('FR-5.1: order open muncul di daftar dapur', function () {
        $selling = kdsBill($this);

        $orders = $this->kds->orders();
        expect($orders)->toHaveCount(1);
        expect($orders->first()['items'])->toHaveCount(1);
        expect($orders->first()['items'][0]['product'])->toBe($this->product->name);
        expect($orders->first()['items'][0]['qty'])->toBe(2.0);
    });

    test('FR-5.1: item in_progress tetap muncul (Cooking), done tersembunyi', function () {
        $selling = kdsBill($this);
        $detail = $selling->sellingDetails()->first();

        $this->kds->updateStatus($detail, SellingDetail::KITCHEN_IN_PROGRESS);
        expect($this->kds->orders())->toHaveCount(1);
        expect($this->kds->orders()->first()['items'])->toHaveCount(1);

        $this->kds->updateStatus($detail, SellingDetail::KITCHEN_DONE);
        expect($this->kds->orders())->toHaveCount(0);
    });

    test('FR-5.2: status update valid in_progress/done tersimpan', function () {
        $selling = kdsBill($this);
        $detail = $selling->sellingDetails()->first();

        $this->kds->updateStatus($detail, SellingDetail::KITCHEN_IN_PROGRESS);
        expect($detail->refresh()->kitchen_status)->toBe('in_progress');

        $this->kds->updateStatus($detail, SellingDetail::KITCHEN_DONE);
        expect($detail->refresh()->kitchen_status)->toBe('done');
    });

    test('FR-5.2: status invalid ditolak 422', function () {
        $selling = kdsBill($this);
        $detail = $selling->sellingDetails()->first();

        $this->kds->updateStatus($detail, 'cooking');
    })->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Invalid kitchen status');

    test('KDS-1: bill partially_paid tetap muncul di dapur', function () {
        $selling = kdsBill($this, 'partially_paid');

        expect($this->kds->orders())->toHaveCount(1);
    });

    test('KDS-2: bill paid tidak muncul di dapur', function () {
        $selling = kdsBill($this);
        $selling->update(['status' => 'paid']);

        expect($this->kds->orders())->toHaveCount(0);
    });

    test('API: GET kds/orders butuh permission read selling', function () {
        actingAs($this->user)->getJson('/api/transaction/kds/orders')
            ->assertStatus(200);
    });

    test('API: POST status butuh permission update selling', function () {
        $selling = kdsBill($this);
        $detail = $selling->sellingDetails()->first();

        actingAs($this->user)->postJson("/api/transaction/kds/detail/{$detail->id}/status", [
            'status' => 'in_progress',
        ])->assertStatus(200);

        expect($detail->refresh()->kitchen_status)->toBe('in_progress');
    });

    test('API: POST status invalid ditolak', function () {
        $selling = kdsBill($this);
        $detail = $selling->sellingDetails()->first();

        actingAs($this->user)->postJson("/api/transaction/kds/detail/{$detail->id}/status", [
            'status' => 'bogus',
        ])->assertStatus(422);
    });
});
