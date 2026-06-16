<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;

class TenantNotifications extends Widget
{
    protected static string $view = 'filament.tenant.widgets.tenant-notifications';

    protected int | string | array $columnSpan = 'full';

    protected static string $pollingInterval = '30s';

    public function getViewData(): array
    {
        $user = auth()->user();
        if (! $user) {
            return ['notifications' => [], 'unreadCount' => 0, 'totalCount' => 0, 'showAll' => false];
        }

        $totalUnread = $user->unreadNotifications()->count();

        return [
            'notifications' => $user->notifications()->latest()->take(5)->get(),
            'unreadCount' => $totalUnread > 5 ? 5 : $totalUnread,
            'totalCount' => $totalUnread,
            'showAll' => $totalUnread > 5,
        ];
    }

    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();
        if ($notification && is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        $this->dispatch('refreshNotifications');
    }

    public function markAllAsRead(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('refreshNotifications');
    }
}
