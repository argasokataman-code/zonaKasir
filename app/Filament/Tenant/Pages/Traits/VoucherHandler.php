<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\CartItem;
use App\Models\Tenants\Voucher as TenantsVoucher;
use App\Services\VoucherService;
use Filament\Notifications\Notification;

trait VoucherHandler
{
    public function assignVoucher(string $code): void
    {
        $this->validateVoucher($code) ? $this->cartDetail['voucher'] = $code : null;
    }

    public function removeVoucher(): void
    {
        $this->cartDetail['voucher'] = null;
        $this->discount_price = 0;
        $this->calculateTotalPrice();
    }

    private function validateVoucher(string $code): bool
    {
        $voucherService = new VoucherService();
        $voucher = $voucherService->applyable($code, $this->total_price);
        if (! $voucher) {
            Notification::make('voucher_not_found')
                ->title(__('Voucher not found'))
                ->warning()
                ->send();

            return false;
        }

        $this->cartItems->each(function (CartItem $item) {
            if ($item->discount_price && $item->discount_price > 0) {
                $this->discount_price += $item->discount_price;
            }
        });
        $this->discount_price += $voucher->calculate();
        $this->total_price = $this->sub_total + ($this->sub_total * $this->tax / 100) - $this->discount_price;

        return true;
    }

    protected function loadAvailableVouchers(): void
    {
        $this->availableVoucher = TenantsVoucher::query()
            ->select('id', 'code', 'type', 'nominal', 'minimal_buying', 'start_date', 'expired', 'kuota')
            ->where('minimal_buying', '<=', $this->cartItems->sum('price'))
            ->where('start_date', '<=', today()->format('Y-m-d'))
            ->where('expired', '>=', today()->format('Y-m-d'))
            ->get();
    }
}