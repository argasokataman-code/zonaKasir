<?php

namespace App\Filament\Admin\Pages;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Withdrawal;
use App\Tenant;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PaymentSubscriptions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $title = 'Revenue & Earnings';

    protected static string $view = 'filament.admin.pages.payment-subscriptions';

    public array $subscriptionPayments = [];
    public int $totalTenants = 0;
    public int $totalPaidTenants = 0;
    public int $totalPaidInvoices = 0;
    public int $totalPendingInvoices = 0;
    public float $totalRevenue = 0;
    public float $totalPendingAmount = 0;
    public float $monthlyRecurringRevenue = 0;
    public int $activeSubscriptions = 0;
    public int $cancelledSubscriptions = 0;
    public array $revenueByPlan = [];
    public array $recentPayments = [];
    public array $monthlyTrend = [];

    // New widgets
    public float $churnRate = 0;
    public float $conversionRate = 0;
    public float $arpu = 0;
    public float $revenueForecast = 0;
    public float $paymentSuccessRate = 0;
    public float $avgDaysToPayment = 0;
    public float $growthRateMoM = 0;
    public int $totalFailedInvoices = 0;
    public int $totalProcessedInvoices = 0;
    public array $expiringSoon = [];
    public array $failedPayments = [];
    public array $topTenantsByRevenue = [];

    // Platform earnings
    public float $totalPlatformFees = 0;
    public float $totalWithdrawalFees = 0;
    public float $totalAppEarnings = 0;

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        // Build tenant name map from data JSON (no 'name' column on tenants table)
        $tenantNames = $this->buildTenantNameMap();

        // Recent invoices for the list
        $invoices = Invoice::select('id', 'tenant_id', 'subscription_id', 'number', 'amount', 'status', 'payment_method', 'created_at')
            ->with('subscription.plan:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $this->subscriptionPayments = $invoices->map(fn ($inv) => [
            'tenant_id' => $inv->tenant_id,
            'tenant_name' => $tenantNames[$inv->tenant_id] ?? $inv->tenant_id,
            'invoice_number' => $inv->number,
            'amount' => $inv->amount,
            'status' => $inv->status,
            'payment_method' => $inv->payment_method,
            'plan_name' => $inv->subscription?->plan?->name ?? '-',
            'created_at' => $inv->created_at?->format('d M Y H:i'),
            'paid_at' => $inv->paid_at?->format('d M Y H:i'),
        ])->toArray();

        // Summary statistics using aggregate queries
        $this->totalTenants = Tenant::count();

        // Paid invoices stats
        $paidStats = Invoice::where('invoices.status', 'paid')
            ->selectRaw('COUNT(*) as count, COUNT(DISTINCT invoices.tenant_id) as tenant_count, COALESCE(SUM(invoices.amount), 0) as total')
            ->first();
        $this->totalPaidInvoices = $paidStats->count ?? 0;
        $this->totalPaidTenants = $paidStats->tenant_count ?? 0;
        $this->totalRevenue = $paidStats->total ?? 0;

        // Pending invoices stats
        $pendingStats = Invoice::where('invoices.status', 'pending')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(invoices.amount), 0) as total')
            ->first();
        $this->totalPendingInvoices = $pendingStats->count ?? 0;
        $this->totalPendingAmount = $pendingStats->total ?? 0;

        // Subscription statistics
        $this->activeSubscriptions = Subscription::where('subscriptions.status', 'active')->count();
        $this->cancelledSubscriptions = Subscription::where('subscriptions.status', 'cancelled')->count();

        // Monthly recurring revenue (paid invoices in last 30 days)
        $this->monthlyRecurringRevenue = Invoice::where('invoices.status', 'paid')
            ->where('invoices.paid_at', '>=', now()->subDays(30))
            ->sum('invoices.amount');

        // Revenue by plan
        $this->revenueByPlan = Invoice::where('invoices.status', 'paid')
            ->join('subscriptions', 'invoices.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('plans.name as plan_name', DB::raw('SUM(invoices.amount) as total_amount'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('plans.name')
            ->get()
            ->toArray();

        // Monthly trend (last 6 months)
        $this->monthlyTrend = Invoice::where('invoices.status', 'paid')
            ->where('invoices.paid_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("TO_CHAR(invoices.paid_at, 'YYYY-MM') as month"),
                DB::raw('SUM(invoices.amount) as total_amount'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy(DB::raw("TO_CHAR(invoices.paid_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get()
            ->toArray();

        // === NEW WIDGETS ===

        // Churn Rate: cancelled / (active + cancelled)
        $totalSubs = $this->activeSubscriptions + $this->cancelledSubscriptions;
        $this->churnRate = $totalSubs > 0
            ? round(($this->cancelledSubscriptions / $totalSubs) * 100, 1)
            : 0;

        // Conversion Rate: paid tenants / total tenants
        $this->conversionRate = $this->totalTenants > 0
            ? round(($this->totalPaidTenants / $this->totalTenants) * 100, 1)
            : 0;

        // ARPU: total revenue / unique paid tenants
        $this->arpu = $this->totalPaidTenants > 0
            ? round($this->totalRevenue / $this->totalPaidTenants, 2)
            : 0;

        // Revenue Forecast: MRR * 12 (annual projection)
        $this->revenueForecast = $this->monthlyRecurringRevenue * 12;

        // Payment Success Rate: paid / (paid + failed)
        $this->totalFailedInvoices = Invoice::where('invoices.status', 'failed')->count();
        $this->totalProcessedInvoices = $this->totalPaidInvoices + $this->totalFailedInvoices;
        $this->paymentSuccessRate = $this->totalProcessedInvoices > 0
            ? round(($this->totalPaidInvoices / $this->totalProcessedInvoices) * 100, 1)
            : 0;

        // Avg Days to Payment: avg(paid_at - created_at)
        $avgDays = Invoice::where('invoices.status', 'paid')
            ->whereNotNull('invoices.paid_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (invoices.paid_at - invoices.created_at)) / 86400) as avg_days')
            ->value('avg_days');
        $this->avgDaysToPayment = round($avgDays ?? 0, 1);

        // Growth Rate MoM: (this month - last month) / last month * 100
        $thisMonth = Invoice::where('invoices.status', 'paid')
            ->where('invoices.paid_at', '>=', now()->startOfMonth())
            ->sum('invoices.amount');
        $lastMonth = Invoice::where('invoices.status', 'paid')
            ->where('invoices.paid_at', '>=', now()->subMonth()->startOfMonth())
            ->where('invoices.paid_at', '<', now()->startOfMonth())
            ->sum('invoices.amount');
        $this->growthRateMoM = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 0;

        // Expiring Soon: subscriptions ending within 7 days
        $this->expiringSoon = Subscription::select('id', 'tenant_id', 'plan_id', 'status', 'ends_at')
            ->where('subscriptions.status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays(7))
            ->with('plan:id,name')
            ->get()
            ->map(fn ($sub) => [
                'tenant_name' => $tenantNames[$sub->tenant_id] ?? $sub->tenant_id,
                'plan_name' => $sub->plan?->name ?? '-',
                'ends_at' => $sub->ends_at->format('d M Y'),
                'days_left' => now()->diffInDays($sub->ends_at),
            ])
            ->toArray();

        // Failed Payments: last 10 failed invoices
        $this->failedPayments = Invoice::select('id', 'tenant_id', 'number', 'amount', 'notes', 'created_at')
            ->where('invoices.status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($inv) => [
                'tenant_name' => $tenantNames[$inv->tenant_id] ?? $inv->tenant_id,
                'invoice_number' => $inv->number,
                'amount' => $inv->amount,
                'created_at' => $inv->created_at?->format('d M Y H:i'),
                'notes' => $inv->notes,
            ])
            ->toArray();

        // Top 10 Tenants by Revenue
        $topTenantsRaw = Invoice::where('invoices.status', 'paid')
            ->select('invoices.tenant_id', DB::raw('SUM(invoices.amount) as total_amount'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('invoices.tenant_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        $this->topTenantsByRevenue = $topTenantsRaw->map(fn ($item) => [
            'tenant_name' => $tenantNames[$item->tenant_id] ?? $item->tenant_id,
            'total_amount' => $item->total_amount,
            'invoice_count' => $item->invoice_count,
        ])->toArray();

        // ── Platform earnings (non-subscription) ──
        $this->totalPlatformFees = (float) MidtransPayment::withoutGlobalScope('tenant')
            ->whereIn('status', ['settlement', 'capture'])
            ->sum('fee_platform');

        $this->totalWithdrawalFees = (float) Withdrawal::withoutGlobalScope('tenant')
            ->where('status', 'completed')
            ->sum('fee_amount');

        $this->totalAppEarnings = $this->totalRevenue + $this->totalPlatformFees + $this->totalWithdrawalFees;
    }

    /**
     * Build tenant_id => name map from tenants.data JSON column.
     * Fallback to tenancy_email or tenant_id if no name found.
     */
    private function buildTenantNameMap(): array
    {
        $tenants = Tenant::select('id', 'data', 'tenancy_email')->get();
        $map = [];

        foreach ($tenants as $t) {
            $data = is_string($t->data) ? json_decode($t->data, true) : ($t->data ?? []);
            $name = $data['name'] ?? $data['full_name'] ?? $t->tenancy_email ?? $t->id;
            $map[$t->id] = $name;
        }

        return $map;
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
