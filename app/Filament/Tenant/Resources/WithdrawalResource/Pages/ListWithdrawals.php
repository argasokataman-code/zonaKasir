<?php

namespace App\Filament\Tenant\Resources\WithdrawalResource\Pages;

use App\Filament\Tenant\Resources\WithdrawalResource;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WithdrawalResource\Widgets\BalanceOverview::class,
        ];
    }
}
