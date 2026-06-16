<?php

namespace App\Filament\Admin\Resources\WithdrawalApprovalResource\Pages;

use App\Filament\Admin\Resources\WithdrawalApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalApprovalResource::class;
}
