<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Subscription;
use Filament\Widgets\Widget;

class TrialBanner extends Widget
{
    protected static string $view = 'filament.tenant.widgets.trial-banner';

    public ?int $daysRemaining = null;
    public bool $isTrial = false;

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $subscription = Subscription::select('id', 'tenant_id', 'status', 'trial_ends_at')
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'trialing')
            ->latest()
            ->first();

        if ($subscription && $subscription->trial_ends_at) {
            $this->isTrial = true;
            $this->daysRemaining = (int) max(0, now()->diffInDays($subscription->trial_ends_at, false));
        }
    }
}
