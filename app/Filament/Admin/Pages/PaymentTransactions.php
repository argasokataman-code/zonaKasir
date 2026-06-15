<?php

namespace App\Filament\Admin\Pages;

use App\Tenant;
use App\Models\Tenants\MidtransPayment;
use Filament\Pages\Page;

class PaymentTransactions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $title = 'Transaction Payments';

    protected static string $view = 'filament.admin.pages.payment-transactions';

    public array $transactions = [];

    public array $stats = [
        'total_gross' => 0,
        'total_fee' => 0,
        'total_net' => 0,
        'count_settled' => 0,
        'count_pending' => 0,
    ];

    public function mount(): void
    {
        $this->loadTransactions();
    }

    public function loadTransactions(): void
    {
        $tenants = Tenant::all();
        $tenantMap = [];
        foreach ($tenants as $t) {
            $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
            $tenantMap[$t->id] = $data['name'] ?? $t->id;
        }

        $payments = MidtransPayment::withoutGlobalScope('tenant')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $this->transactions = $payments->map(fn ($p) => [
            'tenant_id' => $p->tenant_id,
            'tenant_name' => $tenantMap[$p->tenant_id] ?? $p->tenant_id,
            'order_id' => $p->order_id,
            'gross_amount' => $p->gross_amount,
            'fee_midtrans' => $p->fee_midtrans ?? 0,
            'fee_platform' => $p->fee_platform ?? 0,
            'net_amount' => $p->net_amount ?? 0,
            'status' => $p->status,
            'payment_type' => $p->payment_type,
            'payment_channel' => $p->payment_channel,
            'selling_id' => $p->selling_id,
            'created_at' => $p->created_at?->format('d M Y H:i'),
            'paid_at' => $p->paid_at?->format('d M Y H:i'),
        ])->toArray();

        // Aggregate stats
        $settled = $payments->filter(fn ($p) => in_array($p->status, ['settlement', 'capture']));
        $pending = $payments->filter(fn ($p) => $p->status === 'pending');
        $allSettled = MidtransPayment::withoutGlobalScope('tenant')
            ->whereIn('status', ['settlement', 'capture'])
            ->get();

        $this->stats = [
            'total_gross' => (int) $allSettled->sum('gross_amount'),
            'total_fee' => (int) $allSettled->sum('fee_midtrans') + (int) $allSettled->sum('fee_platform'),
            'total_net' => (int) $allSettled->sum('net_amount'),
            'count_settled' => $allSettled->count(),
            'count_pending' => MidtransPayment::withoutGlobalScope('tenant')
                ->where('status', 'pending')
                ->count(),
        ];
    }

    public function load(): void
    {
        $this->loadTransactions();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
