<?php

namespace App\Filament\Tenant\Resources\PaymentMethodResource\Pages;

use App\Filament\Tenant\Resources\PaymentMethodResource;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getRedirectUrl(): string
    {
        return '/member/payment-methods';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['payment_type'] !== 'qris') {
            $data['icon'] = null;
        }

        return $data;
    }
}
