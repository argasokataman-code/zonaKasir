<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\CartItem;
use App\Models\Tenants\Setting;

trait PriceCalculation
{
    protected function calculateTotalPrice(): void
    {
        $this->sub_total = 0;

        $this->discount_price = 0;
        $this->cartItems->each(function (CartItem $item) {
            $priceUnit = $item->priceUnit?->selling_price;
            if ($priceUnit) {
                $priceUnit = $priceUnit * $item->qty;
            }

            $this->sub_total += $priceUnit ?? $item->price;
            if ($item->discount_price && $item->discount_price > 0) {
                $this->discount_price += $item->discount_price;
            }
        });

        $this->total_price = $this->sub_total + ($this->sub_total * $this->tax / 100) - $this->discount_price;
    }

    protected function calculateTotalPriceWithManualDiscount(float $manualDiscount): float
    {
        $this->discount_price += $manualDiscount;

        return $this->sub_total + ($this->sub_total * $this->tax / 100) - $this->discount_price;
    }
}