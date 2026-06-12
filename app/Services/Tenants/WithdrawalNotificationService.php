<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Withdrawal;
use Illuminate\Support\Facades\Notification;

class WithdrawalNotificationService
{
    public function notifyRequested(Withdrawal $withdrawal): void
    {
        $withdrawal->requestedBy->notify(new \App\Notifications\WithdrawalRequested($withdrawal));
    }

    public function notifyApproved(Withdrawal $withdrawal): void
    {
        $withdrawal->requestedBy->notify(new \App\Notifications\WithdrawalApproved($withdrawal));
    }

    public function notifyRejected(Withdrawal $withdrawal): void
    {
        $withdrawal->requestedBy->notify(new \App\Notifications\WithdrawalRejected($withdrawal));
    }

    public function notifyFailed(Withdrawal $withdrawal): void
    {
        $withdrawal->requestedBy->notify(new \App\Notifications\WithdrawalFailed($withdrawal));
    }
}
