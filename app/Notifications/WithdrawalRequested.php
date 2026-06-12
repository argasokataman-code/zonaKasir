<?php

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawalRequested extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Withdrawal requested: Rp ' . number_format($this->withdrawal->amount, 0, ',', '.'),
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
