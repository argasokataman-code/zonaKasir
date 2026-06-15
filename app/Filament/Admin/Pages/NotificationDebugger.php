<?php

namespace App\Filament\Admin\Pages;

use App\Tenant;
use Filament\Pages\Page;

class NotificationDebugger extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Notif Debug';

    protected static ?string $slug = 'notif-debug';

    protected static string $view = 'filament.admin.pages.notification-debugger';

    protected static ?string $navigationGroup = 'Debug';

    public static function shouldRegisterNavigation(): bool
    {
        return app()->environment('local');
    }

    public array $results = [];

    public function mount(): void
    {
        $this->results = [];
    }

    public function check()
    {
        $output = [];
        $tenants = Tenant::where('is_active', true)->get();
        $output[] = "Active tenants: {$tenants->count()}";

        foreach ($tenants as $t) {
            try {
                $userCount = \App\Models\Tenants\User::count();
                $firstUser = \App\Models\Tenants\User::first();
                $notifCount = 0;
                $latestNotif = null;
                if ($firstUser) {
                    $notifCount = $firstUser->notifications()->count();
                    $latestNotif = $firstUser->notifications()->latest()->first();
                }
                $output[] = "Tenant {$t->id}: {$userCount} users, {$notifCount} notifications";
                if ($latestNotif) {
                    $output[] = "  Latest: " . json_encode($latestNotif->data);
                }
            } catch (\Throwable $e) {
                $output[] = "Tenant {$t->id}: ERROR - " . $e->getMessage();
            }
        }

        $this->results = $output;
    }
}
