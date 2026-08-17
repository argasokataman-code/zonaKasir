<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PlanAccessService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManageSubscription extends Page
{

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.tenant.pages.subscription';

    protected static string $layout = 'filament-panels::components.layout.base';

    protected static ?string $title = 'Subscription';

    protected static ?string $navigationLabel = 'Subscription';

    protected static ?string $slug = 'subscription';

    public ?string $snapRedirectUrl = null;

    public bool $showPaymentSuccess = false;

    public string $paymentStatus = '';

    public function mount(): void
    {
        // Prevent empty action modals from rendering (PHP 8.4 root element detection fix)
        $this->hasActionsModalRendered = true;
        $this->hasInfolistsModalRendered = true;
        $this->hasFormsModalRendered = true;

        // ── Midtrans redirect params (after payment) ──
        $statusCode = request()->query('status_code');
        $orderStatus = request()->query('transaction_status');
        if ($statusCode) {
            $this->paymentStatus = $orderStatus ?? ($statusCode === '200' ? 'success' : 'failed');
            $this->showPaymentSuccess = $statusCode === '200';
        }

        $planId = request()->query('plan_id');
        $billing = request()->query('billing', 'monthly');

        if ($planId) {
            $this->subscribePlan((int) $planId, $billing);
        }
    }

    public function subscribePlan(int $planId, string $billingCycle = 'monthly'): void
    {
        $this->processSubscription($planId, $billingCycle);
    }

    private function processSubscription(int $planId, string $billingCycle): void
    {
        $tenantId = auth()->user()->tenant_id;

        try {
            $this->snapRedirectUrl = null;

            $plan = Plan::findOrFail($planId);

            // On-Premise / price=0 plans: cannot subscribe via this page
            if (($plan->price_monthly ?? 0) === 0) {
                Notification::make()
                    ->title('Paket ini tidak tersedia untuk pembelian online')
                    ->body('Hubungi admin untuk paket '.$plan->name)
                    ->warning()
                    ->send();

                return;
            }

            // Check if already has a pending invoice for this plan — prevent duplicate
            $existingSub = Subscription::where('tenant_id', $tenantId)->latest()->first();
            if ($existingSub) {
                $pendingInvoice = Invoice::where('subscription_id', $existingSub->id)
                    ->where('status', 'pending')
                    ->where('target_plan_id', $plan->id)
                    ->latest()
                    ->first();

                if ($pendingInvoice && $pendingInvoice->midtrans_redirect_url) {
                    // Already has a pending payment for this exact plan — reuse
                    $this->snapRedirectUrl = $pendingInvoice->midtrans_redirect_url;
                    return;
                }

                // Cancel stale pending invoices for different plans
                Invoice::where('subscription_id', $existingSub->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }

            // Find or create subscription — DO NOT change plan_id yet
            $subscription = $existingSub;

            if ($subscription) {
                // If trial expired, mark as expired explicitly
                if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
                    $subscription->update(['status' => 'expired']);
                }
                // Update billing cycle to match user's selection (invoice amount uses this)
                // ⚠️ plan_id not updated here — wait for webhook confirmation
                if ($subscription->billing_cycle !== $billingCycle) {
                    $subscription->update(['billing_cycle' => $billingCycle]);
                }
            } else {
                $subscription = Subscription::create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $plan->id,
                    'status' => 'expired',
                    'billing_cycle' => $billingCycle,
                    'starts_at' => now(),
                ]);
            }

            // Pass target plan to invoice (not the subscription's current plan)
            $invoice = app(InvoiceService::class)->createInvoice($subscription, 'midtrans', null, $plan);

            $this->snapRedirectUrl = $this->generateSnapRedirect($invoice, $subscription);
        } catch (\Throwable $e) {
            Log::error('Subscription failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->title('Subscription failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function generateSnapRedirect(Invoice $invoice, Subscription $subscription): ?string
    {
        try {
            $serverKey = config('midtrans.server_key');

            if (empty($serverKey)) {
                Log::warning('Midtrans server key not configured');

                Notification::make()
                    ->title('Invoice created')
                    ->body('Please complete payment manually. Contact admin if needed.')
                    ->warning()
                    ->send();

                return null;
            }

            $orderId = 'SUB-'.$subscription->id.'-'.time().'-'.random_int(1000, 9999);

            $isProduction = config('midtrans.environment') === 'production';
            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $invoice->amount,
                ],
                'customer_details' => [
                    'first_name' => $subscription->tenant_id,
                ],
                'callbacks' => [
                    'finish' => url('/member/subscription'),
                ],
            ];

            $response = Http::withBasicAuth($serverKey, '')
                ->post($baseUrl, $payload);

            if ($response->failed()) {
                Log::error('Midtrans Snap token failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $response->json(),
                ]);

                Notification::make()
                    ->title('Payment link generation failed')
                    ->body('Please try again or contact support.')
                    ->danger()
                    ->send();

                return null;
            }

            $redirectUrl = $response->json('redirect_url');

            $invoice->update(['midtrans_redirect_url' => $redirectUrl]);

            return $redirectUrl;
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap error', ['error' => $e->getMessage()]);

            Notification::make()
                ->title('Payment error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    public function getCurrentPlan(): ?array
    {
        $tenantId = auth()->user()->tenant_id;

        $access = app(PlanAccessService::class);
        $plan = $access->getPlan($tenantId);
        $sub = $access->getActiveSubscription($tenantId);

        if (! $plan) {
            // Trial without a plan — show generic info
            if ($sub && $sub->status === 'trialing') {
                $trialEndsAt = $sub->trial_ends_at;
                return [
                    'id' => null,
                    'name' => __('Trial'),
                    'price_monthly' => 0,
                    'price_yearly' => 0,
                    'features' => [],
                    'max_stores' => 1,
                    'max_users' => 1,
                    'billing_cycle' => $sub->billing_cycle ?? 'monthly',
                    'status' => 'trialing',
                    'is_on_trial' => true,
                    'trial_ends_at' => $trialEndsAt?->format('d M Y'),
                ];
            }

            return null;
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'price_monthly' => $plan->price_monthly,
            'price_yearly' => $plan->price_yearly,
            'features' => $plan->features ?? [],
            'max_stores' => $plan->max_stores,
            'max_users' => $plan->max_users,
            'billing_cycle' => $sub?->billing_cycle ?? 'monthly',
            'status' => $sub?->status ?? 'none',
            'is_on_trial' => $access->isOnTrial($tenantId),
        ];
    }

    public function getAvailablePlans(): array
    {
        return Plan::select('id', 'name', 'price_monthly', 'price_yearly', 'features', 'max_stores', 'max_users')
            ->where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->orderBy('price_monthly')
            ->get()
            ->toArray();
    }

    public function getInvoices(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return Invoice::select('id', 'tenant_id', 'subscription_id', 'status', 'target_plan_id', 'amount', 'number', 'payment_method', 'midtrans_redirect_url', 'created_at')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->get()
            ->toArray();
    }

    // ── On-Premise Methods ──

    public function getOnpremLicense(): ?array
    {
        if (! config('app.on_premise')) {
            return null;
        }

        $licenseKey = env('ONPREM_LICENSE_KEY');
        if (! $licenseKey) {
            return [
                'status' => 'invalid',
                'customer' => 'Unlicensed',
                'domain' => request()->getHost(),
                'type' => 'unknown',
                'issued_at' => null,
                'expires_at' => null,
                'features' => [],
            ];
        }

        $parts = explode('.', $licenseKey);
        $decoded = @base64_decode($parts[0] ?? '');
        $data = $decoded ? (@json_decode($decoded, true) ?? []) : [];

        return [
            'status' => 'active',
            'customer' => $data['customer'] ?? 'Licensed',
            'domain' => $data['domain'] ?? request()->getHost(),
            'type' => $data['type'] ?? 'managed',
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'features' => $data['features'] ?? ['Core POS', 'Offline Mode', 'Local Network'],
        ];
    }

    public function getServerHealth(): array
    {
        $phpVersion = phpversion();
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        $memoryTotal = @ini_get('memory_limit');
        $memoryUsed = round(memory_get_usage(true) / 1024 / 1024, 1);
        $memoryLimit = (int) str_replace(['M', 'G', 'K'], ['', '', ''], strtoupper($memoryTotal ?? '128M'));
        $memoryPercent = $memoryLimit > 0 ? round(($memoryUsed / $memoryLimit) * 100, 1) : 0;

        $loadAvg = sys_getloadavg();
        $uptime = @file_get_contents('/proc/uptime');
        $uptimeHours = $uptime ? round((float) explode(' ', $uptime)[0] / 3600, 1) : null;

        $dbStatus = 'unknown';
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        return [
            'php_version' => $phpVersion,
            'disk_total' => round($diskTotal / 1024 / 1024 / 1024, 2),
            'disk_used' => round($diskUsed / 1024 / 1024 / 1024, 2),
            'disk_percent' => $diskPercent,
            'memory_used' => $memoryUsed,
            'memory_limit' => $memoryLimit,
            'memory_percent' => $memoryPercent,
            'load_avg' => [
                '1min' => $loadAvg[0] ?? 0,
                '5min' => $loadAvg[1] ?? 0,
                '15min' => $loadAvg[2] ?? 0,
            ],
            'uptime_hours' => $uptimeHours,
            'database' => $dbStatus,
        ];
    }

    public function getServiceStatus(): array
    {
        $appVersion = config('app.version', '1.0.0');
        $lastUpdate = \App\Models\Tenants\About::select('updated_at')->first()?->updated_at;
        $lastBackup = null;
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = glob($backupPath.'/*.sql*');
            if (! empty($files)) {
                $latest = max(array_map('filemtime', $files));
                $lastBackup = \Carbon\Carbon::createFromTimestamp($latest);
            }
        }

        return [
            'app_version' => $appVersion,
            'last_update' => $lastUpdate?->diffForHumans(),
            'last_update_at' => $lastUpdate?->format('d M Y H:i'),
            'last_backup' => $lastBackup?->diffForHumans(),
            'last_backup_at' => $lastBackup?->format('d M Y H:i'),
        ];
    }

    public function getSupportInfo(): array
    {
        return [
            'email' => config('app.support_email', 'support@zonakasir.com'),
            'phone' => config('app.support_phone', '-'),
            'sla' => config('app.support_sla', '24 hours'),
            'docs_url' => config('app.docs_url', 'https://docs.zonakasir.com'),
        ];
    }

    public function requestSupport(): void
    {
        $tenantId = auth()->user()->tenant_id;
        $user = auth()->user();

        Notification::make()
            ->title(__('Support Request Sent'))
            ->body(__('Our team will contact you within 24 hours.'))
            ->success()
            ->send();
    }
}
