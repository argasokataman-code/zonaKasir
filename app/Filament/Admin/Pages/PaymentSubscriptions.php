<?php

namespace App\Filament\Admin\Pages;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PaymentSubscriptions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $navigationGroup = 'Payment Gateway';

    protected static ?string $title = 'Subscription Payments';

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

    public function mount(): void
    {
        $this->load();
    }

    public function load(): void
    {
        // Recent invoices for the list
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

        // Summary statistics using aggregate queries (no loading all records)
        $this->totalTenants = Tenant::count();

        // Paid invoices stats
        $paidStats = Invoice::where('status', 'paid')
            ->selectRaw('COUNT(*) as count, COUNT(DISTINCT tenant_id) as tenant_count, COALESCE(SUM(amount), 0) as total')
            ->first();
        $this->totalPaidInvoices = $paidStats->count ?? 0;
        $this->totalPaidTenants = $paidStats->tenant_count ?? 0;
        $this->totalRevenue = $paidStats->total ?? 0;

        // Pending invoices stats
        $pendingStats = Invoice::where('status', 'pending')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();
        $this->totalPendingInvoices = $pendingStats->count ?? 0;
        $this->totalPendingAmount = $pendingStats->total ?? 0;

        // Subscription statistics
        $this->activeSubscriptions = Subscription::where('status', 'active')->count();
        $this->cancelledSubscriptions = Subscription::where('status', 'cancelled')->count();

        // Monthly recurring revenue (paid invoices in last 30 days)
        $this->monthlyRecurringRevenue = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('amount');

        // Revenue by plan
        $this->revenueByPlan = Invoice::where('status', 'paid')
            ->join('subscriptions', 'invoices.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select('plans.name as plan_name', DB::raw('SUM(invoices.amount) as total_amount'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('plans.name')
            ->get()
            ->toArray();

        // Monthly trend (last 6 months)
        $this->monthlyTrend = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('month')
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
        $this->totalFailedInvoices = Invoice::where('status', 'failed')->count();
        $this->totalProcessedInvoices = $this->totalPaidInvoices + $this->totalFailedInvoices;
        $this->paymentSuccessRate = $this->totalProcessedInvoices > 0
            ? round(($this->totalPaidInvoices / $this->totalProcessedInvoices) * 100, 1)
            : 0;

        // Avg Days to Payment: avg(paid_at - created_at)
        $avgDays = Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->selectRaw('AVG(DATEDIFF(paid_at, created_at)) as avg_days')
            ->value('avg_days');
        $this->avgDaysToPayment = round($avgDays ?? 0, 1);

        // Growth Rate MoM: (this month - last month) / last month * 100
        $thisMonth = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $lastMonth = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonth()->startOfMonth())
            ->where('paid_at', '<', now()->startOfMonth())
            ->sum('amount');
        $this->growthRateMoM = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 0;

        // Expiring Soon: subscriptions ending within 7 days
        $this->expiringSoon = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays(7))
            ->with('plan', 'tenant')
            ->get()
            ->map(fn ($sub) => [
                'tenant_name' => $sub->tenant?->name ?? $sub->tenant_id,
                'plan_name' => $sub->plan?->name ?? '-',
                'ends_at' => $sub->ends_at->format('d M Y'),
                'days_left' => now()->diffInDays($sub->ends_at),
            ])
            ->toArray();

        // Failed Payments: last 10 failed invoices
        $this->failedPayments = Invoice::where('status', 'failed')
            ->with('tenant')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($inv) => [
                'tenant_name' => $inv->tenant?->name ?? $inv->tenant_id,
                'invoice_number' => $inv->number,
                'amount' => $inv->amount,
                'created_at' => $inv->created_at?->format('d M Y H:i'),
                'notes' => $inv->notes,
            ])
            ->toArray();

        // Top 10 Tenants by Revenue
        $topTenantsRaw = Invoice::where('status', 'paid')
            ->select('tenant_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('tenant_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        // Get tenant names separately
        $tenantIds = $topTenantsRaw->pluck('tenant_id')->toArray();
        $tenantNames = Tenant::whereIn('id', $tenantIds)
            ->pluck('name', 'id')
            ->toArray();

        $this->topTenantsByRevenue = $topTenantsRaw->map(fn ($item) => [
            'tenant_name' => $tenantNames[$item->tenant_id] ?? $item->tenant_id,
            'total_amount' => $item->total_amount,
            'invoice_count' => $item->invoice_count,
        ])->toArray();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }
}
