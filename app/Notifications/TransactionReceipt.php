<?php

namespace App\Notifications;

use App\Models\Tenants\Selling;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionReceipt extends Notification
{
    use Queueable;

    public function __construct(public Selling $selling)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Transaction Completed',
            'message' => "Transaction #{$this->selling->id} completed. Total: {$this->selling->grand_total_price}",
            'selling_id' => $this->selling->id,
            'total_price' => $this->selling->total_price,
            'grand_total_price' => $this->selling->grand_total_price,
            'payment_method' => $this->selling->paymentMethod?->name,
            'member_name' => $this->selling->member?->name,
            'created_at' => $this->selling->created_at->toISOString(),
        ];
    }
}
