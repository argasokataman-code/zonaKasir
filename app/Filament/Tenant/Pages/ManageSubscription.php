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
        return Plan::where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->orderBy('price_monthly')
            ->get()
            ->toArray();
    }

    public function getInvoices(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return Invoice::where('tenant_id', $tenantId)
            ->latest()
            ->get()
            ->toArray();
    }
}
