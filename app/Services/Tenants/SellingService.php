<?php

namespace App\Services\Tenants;

use App\Events\RecalculateEvent;
use App\Events\SellingCreated;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\PriceUnit;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingPayment;
use App\Models\Tenants\Setting;
use App\Services\Tenants\Traits\HasNumber;
use App\Services\VoucherService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SellingService
{
    use HasNumber;

    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            // Idempotency (RC-4): satu cart_uuid = satu selling. Retry/klik ganda
            // ketemu selling yang sama → return yang sudah ada.
            if (! empty($data['cart_uuid'])) {
                $existing = Selling::query()
                    ->where('cart_uuid', $data['cart_uuid'])
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    DB::commit();

                    return $existing;
                }
            }

            // Lock stok (RC-2): kunci baris produk SEBELUM validasi — 2 kasir yang
            // nabrak produk sama akan terserialisasi, yang kedua lihat stok baru.
            $productIds = collect($data['products'])->pluck('product_id')->filter()->unique()->values()->sort()->all();
            $lockedProducts = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->validateStockInsideLock($data['products'], $lockedProducts);

            /** @var Selling $selling */
            $selling = Selling::create($data);

            SellingCreated::dispatch($selling, $data);

            /** @var Collection<Product> $products */
            $products = Product::select('id', 'selling_price', 'initial_price')
                ->whereIn('id', $selling->sellingDetails->pluck('product_id'))->get();
            RecalculateEvent::dispatch($products, $data);

            // Status bill + auto-create payment row (multi-payment, Phase C + M1)
            $this->applySellingStatusAndPayment($selling, $data);

            DB::commit();

            return $selling;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateStockInsideLock(array $products, $lockedProducts): void
    {
        foreach ($products as $item) {
            $product = $lockedProducts->get($item['product_id']);
            if (! $product || $product->is_non_stock) {
                continue;
            }

            $requestedQty = (float) ($item['qty'] ?? 0);

            if (! empty($item['price_unit_id'])) {
                $priceUnit = PriceUnit::query()->find($item['price_unit_id']);
                $requestedQty *= (float) ($priceUnit->stock ?? 1);
            }

            $available = $product->stockLatestCalculateIn()?->sum('stock') ?? 0;
            if ($requestedQty > $available) {
                throw new \RuntimeException("Insufficient stock for product {$product->id}: requested {$requestedQty}, available {$available}");
            }
        }
    }

    private function applySellingStatusAndPayment(Selling $selling, array $data): void
    {
        $paid = (float) ($data['payed_money'] ?? 0);
        $total = (float) $selling->total_price;
        $isCredit = ! empty($data['payment_method_id']) &&
            PaymentMethod::query()->find($data['payment_method_id'])?->is_credit;

        $selling->status = ($paid >= $total && ! $isCredit) ? 'paid' : 'open';
        if ($isCredit) {
            $selling->status = 'paid'; // piutang: selling final, receivable terpisah
        }
        $selling->save();

        // Simpan payment row (multi-payment) — idempotent per cart_uuid
        if (! empty($data['cart_uuid'])) {
            $key = $data['cart_uuid'] . '-1';
            SellingPayment::query()->firstOrCreate(
                [
                    'tenant_id' => $selling->tenant_id,
                    'idempotency_key' => $key,
                ],
                [
                    'selling_id' => $selling->id,
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'amount' => $paid,
                    'is_cash' => true,
                    'status' => $isCredit ? 'success' : ($paid >= $total ? 'success' : 'pending'),
                    'payment_date' => now(),
                ]
            );
        }
    }

    public function mapProductRequest(array $data): array
    {
        if (Setting::get('default_tax', 0) != 0 && !isset($data['tax'])) {
            $data['tax'] = Setting::get('default_tax');
        }
        $request = [];
        $payed_money = ($data['payed_money'] ?? 0);
        if (isset($data['friend_price']) && ! $data['friend_price']) {
            $total_price = 0;
            $total_price_after_discount = 0;
            $total_discount_per_item = 0;
            $total_cost = 0;
            $productsCollection = collect($data['products']);

            // Bulk load to avoid N+1 in loop
            $productIds = $productsCollection->pluck('product_id')->filter()->unique();
            $priceUnitIds = $productsCollection->pluck('price_unit_id')->filter()->unique();
            $productsMap = Product::select('id', 'selling_price', 'initial_price')
                ->whereIn('id', $productIds)->get()->keyBy('id');
            $priceUnitsMap = PriceUnit::select('id', 'selling_price')
                ->whereIn('id', $priceUnitIds)->get()->keyBy('id');

            $productsCollection->each(
                function ($product) use (&$total_price, &$total_cost, &$total_price_after_discount, &$total_discount_per_item, $productsMap, $priceUnitsMap) {
                    if (isset($product['price_unit_id']) && $product['price_unit_id'] != null) {
                        $product['price'] = ($priceUnitsMap->get($product['price_unit_id'])?->selling_price ?? 0) * $product['qty'];
                    }
                    $modelProduct = $productsMap->get($product['product_id']);
                    $total_price += $product['price'] ?? ($modelProduct->selling_price ?? 0) * $product['qty'];
                    $total_discount_per_item += ($product['discount_price'] ?? 0);
                    $total_price_after_discount = $total_price - ($product['discount_price'] ?? 0);
                    $total_cost += ($modelProduct->initial_price ?? 0) * $product['qty'];
                }
            );
            $total_price = ($tax_price = $total_price * ($tax = $data['tax'] ?? 0) / 100) + $total_price;
            $total_qty = collect($data['products'])->sum('qty');
            $discount_price = $data['discount_price'] ?? 0;
            if ($data['voucher'] ?? false) {
                $voucherService = new VoucherService();
                if ($voucher = $voucherService->applyable($data['voucher'], $total_price)) {
                    $discount_price = $voucher->calculate();
                    // $total_price = $total_price - $discount_price;
                    $voucher->reduceUsed();
                }
            }
            $request = [
                'discount_price' => $discount_price,
                'total_price' => $total_price,
                'total_cost' => $total_cost,
                'total_qty' => $total_qty,
                'money_changes' => $payed_money - ($total_price - $discount_price - $total_discount_per_item),
                'total_discount_per_item' => $total_discount_per_item,
                'tax_price' => $tax_price,
                'tax' => $tax,
                'payed_money' => $payed_money,
            ];
        } else {
            $request = [
                'money_changes' => ($data['payed_money'] ?? 0) - $data['total_price'],
                'payed_money' => $payed_money,
            ];
        }

        if (! isset($data['payment_method_id'])) {
            $request = array_merge($request, [
                'payment_method_id' => PaymentMethod::select('id')->where('name', 'Cash')->first()->id,
            ]);
        } else {
            /** @var PaymentMethod $pMethod */
            $pMethod = PaymentMethod::select('id', 'is_credit')->find($data['payment_method_id']);
            if ($pMethod->is_credit) {
                $request['money_changes'] = 0;
            }
        }

        return $request;
    }
}
