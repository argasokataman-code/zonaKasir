<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;

class TenantNotifications extends Widget
{
    protected static string $view = 'filament.tenant.widgets.tenant-notifications';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();
        if (! $user) {
            return ['notifications' => []];
        }

        return [
            'notifications' => $user->notifications()->latest()->take(10)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }
}
