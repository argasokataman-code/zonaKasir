<?php

namespace App\Observers;

use App\Models\Tenants\CashDrawer;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Setting;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Support\Str;

class SellingObserver extends AbstractObserver implements DataAwareRule
{
    protected $data = [];

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function creating(Selling $selling)
    {
        if (! $selling->date) {
            $selling->date = now()->format('Y-m-d H:i:s');
        }
        // ponytail: lockForUpdate on max(code) — 2 transaksi bersamaan gak bisa
        // dapat kode sama (lock row-level serialisasi di dalam transaction M1-C).
        // Unique index code (tenant_id, code) tetap jadi defense terakhir.
        $lastCode = Selling::query()
            ->where('code', 'like', 'SELL%')
            ->orderByDesc('code')
            ->lockForUpdate()
            ->value('code');
        $lastNumber = $lastCode ? (int) substr($lastCode, 4) : 0;
        $selling->code = 'SELL'.Str::of($lastNumber + 1)->padLeft(4, 0)->value();
        if (Setting::get('cash_drawer_enabled', false)) {
            $selling->cash_drawer_id = CashDrawer::lastOpened()->select('id')->first()->id;
        }
        $selling->user()->associate(auth()->user());
    }
}
