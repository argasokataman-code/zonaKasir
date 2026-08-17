<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingSplitGroup;
use App\Services\Tenants\SellingService;
use App\Services\Tenants\SplitService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

trait SplitBillHandler
{
    public ?int $splitSellingId = null;

    public string $splitConfigJson = '[]';

    public string $splitBillCartItemsJson = '[]';

    public function getSplitBillCartItems(): string
    {
        $items = $this->cartItems->map(fn ($item) => [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'name' => $item->product->name ?? 'Unknown',
            'qty' => $item->qty,
            'price' => $item->price,
            'discount_price' => $item->discount_price ?? 0,
            'unit_price' => $item->qty > 0 ? round($item->price / $item->qty, 2) : 0,
            'assigned_group' => -1,
        ])->toArray();

        $this->splitBillCartItemsJson = json_encode($items);

        return $this->splitBillCartItemsJson;
    }

    public function openSplitBillModal(): void
    {
        // No-op — just triggers Livewire re-render so @js($cartItems) in Blade gets fresh data
    }

    public function setSplitConfig(array $groups): void
    {
        $this->splitConfigJson = json_encode($groups);
        $this->confirmSplitBill();
    }

    public function confirmSplitBill(): void
    {
        $splitConfig = json_decode($this->splitConfigJson, true) ?? [];

        if (empty($splitConfig) || empty($this->cartItems)) {
            return;
        }

        $sellingService = app(SellingService::class);

        // Build data like normal payment flow
        $data = [
            'products' => $this->cartItems->map(fn ($item) => [
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'price' => $item->price,
                'discount_price' => $item->discount_price ?? 0,
                'price_unit_id' => $item->price_unit_id,
            ])->toArray(),
            'member_id' => $this->cartDetail['member_id'] ?? null,
            'note' => $this->cartDetail['note'] ?? null,
            'table_id' => $this->cartDetail['table_id'] ?? null,
            'discount_price' => floatval(str_replace(',', '', $this->cartDetail['discount_price'] ?? '0')),
            'voucher' => $this->cartDetail['voucher'] ?? null,
            'tax' => $this->tax,
            'cart_uuid' => $this->cartUuid ?? null,
            'allow_partial' => false,
            'payed_money' => 0,
            'friend_price' => false,
        ];

        // mapProductRequest computes total_price, total_cost, total_qty, money_changes, etc.
        $mapped = $sellingService->mapProductRequest($data);
        $data = array_merge($data, $mapped);

        // Create selling without payment
        $selling = DB::transaction(function () use ($data, $sellingService) {
            return $sellingService->create($data);
        });

        if (! $selling) {
            Notification::make()->danger()->title('Failed to create transaction')->send();
            return;
        }

        // Map cart_item IDs → selling_detail IDs (they differ after create)
        $details = $selling->sellingDetails()->get();
        $cartToDetail = [];
        foreach ($this->cartItems as $cartItem) {
            $match = $details->firstWhere('product_id', $cartItem->product_id);
            if ($match) {
                $cartToDetail[$cartItem->id] = $match->id;
            }
        }

        // Build groups array for SplitService using correct detail_ids
        $groups = array_map(function ($group) use ($cartToDetail) {
            return [
                'label' => $group['label'] ?? null,
                'items' => array_map(function ($item) use ($cartToDetail) {
                    return [
                        'detail_id' => $cartToDetail[$item['detail_id']] ?? $item['detail_id'],
                        'qty' => (float) $item['qty'],
                    ];
                }, $group['items'] ?? []),
            ];
        }, $splitConfig);

        try {
            $splitService = app(SplitService::class);
            $splitService->split($selling, $groups);
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Split failed: ' . $e->getMessage())->send();
            return;
        }

        // Store selling ID for per-group payment
        $this->splitSellingId = $selling->id;

        // Clear cart + reset
        $this->cartItems->each->delete();
        $this->refreshCart();
        $this->cartUuid = null;

        // Dispatch to Alpine: show per-group payment
        $groupsData = SellingSplitGroup::where('selling_id', $selling->id)
            ->with('details.product')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'label' => $g->label,
                'total' => $g->total,
                'paid_total' => $g->paid_total,
                'status' => $g->status,
                'remaining' => $g->total - $g->paid_total,
                'items' => $g->details->map(fn ($d) => [
                    'name' => $d->product->name ?? 'Unknown',
                    'qty' => $d->qty,
                    'price' => $d->price,
                ])->toArray(),
            ])
            ->toArray();

        $this->dispatch('split-bill-created', sellingId: $selling->id, groups: $groupsData);
    }

    /**
     * Pay a specific split group.
     */
    public function paySplitGroup(int $splitGroupId, int $paymentMethodId, float $amount): void
    {
        $selling = Selling::find($this->splitSellingId);
        if (! $selling) {
            return;
        }

        $group = SellingSplitGroup::find($splitGroupId);
        if (! $group || $group->selling_id !== $selling->id) {
            return;
        }

        $remaining = $group->total - $group->paid_total;
        if ($amount < $remaining - 0.01) {
            Notification::make()->danger()->title('Amount does not cover the remaining balance')->send();
            return;
        }

        $sellingService = app(SellingService::class);
        $sellingService->addPayment($selling, [
            'payment_method_id' => $paymentMethodId,
            'amount' => $amount,
        ], null, $splitGroupId);

        // Check if all groups are paid
        $allPaid = SellingSplitGroup::where('selling_id', $selling->id)
            ->whereColumn('paid_total', '<', 'total')
            ->doesntExist();

        if ($allPaid) {
            $selling->update(['status' => 'paid', 'is_paid' => true]);
        }

        $this->dispatch('split-group-paid', splitGroupId: $splitGroupId, allPaid: $allPaid, sellingId: $selling->id);
    }
}
