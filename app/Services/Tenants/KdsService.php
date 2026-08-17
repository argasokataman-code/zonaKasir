<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KdsService
{
    public function orders(): Collection
    {
        return Selling::query()
            ->open()
            ->whereHas('sellingDetails', fn ($q) => $q->whereNull('kitchen_status'))
            ->with([
                'table:id,number',
                'sellingDetails' => fn ($q) => $q
                    ->whereNull('kitchen_status')
                    ->with('product:id,name'),
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function ($selling) {
                return [
                    'id' => $selling->id,
                    'code' => $selling->code,
                    'table' => $selling->table?->number,
                    'created_at' => $selling->created_at,
                    'items' => $selling->sellingDetails->map(fn ($d) => [
                        'id' => $d->id,
                        'product' => $d->product?->name,
                        'qty' => $d->qty,
                    ]),
                ];
            })
            ->values();
    }

    public function updateStatus(SellingDetail $detail, string $status): SellingDetail
    {
        abort_if(! in_array($status, [SellingDetail::KITCHEN_IN_PROGRESS, SellingDetail::KITCHEN_DONE]), 422, 'Invalid kitchen status');

        $detail = DB::transaction(function () use ($detail, $status) {
            $locked = SellingDetail::query()->lockForUpdate()->findOrFail($detail->id);
            $locked->update(['kitchen_status' => $status]);

            return $locked;
        });

        return $detail->fresh();
    }

    public function updateStatusById(int $detailId, string $status): SellingDetail
    {
        return $this->updateStatus(SellingDetail::query()->findOrFail($detailId), $status);
    }
}
