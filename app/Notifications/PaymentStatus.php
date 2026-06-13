<?php

namespace App\Notifications;

use App\Models\Tenants\MidtransPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentStatus extends Notification
{
    use Queueable;

    public function __construct(public MidtransPayment $payment, public string $oldStatus, public string $newStatus)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $statusLabel = match ($this->newStatus) {
            'settlement', 'capture' => 'Payment Successful',
            'pending' => 'Payment Pending',
            'deny', 'cancel' => 'Payment Failed',
            'expire' => 'Payment Expired',
            'refund' => 'Payment Refunded',
            default => "Payment {$this->newStatus}",
        };

        return [
            'title' => $statusLabel,
            'message' => "Order #{$this->payment->order_id}: {$statusLabel}. Amount: {$this->payment->gross_amount}",
            'order_id' => $this->payment->order_id,
            'payment_type' => $this->payment->payment_type,
            'gross_amount' => $this->payment->gross_amount,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'selling_id' => $this->payment->selling_id,
            'paid_at' => $this->payment->paid_at?->toISOString(),
        ];
    }
}
