<?php

namespace App\Filament\Admin\Pages;

use App\Tenant;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\WithdrawalService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WithdrawalApproval extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Withdrawal Approval';
    protected static ?string $title = 'Pending Withdrawals';
    protected static string $view = 'filament.admin.pages.withdrawal-approval';

    public array $withdrawals = [];

    public function mount(): void
    {
        $this->loadPendingWithdrawals();
    }

    public function loadPendingWithdrawals(): void
    {
        $tenants = Tenant::all();
        $tenantMap = [];
        foreach ($tenants as $t) {
            $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
            $tenantMap[$t->id] = $data['name'] ?? $t->id;
        }

        $pending = Withdrawal::withoutGlobalScope('tenant')
            ->where('status', 'pending')
            ->with('requestedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->withdrawals = $pending->map(fn ($w) => [
            'tenant_id' => $w->tenant_id,
            'tenant_name' => $tenantMap[$w->tenant_id] ?? $w->tenant_id,
            'withdrawal_id' => $w->id,
            'type' => $w->type ?? 'tenant_request',
            'amount' => $w->amount,
            'fee_amount' => $w->fee_amount ?? 0,
            'bank_name' => $w->bank_name,
            'bank_account_name' => $w->bank_account_name,
            'bank_account_number' => $w->bank_account_number,
            'requested_by' => $w->requestedBy?->name ?? 'Unknown',
            'created_at' => $w->created_at?->format('d M Y H:i'),
            'status' => $w->status,
        ])->toArray();
    }

    public function approve(string $tenantId, int $withdrawalId): void
    {
        try {
            $adminId = auth()->id();
            app(WithdrawalService::class)->approve($withdrawalId, $adminId);

            Notification::make()->title('Withdrawal approved & disbursed via Flip')->success()->send();
            $this->loadPendingWithdrawals();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function reject(string $tenantId, int $withdrawalId): void
    {
        try {
            $adminId = auth()->id();
            app(WithdrawalService::class)->reject($withdrawalId, $adminId, 'Rejected by admin');

            Notification::make()->title('Withdrawal rejected')->warning()->send();
            $this->loadPendingWithdrawals();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
