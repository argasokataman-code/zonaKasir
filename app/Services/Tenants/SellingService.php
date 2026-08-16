<?php

namespace App\Services\Tenants;

use App\Events\RecalculateEvent;
use App\Events\SellingCreated;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\PriceUnit;
use App\Models\Tenants\Product;
use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingPayment;
use App\Models\Tenants\SellingSplitGroup;
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
            $selling->status = 'open'; // payment(s) menentukannya via addPayment
            $selling->save();

            SellingCreated::dispatch($selling, $data);

            /** @var Collection<Product> $products */
            $products = Product::select('id', 'selling_price', 'initial_price')
                ->whereIn('id', $selling->sellingDetails->pluck('product_id'))->get();
            RecalculateEvent::dispatch($products, $data);

            // Status bill + auto-create payment row (multi-payment, Phase C + M1)
            $this->applySellingStatusAndPayment($selling, $data);
            $selling->refresh();

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
        $payments = $this->normalizePayments($selling, $data);

        foreach ($payments as $i => $payment) {
            $payment['idempotency_key'] = $payment['idempotency_key']
                ?? (! empty($data['cart_uuid']) ? $data['cart_uuid'].'-'.($i + 1) : null);
            $this->addPayment($selling, $payment, $data['cart_uuid'] ?? null);
        }
    }

    /**
     * Normalisasi input pembayaran ke daftar payment.
     * Multi-payment: $data['payments'] = [[payment_method_id, amount], ...]
     * Legacy: satu payment dari payed_money + payment_method_id (backward compat).
     */
    private function normalizePayments(Selling $selling, array $data): array
    {
        if (! empty($data['payments']) && is_array($data['payments'])) {
            $payments = collect($data['payments'])
                ->values()
                ->map(fn ($p) => [
                    'payment_method_id' => $p['payment_method_id'],
                    'amount' => (float) ($p['amount'] ?? 0),
                    'status' => $p['status'] ?? 'success',
                    'is_cash' => $p['is_cash'] ?? null,
                    'midtrans_ref' => $p['midtrans_ref'] ?? null,
                    'payment_date' => $p['payment_date'] ?? now(),
                ])
                ->toArray();

            return $payments;
        }

        $paid = (float) ($data['payed_money'] ?? 0);
        $paymentMethodId = $data['payment_method_id'] ?? null;

        $pMethod = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;

        // Bill open tanpa DP (F&B): bayar 0 → jangan bikin payment row (FR-1.3)
        if ($paid <= 0 && ! $pMethod?->is_credit) {
            return [];
        }

        $total = (float) ($data['total_price'] ?? 0);

        // Legacy single-payment: payed_money = uang diserahkan (boleh > total;
        // kembalian di-handle money_changes). Amount dicatat = min(payed, total).
        $amount = min($paid, $total);

        return [[
            'payment_method_id' => $paymentMethodId,
            'amount' => $amount,
            'status' => ($pMethod?->is_credit || $amount >= $total) ? 'success' : 'pending',
            'is_cash' => $pMethod?->is_cash ?? true,
            'midtrans_ref' => null,
            'payment_date' => now(),
        ]];
    }

    /**
     * Tambah pembayaran ke bill (FR-4.1, FR-4.3).
     * Lock selling → cek overpay → insert row idempotent → recompute status.
     */
    public function addPayment(Selling $selling, array $payment, ?string $cartUuid = null, ?int $splitGroupId = null): void
    {
        DB::beginTransaction();
        try {
            /** @var Selling $selling */
            $selling = Selling::query()->whereKey($selling->id)->lockForUpdate()->first();

            if (! $selling || in_array($selling->status, ['cancelled', 'paid'])) {
                throw new \RuntimeException('Bill cannot accept payment in its current status');
            }

            $total = (float) $selling->total_price;
            $paidSoFar = (float) $selling->totalPaid();

            // Split bill (FR-3.2): payment dibayarkan terhadap SATU bagian.
            // Overpay dicek per bagian (group) + tetap tak boleh > total bill.
            $group = null;
            $groupRemaining = null;
            if ($splitGroupId) {
                $group = SellingSplitGroup::query()->whereKey($splitGroupId)->lockForUpdate()->first();
                if (! $group || $group->selling_id !== $selling->id) {
                    throw new \RuntimeException('Split group not found for this bill');
                }
                if ($group->status === 'paid') {
                    throw new \RuntimeException('Split group already paid');
                }
                $groupRemaining = (float) $group->total - (float) $group->paid_total;
            } elseif ($selling->hasSplit()) {
                // Bill split: payment WAJIB dialokasikan ke sebuah group (FR-3.2)
                throw new \RuntimeException('Split bill: allocate payment to a split group');
            }

            $amount = (float) ($payment['amount'] ?? 0);

            // FR-4.3: over-payment dilarang (toleransi pembulatan < Rp 1)
            if ($splitGroupId) {
                if ($amount > $groupRemaining + 1) {
                    throw new \RuntimeException(
                        "Overpayment rejected: split group remaining {$groupRemaining} < amount {$amount}"
                    );
                }
            } elseif ($paidSoFar + $amount > $total + 1) {
                throw new \RuntimeException(
                    "Overpayment rejected: paid so far {$paidSoFar} + {$amount} > total {$total}"
                );
            }

            $pMethod = $payment['payment_method_id'] ?? null
                ? PaymentMethod::find($payment['payment_method_id'])
                : null;

            // Key stabil per (cart, method, amount) → retry idempotent; not shifting count
            $key = $payment['idempotency_key']
                ?? ($cartUuid
                    ? $cartUuid.'-'.substr(md5(($splitGroupId ? 'g'.$splitGroupId.'|' : '').($payment['payment_method_id'] ?? 'none').'|'.(int) ($amount * 100)), 0, 10)
                    : (string) Str::uuid());
            SellingPayment::query()->firstOrCreate(
                ['tenant_id' => $selling->tenant_id, 'idempotency_key' => $key],
                [
                    'selling_id' => $selling->id,
                    'split_group_id' => $splitGroupId,
                    'payment_method_id' => $payment['payment_method_id'] ?? null,
                    'amount' => $amount,
                    'is_cash' => $payment['is_cash'] ?? ($pMethod?->is_cash ?? true),
                    'status' => $payment['status'] ?? 'success',
                    'midtrans_ref' => $payment['midtrans_ref'] ?? null,
                    'payment_date' => $payment['payment_date'] ?? now(),
                ]
            );

            if ($group) {
                $group->refresh();
                $group->paid_total = (float) $group->payments()
                    ->where('status', 'success')
                    ->sum('amount');
                $group->status = $group->paid_total >= (float) $group->total ? 'paid' : $group->status;
                $group->save();
            }

            $this->recomputeStatus($selling, $pMethod, $group);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * FR-2.4: pindah bill antar meja (transaksional).
     */
    public function moveTable(int $sellingId, int $toTableId): Selling
    {
        return DB::transaction(function () use ($sellingId, $toTableId) {
            /** @var Selling $selling */
            $selling = Selling::query()->whereKey($sellingId)->lockForUpdate()->first();
            if (! $selling) {
                throw new \RuntimeException('Selling not found');
            }

            // Lock target table: cek-then-write race 2 move konkurren ke meja sama
            $target = \App\Models\Tenants\Table::query()->whereKey($toTableId)->lockForUpdate()->first();
            if (! $target) {
                throw new \RuntimeException('Target table not found');
            }
            // Meja target sudah terisi bill aktif lain → tolak (FR-2.4)
            if ($target->activeSelling() && $target->activeSelling()->id !== $selling->id) {
                throw new \RuntimeException('Target table already has an active bill');
            }

            $selling->table_id = $toTableId;
            $selling->save();

            return $selling;
        });
    }

    /**
     * FR-2.5: gabung bill B ke bill A (acara/batch besar). Item B pindah ke A,
     * total A dihitung ulang, B di-cancel. Tidak menghitung ulang stok (item sudah ter-reduce).
     */
    public function mergeBill(int $sourceSellingId, int $targetSellingId): Selling
    {
        return DB::transaction(function () use ($sourceSellingId, $targetSellingId) {
            // Lock ascending id — hindari deadlock 2 merge arah berlawanan
            $ids = collect([$sourceSellingId, $targetSellingId])->sort()->values();
            $lockedRows = Selling::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $target = $lockedRows->get($targetSellingId);
            $source = $lockedRows->get($sourceSellingId);
            if (! $target || ! $source) {
                throw new \RuntimeException('Selling not found');
            }
            if (in_array($target->status, ['cancelled', 'paid']) || in_array($source->status, ['cancelled', 'paid'])) {
                throw new \RuntimeException('Cannot merge a settled or cancelled bill');
            }

            $target->sellingDetails()
                ->getModel()->newQuery()
                ->where('selling_id', $source->id)
                ->update(['selling_id' => $target->id]);

            // Payment source ikut pindah ke target (jangan jadi yatim di bill cancelled)
            $target->payments()
                ->getModel()->newQuery()
                ->where('selling_id', $source->id)
                ->update(['selling_id' => $target->id]);

            $source->update([
                'status' => 'cancelled',
                'is_paid' => false,
                'cancelled_at' => now(),
                'cancel_reason' => 'merged into selling '.$target->code,
            ]);

            $this->recalcTotals($target);
            $this->recomputeStatus($target);
            $target->refresh();

            return $target;
        });
    }

    private function recalcTotals(Selling $selling): void
    {
        $details = $selling->sellingDetails()->get();
        $selling->update([
            'total_price' => (float) $details->sum('price'),
            'total_qty' => (float) $details->sum('qty'),
        ]);
    }
    private function recomputeStatus(Selling $selling, ?PaymentMethod $pMethod = null, ?SellingSplitGroup $group = null): void
    {
        $total = (float) $selling->total_price;
        $paid = (float) $selling->totalPaid();

        // Piutang: ada payment credit (produk/layanan diambil, bayar belakangan).
        // Selling final, receivable terpisah (CreateReceivableIfCredit).
        $hasCredit = $pMethod?->is_credit
            || $selling->payments()
                ->where('status', 'success')
                ->whereHas('paymentMethod', fn ($q) => $q->where('is_credit', true))
                ->exists();

        if ($hasCredit) {
            $selling->status = 'paid';
            $selling->is_paid = true;
            $selling->save();

            return;
        }

        // Split bill (FR-3.6): bill lunas saat SEMUA bagian lunas
        if ($selling->hasSplit()) {
            $allPaid = $selling->splitGroups()->where('status', '!=', 'paid')->count() === 0;
            $selling->status = $allPaid ? 'paid' : 'partially_paid';
            $selling->is_paid = $allPaid;
            $selling->save();

            return;
        }

        if ($paid >= $total) {
            $selling->status = 'paid';
            $selling->is_paid = true;
        } elseif ($paid > 0) {
            $selling->status = 'partially_paid';
            $selling->is_paid = false;
        } else {
            $selling->status = 'open';
            $selling->is_paid = false;
        }
        $selling->save();
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
