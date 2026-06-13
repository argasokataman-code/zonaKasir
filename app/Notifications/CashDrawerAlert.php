<?php

namespace App\Notifications;

use App\Models\Tenants\CashDrawer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CashDrawerAlert extends Notification
{
    use Queueable;

    public function __construct(public CashDrawer $cashDrawer, public string $action, public ?string $message = null)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $actionLabel = match ($this->action) {
            'opened' => 'Cash Drawer Opened',
            'closed' => 'Cash Drawer Closed',
            'discrepancy' => 'Cash Drawer Discrepancy',
            default => 'Cash Drawer Alert',
        };
        $openedByName = $this->cashDrawer->openedBy?->name;
        $message = $this->message ?? "Cash drawer {$this->action} by " . ($openedByName ?? 'Unknown');

        return [
            'title' => $actionLabel,
            'message' => $message,
            'cash_drawer_id' => $this->cashDrawer->id,
            'action' => $this->action,
            'opening_balance' => $this->cashDrawer->cash,
            'opened_by' => $this->cashDrawer->opened_by,
            'closed_by' => $this->cashDrawer->closed_by,
            'created_at' => $this->cashDrawer->created_at->toISOString(),
        ];
    }
}
