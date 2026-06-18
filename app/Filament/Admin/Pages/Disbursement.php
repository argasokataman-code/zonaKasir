<?php

namespace App\Filament\Admin\Pages;

use App\Models\Tenants\Withdrawal;
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

    // ── Search & Filter ──
    public ?string $withdrawalSearch = '';

    public ?string $withdrawalStatusFilter = '';

    public ?string $withdrawalTypeFilter = '';

    public int $withdrawalPage = 1;

    public int $withdrawalPerPage = 20;

    public int $withdrawalTotalCount = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->loadFlipBalance();
        $this->loadFlipDisbursements();
        $this->loadWithdrawals();
        $this->applyWithdrawalFilter();
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
        $tenants = \App\Tenant::select('id', 'data')->get();
        $tenantMap = [];
        foreach ($tenants as $t) {
            $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
            $tenantMap[$t->id] = $data['name'] ?? $t->id;
        }

        $allWithdrawals = Withdrawal::withoutGlobalScope('tenant')
            ->select('id', 'tenant_id', 'type', 'amount', 'fee_amount', 'status', 'bank_name', 'bank_code', 'bank_account_name', 'bank_account_number', 'disburse_id', 'created_at', 'processed_at')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        $this->withdrawals = $allWithdrawals->map(fn ($w) => [
            'tenant_id' => $w->tenant_id,
            'tenant_name' => $tenantMap[$w->tenant_id] ?? $w->tenant_id,
            'id' => $w->id,
            'type' => $w->type ?? 'tenant_request',
            'amount' => $w->amount,
            'fee_amount' => $w->fee_amount ?? 0,
            'status' => $w->status,
            'bank_name' => $w->bank_name,
            'bank_code' => $w->bank_code,
            'bank_account_name' => $w->bank_account_name,
            'bank_account_number' => $w->bank_account_number,
            'disburse_id' => $w->disburse_id,
            'created_at' => $w->created_at?->format('d M Y H:i'),
            'processed_at' => $w->processed_at?->format('d M Y H:i'),
        ])->toArray();

        $this->withdrawalTotalCount = count($this->withdrawals);
    }

    public function applyWithdrawalFilter(): void
    {
        $search = strtolower($this->withdrawalSearch ?? '');
        $status = $this->withdrawalStatusFilter ?? '';
        $type = $this->withdrawalTypeFilter ?? '';

        $filtered = $this->withdrawals;

        if ($search !== '') {
            $filtered = array_filter($filtered, function ($w) use ($search) {
                return str_contains(strtolower($w['tenant_name']), $search)
                    || str_contains(strtolower($w['bank_account_name']), $search)
                    || str_contains($w['bank_account_number'], $search)
                    || str_contains($w['disburse_id'] ?? '', $search);
            });
        }

        if ($status !== '') {
            $filtered = array_filter($filtered, fn ($w) => $w['status'] === $status);
        }

        if ($type !== '') {
            $filtered = array_filter($filtered, fn ($w) => $w['type'] === $type);
        }

        $this->withdrawalTotalCount = count($filtered);

        // Paginate
        $offset = ($this->withdrawalPage - 1) * $this->withdrawalPerPage;
        $this->withdrawals = array_slice($filtered, $offset, $this->withdrawalPerPage);
    }

    public function updatedWithdrawalSearch(): void
    {
        $this->withdrawalPage = 1;
        $this->applyWithdrawalFilter();
    }

    public function updatedWithdrawalStatusFilter(): void
    {
        $this->withdrawalPage = 1;
        $this->applyWithdrawalFilter();
    }

    public function updatedWithdrawalTypeFilter(): void
    {
        $this->withdrawalPage = 1;
        $this->applyWithdrawalFilter();
    }

    public function withdrawalNextPage(): void
    {
        $totalPages = ceil($this->withdrawalTotalCount / $this->withdrawalPerPage);
        if ($this->withdrawalPage < $totalPages) {
            $this->withdrawalPage++;
            $this->applyWithdrawalFilter();
        }
    }

    public function withdrawalPrevPage(): void
    {
        if ($this->withdrawalPage > 1) {
            $this->withdrawalPage--;
            $this->applyWithdrawalFilter();
        }
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
