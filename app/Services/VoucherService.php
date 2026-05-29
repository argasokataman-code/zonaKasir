<?php

namespace App\Services;

use App\Exceptions\VoucherException;
use App\Models\Tenants\Voucher;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    public Voucher $voucher;

    private float $price;

    public function applyable(string $code, float $price): ?VoucherService
    {
        $today = today();
        /** @var Voucher $voucher */
        $voucher = Voucher::whereCode($code)
            ->first();
        if (! $voucher) {
            Log::warning("Voucher not found: {$code}");
            return null;
        }
        if ($voucher?->minimal_buying <= $price && $today->gte($voucher->start_date) && $today->lte($voucher->expired) && $voucher->kuota > 0) {
            $this->price = $price;
            $this->voucher = $voucher;
            Log::info("Voucher applied: {$code} for price {$price}");

            return $this;
        }

        Log::warning("Voucher conditions not met: {$code}", [
            'minimal_buying' => $voucher?->minimal_buying,
            'price' => $price,
            'start_date' => $voucher?->start_date,
            'expired' => $voucher?->expired,
            'kuota' => $voucher?->kuota,
        ]);
        return null;
    }

    public function calculate(): float
    {
        if (! $this->voucher) {
            Log::error('Calculate called before voucher assignment');
            throw VoucherException::notAssigned();
        }
        $discount = 0;
        if ($this->voucher->type == 'percentage') {
            $discount = ($this->price * $this->voucher->nominal / 100);
        }

        if ($this->voucher->type == 'flat') {
            $discount = $this->voucher->nominal;
        }

        return $discount;
    }

    public function reduceUsed()
    {
        if (! $this->voucher) {
            Log::error('reduceUsed called before voucher assignment');
            throw VoucherException::notAssigned();
        }

        $this->voucher->update([
            'kuota' => $this->voucher->kuota - 1,
        ]);
        Log::info("Voucher used: {$this->voucher->code}, remaining quota: {$this->voucher->kuota - 1}");
    }
}
