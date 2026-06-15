<?php

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransferCompleted extends Notification
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

        return [
            'message'         => "Transfer ke tenant BERHASIL: Rp {$netAmount}",
            'withdrawal_id'   => $this->withdrawal->id,
            'transaction_id'  => $this->withdrawal->disburse_id,
            'gross_amount'    => $grossAmount,
            'fee_amount'      => $this->withdrawal->fee_amount ?? 0,
            'net_amount'      => $this->withdrawal->amount,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'status'          => $this->withdrawal->status,
            'timestamp'       => now()->toISOString(),
        ];
    }
}
