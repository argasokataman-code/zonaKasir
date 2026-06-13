<?php

namespace App\Filament\Tenant\Resources\UserResource\Pages;

use App\Filament\Tenant\Resources\UserResource;
use App\Models\Tenants\User;
use App\Services\PlanAccessService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return '/member/users';
    }

    protected function beforeCreate(): void
    {
        $access = app(PlanAccessService::class);
        $tenantId = auth()->user()->tenant_id;
        $currentCount = User::count();

        if (! $access->canCreateUser($tenantId, $currentCount)) {
            Notification::make()
                ->title('User limit reached')
                ->body('Upgrade your plan to add more users. Max users allowed: '.$access->getMaxUsers($tenantId))
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
