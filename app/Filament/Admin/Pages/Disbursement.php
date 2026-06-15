<?php

namespace App\Filament\Admin\Pages;

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\About;
use App\Tenant;
use App\Services\Tenants\DirectTransferService;
use App\Services\Tenants\FlipDataService;
use App\Services\Tenants\InsufficientBalanceException;
use App\Services\Tenants\DisbursementFailedException;
use Filament\Notifications\Notification;
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

    public array $tenants = [];

    public array $withdrawals = [];

    // ── Transfer Form Properties ──
    public ?string $selectedTenantId = null;

    public ?int $transferAmount = null;

    public ?string $transferNotes = null;

    public bool $showTransferForm = false;

    public bool $showConfirmation = false;

    public ?array $selectedTenantInfo = null;

    public ?int $calculatedFee = null;

    public ?int $calculatedNet = null;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        $this->loadFlipBalance();
        $this->loadFlipDisbursements();
        $this->loadTenants();
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

    public function loadTenants(): void
    {
        $centralTenants = Tenant::all();

        $tenantAbouts = About::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $centralTenants->pluck('id'))
            ->get()
            ->keyBy('tenant_id');

        $this->tenants = $centralTenants->map(function ($t) use ($tenantAbouts) {
            $data = is_string($t->data) ? json_decode($t->data, true) : $t->data;
            $about = $tenantAbouts->get($t->id);

            return [
                'id' => $t->id,
                'name' => $data['name'] ?? $t->id,
                'shop_name' => $about?->shop_name ?? '-',
                'bank_name' => $about?->bank_name ?? '-',
                'bank_code' => $about?->bank_code ?? '-',
                'bank_account_name' => $about?->bank_account_name ?? '-',
                'bank_account_number' => $about?->bank_account_number ?? '-',
                'has_bank' => !empty($about?->bank_account_number),
            ];
        })->toArray();
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
    }

    // ── Transfer Form Methods ──

    public function openTransferForm(): void
    {
        $this->showTransferForm = true;
        $this->showConfirmation = false;
        $this->selectedTenantId = null;
        $this->transferAmount = null;
        $this->transferNotes = null;
        $this->selectedTenantInfo = null;
        $this->calculatedFee = null;
        $this->calculatedNet = null;
    }

    public function closeTransferForm(): void
    {
        $this->showTransferForm = false;
        $this->showConfirmation = false;
    }

    public function updatedSelectedTenantId(): void
    {
        $this->selectedTenantInfo = collect($this->tenants)
            ->firstWhere('id', $this->selectedTenantId);

        $this->calculateFee();
    }

    public function updatedTransferAmount(): void
    {
        $this->calculateFee();
    }

    private function calculateFee(): void
    {
        if ($this->transferAmount && $this->transferAmount >= 50000) {
            $this->calculatedFee = 2500;
            $this->calculatedNet = $this->transferAmount - $this->calculatedFee;
        } else {
            $this->calculatedFee = null;
            $this->calculatedNet = null;
        }
    }

    public function showConfirmationDialog(): void
    {
        if (! $this->selectedTenantId) {
            Notification::make()
                ->title('Pilih tenant terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        if (! $this->transferAmount || $this->transferAmount < 50000) {
            Notification::make()
                ->title('Minimal transfer Rp 50.000')
                ->danger()
                ->send();
            return;
        }

        if (! $this->selectedTenantInfo['has_bank']) {
            Notification::make()
                ->title('Tenant belum mengatur informasi bank')
                ->danger()
                ->send();
            return;
        }

        $this->showConfirmation = true;
    }

    public function executeTransfer(): void
    {
        $this->showConfirmation = false;

        try {
            $service = app(DirectTransferService::class);
            $withdrawal = $service->transferToTenant(
                amount: $this->transferAmount,
                adminId: auth('admin')->user()->id,
                notes: $this->transferNotes ?? '',
            );

            Notification::make()
                ->title('Transfer berhasil!')
                ->body("Rp " . number_format($this->calculatedNet, 0, ',', '.') . " akan dikirim ke rekening tenant.")
                ->success()
                ->send();

            $this->closeTransferForm();
            $this->load();

        } catch (InsufficientBalanceException $e) {
            Notification::make()
                ->title('Saldo tidak mencukupi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (DisbursementFailedException $e) {
            Notification::make()
                ->title('Transfer gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Validasi gagal')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
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
