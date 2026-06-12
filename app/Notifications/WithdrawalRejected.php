<?php

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WithdrawalRejected extends Notification
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
            'message' => 'Withdrawal rejected: ' . ($this->withdrawal->rejection_reason ?? 'No reason provided'),
            'withdrawal_id' => $this->withdrawal->id,
        ];
    }
}
