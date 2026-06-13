<?php

namespace App\Filament\Admin\Pages;

use App\Tenant;
use App\Models\Tenants\Withdrawal;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class WithdrawalApproval extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Withdrawal Approval';
    protected static ?string $title = 'Pending Withdrawals';
    protected static string $view = 'filament.admin.pages.withdrawal-approval';

    public array $withdrawals = [];
    public ?int $selectedTenantId = null;

    public function mount(): void
    {
        $this->loadPendingWithdrawals();
    }

    public function loadPendingWithdrawals(): void
    {
        $this->withdrawals = [];

        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);

                $pending = Withdrawal::where('status', 'pending')
                    ->with('requestedBy')
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach ($pending as $withdrawal) {
                    $this->withdrawals[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name ?? $tenant->id,
                        'withdrawal_id' => $withdrawal->id,
                        'amount' => $withdrawal->amount,
                        'bank_name' => $withdrawal->bank_name,
                        'bank_account_name' => $withdrawal->bank_account_name,
                        'bank_account_number' => $withdrawal->bank_account_number,
                        'requested_by' => $withdrawal->requestedBy?->name ?? 'Unknown',
                        'created_at' => $withdrawal->created_at?->format('d M Y H:i'),
                        'status' => $withdrawal->status,
                    ];
                }
            } catch (\Throwable $e) {
                // Skip this tenant if initialization fails
                continue;
            }
        }

        tenancy()->end();
    }

    public function approve(string $tenantId, int $withdrawalId): void
    {
        try {
            $tenant = Tenant::find($tenantId);
            tenancy()->initialize($tenant);

            $withdrawal = Withdrawal::find($withdrawalId);
            if (!$withdrawal || $withdrawal->status !== 'pending') {
                Notification::make()->title('Withdrawal already processed')->danger()->send();
                return;
            }

            // Check 2-admin approval for > 25jt
            $singleAdminMax = config('midtrans.withdrawal_approval.single_admin_max', 25000000);
            $adminId = 1; // Admin user ID

            if ($withdrawal->amount > $singleAdminMax) {
                if ($withdrawal->approved_by === null) {
                    $withdrawal->update(['approved_by' => $adminId]);
                    Notification::make()->title('First approval recorded - needs second admin approval')->success()->send();
                } else {
                    $withdrawal->update(['status' => 'approved', 'processed_at' => now()]);
                    Notification::make()->title('Withdrawal approved')->success()->send();
                }
            } else {
                $withdrawal->update(['status' => 'approved', 'approved_by' => $adminId, 'processed_at' => now()]);
                Notification::make()->title('Withdrawal approved')->success()->send();
            }

            $this->loadPendingWithdrawals();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        } finally {
            tenancy()->end();
        }
    }

    public function reject(string $tenantId, int $withdrawalId): void
    {
        try {
            $tenant = Tenant::find($tenantId);
            tenancy()->initialize($tenant);

            $withdrawal = Withdrawal::find($withdrawalId);
            if (!$withdrawal || $withdrawal->status !== 'pending') {
                Notification::make()->title('Withdrawal already processed')->danger()->send();
                return;
            }

            $adminId = 1; // Admin user ID
            $withdrawal->update([
                'status' => 'rejected',
                'rejected_by' => $adminId,
                'rejection_reason' => 'Rejected by admin',
            ]);

            Notification::make()->title('Withdrawal rejected')->warning()->send();
            $this->loadPendingWithdrawals();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        } finally {
            tenancy()->end();
        }
    }
}
