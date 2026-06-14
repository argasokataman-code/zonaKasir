<?php

namespace App\Filament\Admin\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;

class PaymentSubscriptions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $title = 'Subscription Payments';

    protected static string $view = 'filament.admin.pages.payment-subscriptions';

    public array $subscriptionPayments = [];

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $invoices = Invoice::with('tenant', 'subscription.plan')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $this->subscriptionPayments = $invoices->map(fn ($inv) => [
            'tenant_id' => $inv->tenant_id,
            'tenant_name' => $inv->tenant?->name ?? $inv->tenant_id,
            'invoice_number' => $inv->number,
            'amount' => $inv->amount,
            'status' => $inv->status,
            'payment_method' => $inv->payment_method,
            'plan_name' => $inv->subscription?->plan?->name ?? '-',
            'created_at' => $inv->created_at?->format('d M Y H:i'),
            'paid_at' => $inv->paid_at?->format('d M Y H:i'),
        ])->toArray();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
