<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingSplitGroup;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Split bill (PRD 4.3, FR-3.1..3.7).
 *
 * Satu bill dipecah jadi beberapa bagian (SellingSplitGroup). Setiap bagian
 * = subset selling_details + qty pecahan (FR-3.4). Diskon/pajak/voucher
 * dihitung proporsional terhadap subtotal bagian (FR-3.5). Status bagian
 * pending/paid (FR-3.6); bill lunas saat semua bagian lunas.
 */
class SplitService
{
    /**
     * Pecah selling jadi N bagian. $groups = [[label, items: [[detail_id, qty], ...]], ...]
     * Setiap detail yang tidak disebut → masuk ke bagian terakhir otomatis
     * (default). Validasi: Σ total semua bagian = total bill (toleransi Rp 1).
     */
    public function split(Selling $selling, array $groups): array
    {
        return DB::transaction(function () use ($selling, $groups) {
            /** @var Selling $selling */
            $selling = Selling::query()->whereKey($selling->id)->lockForUpdate()->first();
            if (! $selling || in_array($selling->status, ['cancelled', 'paid'])) {
                throw new \RuntimeException('Bill cannot be split in its current status');
            }
            if ($selling->hasSplit()) {
                throw new \RuntimeException('Bill already split');
            }
            // Jangan split bill yg sudah ada payment (uang masuk tanpa alokasi group),
            // termasuk payment pending/partial (SB-8)
            if ($selling->payments()->exists()) {
                throw new \RuntimeException('Cannot split a bill that already has payments');
            }

            $details = $selling->sellingDetails()->get();

            // Tax rate dihitung ulang dari selling (total tax / subtotal asli)
            $subtotalAll = (float) $details->sum('price');
            $taxRate = $subtotalAll > 0 ? ((float) $selling->tax_price / $subtotalAll) : 0;

            // Diskon global bill (FR-3.5): proporsional terhadap subtotal tiap bagian.
            // Net bill = total_price - discount_price - total_discount_per_item.
            $gross = (float) $selling->total_price;
            $net = $gross - (float) $selling->discount_price - (float) $selling->total_discount_per_item;
            $globalDiscount = $gross - $net;

            // Kumpulkan subtotal per group (alokasi item) — SIMPAN dulu, baru buat group
            $allocatedQty = []; // detail_id => qty teralokasi
            $groupPlans = [];   // per group: label, subtotal, items

            foreach ($groups as $i => $groupDef) {
                $items = $groupDef['items'] ?? [];

                $groupSubtotal = 0.0;
                $groupDetailIds = [];

                foreach ($items as $item) {
                    $detailId = $item['detail_id'] ?? null;
                    $qty = (float) ($item['qty'] ?? 0);
                    $detail = $details->firstWhere('id', $detailId);
                    if (! $detail) {
                        throw new \RuntimeException("Selling detail {$detailId} not found");
                    }
                    $alreadyAllocated = $allocatedQty[$detailId] ?? 0;
                    if ($qty <= 0 || $alreadyAllocated + $qty > (float) $detail->qty) {
                        throw new \RuntimeException("Invalid qty {$qty} for detail {$detailId}");
                    }

                    $unitPrice = (float) $detail->price / (float) $detail->qty;
                    $groupSubtotal += $unitPrice * $qty;
                    $groupDetailIds[] = ['detail_id' => $detailId, 'qty' => $qty, 'unit_price' => $unitPrice];
                    $allocatedQty[$detailId] = $alreadyAllocated + $qty;
                }

                $groupPlans[] = ['label' => $groupDef['label'] ?? null, 'subtotal' => $groupSubtotal, 'items' => $groupDetailIds];
            }

            // Item yang tidak dialokasikan (penuh atau sisa qty) → ke bagian terakhir
            $unallocated = $details->filter(function ($detail) use ($allocatedQty) {
                $allocated = $allocatedQty[$detail->id] ?? 0;
                return $allocated < (float) $detail->qty;
            });
            if ($unallocated->count() > 0 && count($groupPlans) > 0) {
                $last = count($groupPlans) - 1;
                foreach ($unallocated as $detail) {
                    $unitPrice = (float) $detail->price / (float) $detail->qty;
                    $remaining = (float) $detail->qty - ($allocatedQty[$detail->id] ?? 0);
                    $groupPlans[$last]['subtotal'] += $unitPrice * $remaining;
                    $groupPlans[$last]['items'][] = ['detail_id' => $detail->id, 'qty' => $remaining, 'unit_price' => $unitPrice];
                }
            }

            // Cek Σ subtotal group = gross bill
            $sumSubtotal = array_sum(array_column($groupPlans, 'subtotal'));
            if (abs($sumSubtotal - $gross) > 1) {
                throw new \RuntimeException(
                    "Split subtotal {$sumSubtotal} does not match bill gross {$gross}"
                );
            }

            // FR-3.5: alokasi diskon proporsional ke subtotal; selisih pembulatan ke group terakhir
            $created = [];
            $distributedDiscount = 0.0;
            foreach ($groupPlans as $plan) {
                $share = ($plan['subtotal'] > 0 && $sumSubtotal > 0)
                    ? ($globalDiscount * $plan['subtotal'] / $sumSubtotal)
                    : 0.0;
                $created[] = $this->createGroup(
                    $selling,
                    $plan['label'],
                    $plan['subtotal'],
                    $taxRate,
                    $plan['items'],
                    round($share, 2)
                );
                $distributedDiscount += round($share, 2);
            }
            // Selisih pembulatan diskon → group terakhir (SB-14)
            if (count($created) > 0 && abs($distributedDiscount - $globalDiscount) > 0.01) {
                $diff = round($globalDiscount - $distributedDiscount, 2);
                $lastGroup = $created[count($created) - 1];
                $lastGroup->update([
                    'discount_amount' => round($lastGroup->discount_amount + $diff, 2),
                    'total' => round($lastGroup->total - $diff, 2),
                ]);
            }

            // FR-3.3: Σ total bagian wajib = net bill (toleransi < Rp 1)
            $sumGroups = array_sum(array_map(fn ($g) => $g->total, $created));
            if (abs($sumGroups - $net) > 1) {
                throw new \RuntimeException(
                    "Split total {$sumGroups} does not match bill net {$net}"
                );
            }

            $selling->status = 'partially_paid';
            $selling->is_paid = false;
            $selling->save();

            return $created;
        });
    }

    private function createGroup(Selling $selling, ?string $label, float $subtotal, float $taxRate, array $items, float $discountAmount = 0.0): SellingSplitGroup
    {
        $taxAmount = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $taxAmount - $discountAmount, 2);
        $group = SellingSplitGroup::query()->create([
            'tenant_id' => $selling->tenant_id,
            'selling_id' => $selling->id,
            'label' => $label,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'paid_total' => 0,
        ]);

        foreach ($items as $item) {
            $this->assignDetailToGroup($group, $item['detail_id'], $item['qty']);
        }

        return $group;
    }

    /**
     * FR-3.4: qty pecahan. Kalau qty utuh → pindah FK detail. Kalau pecahan
     * (qty < detail.qty) → clone detail row utk bagian, sisanya di detail asli.
     * Discount di-prorata mengikuti fraksi qty (jangan dobel di laporan).
     */
    private function assignDetailToGroup(SellingSplitGroup $group, int $detailId, float $qty): void
    {
        $detail = \App\Models\Tenants\SellingDetail::query()->find($detailId);
        if (! $detail) {
            throw new \RuntimeException("Selling detail {$detailId} not found");
        }

        $fullQty = (float) $detail->qty;
        $unitPrice = (float) $detail->price / $fullQty;
        $unitDiscount = (float) $detail->discount_price / $fullQty;

        if (abs($qty - $fullQty) < 0.01) {
            // qty utuh → pindah FK ke group ini
            $detail->update(['split_group_id' => $group->id]);
        } else {
            // pecahan: qty ini → clone (dengan discount prorata); sisanya di asli
            $clone = $detail->replicate();
            $clone->qty = $qty;
            $clone->price = round($unitPrice * $qty, 2);
            $clone->discount_price = round($unitDiscount * $qty, 2);
            $clone->split_group_id = $group->id;
            $clone->save();

            $detail->update([
                'qty' => round($fullQty - $qty, 2),
                'price' => round($unitPrice * ($fullQty - $qty), 2),
                'discount_price' => round($unitDiscount * ($fullQty - $qty), 2),
            ]);
        }
    }
}
