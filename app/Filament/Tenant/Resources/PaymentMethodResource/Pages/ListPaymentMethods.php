<?php

namespace App\Filament\Tenant\Resources\PaymentMethodResource\Pages;

use App\Filament\Tenant\Resources\PaymentMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;
}
