<?php

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferFailed extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $grossAmount = number_format(
            $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0), 0, ',', '.'
        );
        $netAmount = number_format($this->withdrawal->amount, 0, ',', '.');
        $error = $this->withdrawal->disburse_response['error'] ?? 'Unknown error';

        return [
            'message'         => "Transfer ke tenant GAGAL: Rp {$netAmount}",
            'withdrawal_id'   => $this->withdrawal->id,
            'gross_amount'    => $grossAmount,
            'net_amount'      => $netAmount,
            'error'           => $error,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'timestamp'       => now()->toISOString(),
        ];
    }
}
