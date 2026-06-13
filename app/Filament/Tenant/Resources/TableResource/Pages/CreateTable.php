<?php

namespace App\Filament\Tenant\Resources\TableResource\Pages;

use App\Filament\Tenant\Resources\TableResource;
use App\Filament\Tenant\Resources\Traits\RedirectToIndex;
use App\Models\Tenants\Table as TableModel;
use App\Services\PlanAccessService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTable extends CreateRecord
{
    use RedirectToIndex;

    protected static string $resource = TableResource::class;

    protected function beforeCreate(): void
    {
        $access = app(PlanAccessService::class);
        $tenantId = auth()->user()->tenant_id;
        $currentCount = TableModel::count();

        if (! $access->canCreateStore($tenantId, $currentCount)) {
            Notification::make()
                ->title('Store limit reached')
                ->body('Upgrade your plan to add more stores. Max stores allowed: '.$access->getMaxStores($tenantId))
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
