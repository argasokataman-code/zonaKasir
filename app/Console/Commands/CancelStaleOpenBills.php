<?php

namespace App\Console\Commands;

use App\Models\Tenants\Selling;
use App\Models\Tenants\SellingDetail;
use App\Models\Tenants\Setting;
use App\Services\TenantContext;
use App\Services\Tenants\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelStaleOpenBills extends Command
{
    protected $signature = 'selling:cancel-stale {--hours=12 : idle threshold} {--tenant= : single tenant id}';

    protected $description = 'Auto-cancel open/partially_paid bills idle beyond threshold, reverse stock (FR-1.6)';

    public function handle(StockService $stockService): int
    {
        $hours = (int) $this->option('hours');
        $threshold = $hours;

        if ($tenantId = $this->option('tenant')) {
            TenantContext::set($tenantId);
            $totalCancelled = $this->cancelForTenant($stockService);
            TenantContext::reset();
            $this->info("Cancelled {$totalCancelled} stale bills (idle > {$threshold}h)");
            return self::SUCCESS;
        }

        $tenants = \App\Tenant::query()->select('id')->get();

        $totalCancelled = 0;
        foreach ($tenants as $tenant) {
            TenantContext::set($tenant->id);
            $totalCancelled += $this->cancelForTenant($stockService);
        }

        TenantContext::reset();

        $this->info("Cancelled {$totalCancelled} stale bills (idle > {$threshold}h)");
        return self::SUCCESS;
    }

    private function cancelForTenant(StockService $stockService): int
    {
        $cutoff = now()->subHours((float) Setting::get('idle_bill_hours', (int) $this->option('hours')));

        $stale = Selling::query()
            ->whereIn('status', ['open', 'partially_paid'])
            ->where('updated_at', '<', $cutoff)
            // Jangan auto-cancel bill yg sudah ada payment success (uang masuk,
            // butuh review manual — jangan balikin stok seenaknya)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'success'))
            ->get();

        $cancelled = 0;
        foreach ($stale as $selling) {
            $this->cancelBill($selling, $stockService);
            $cancelled++;
        }

        return $cancelled;
    }

    private function cancelBill(Selling $selling, StockService $stockService): void
    {
        DB::transaction(function () use ($selling, $stockService) {
            // Lock bill: hindari race dgn addPayment kasir (RC-8/AU-2)
            $locked = Selling::query()
                ->whereKey($selling->id)
                ->whereIn('status', ['open', 'partially_paid'])
                ->lockForUpdate()
                ->first();
            if (! $locked) {
                return; // sudah diproses/berubah status
            }

            $locked->loadMissing('sellingDetails.product');

            foreach ($locked->sellingDetails as $detail) {
                $product = $detail->product;
                if (! $product || $product->is_non_stock) {
                    continue;
                }
                $stockService->addStock($product, $detail->qty);
            }

            $locked->update([
                'status' => 'cancelled',
                'is_paid' => false,
                'cancelled_at' => now(),
                'cancel_reason' => 'idle timeout (auto)',
            ]);
        });
    }
}
