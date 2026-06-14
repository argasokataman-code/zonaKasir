<?php

namespace App\Filament\Admin\Pages;

use App\Models\Tenants\Withdrawal;
use App\Tenant;
use App\Services\Tenants\FlipDataService;
use Filament\Pages\Page;

class Disbursement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static ?string $navigationLabel = 'Disbursement';

    protected static ?string $title = 'Disbursement (Flip)';

    protected static string $view = 'filament.admin.pages.disbursement';

    public ?array $flipBalance = null;

    public ?string $balanceError = null;

    public array $flipDisbursements = [];

    public array $withdrawals = [];

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->loadFlipBalance();
        $this->loadFlipDisbursements();
        $this->loadWithdrawals();
    }

    private function tenantMap(): array
    {
        $tenants = Tenant::all();
        $map = [];
        foreach ($tenants as $t) {
            $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
            $map[$t->id] = $data['name'] ?? $t->id;
        }
        return $map;
    }

    public function loadFlipBalance(): void
    {
        try {
            $this->flipBalance = app(FlipDataService::class)->getBalance();

            if ($this->flipBalance === null) {
                $this->balanceError = 'Failed to fetch balance from Flip. Check FLIP_SECRET_KEY config.';
            }
        } catch (\Throwable $e) {
            $this->balanceError = $e->getMessage();
        }
    }

    public function loadFlipDisbursements(): void
    {
        try {
            $data = app(FlipDataService::class)->getDisbursements();
            $this->flipDisbursements = is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            $this->flipDisbursements = [];
        }
    }

    public function loadWithdrawals(): void
    {
        $tenantMap = $this->tenantMap();

        $withdrawals = Withdrawal::withoutGlobalScope('tenant')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $this->withdrawals = $withdrawals->map(fn ($w) => [
            'tenant_id' => $w->tenant_id,
            'tenant_name' => $tenantMap[$w->tenant_id] ?? $w->tenant_id,
            'id' => $w->id,
            'amount' => $w->amount,
            'status' => $w->status,
            'bank_name' => $w->bank_name,
            'bank_code' => $w->bank_code,
            'bank_account_name' => $w->bank_account_name,
            'bank_account_number' => $w->bank_account_number,
            'disburse_id' => $w->disburse_id,
            'created_at' => $w->created_at?->format('d M Y H:i'),
            'processed_at' => $w->processed_at?->format('d M Y H:i'),
        ])->toArray();
    }

    public function refresh(): void
    {
        $this->load();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
