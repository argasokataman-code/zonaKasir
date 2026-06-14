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

    protected static ?string $title = 'Subscription';

    protected static ?string $navigationLabel = 'Subscription';

    protected static ?string $slug = 'subscription';

    public ?string $snapRedirectUrl = null;

    public function mount(): void
    {
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

            // Free plan: activate immediately
            if (($plan->price_monthly ?? 0) === 0) {
                $subscription = Subscription::where('tenant_id', $tenantId)
                    ->latest()
                    ->first();

                if ($subscription) {
                    $subscription->update([
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'billing_cycle' => 'monthly',
                        'starts_at' => now(),
                        'ends_at' => null,
                        'trial_ends_at' => null,
                    ]);
                } else {
                    Subscription::create([
                        'tenant_id' => $tenantId,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'billing_cycle' => 'monthly',
                        'starts_at' => now(),
                    ]);
                }

                Notification::make()
                    ->title('Plan activated')
                    ->body('Switched to '.$plan->name.' (Free)')
                    ->success()
                    ->send();

                return;
            }

            // Paid plan: find subscription
            $subscription = Subscription::where('tenant_id', $tenantId)
                ->latest()
                ->first();

            if ($subscription) {
                // If trial expired, mark as expired explicitly
                if ($subscription->status === 'trialing' && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
                    $subscription->update(['status' => 'expired']);
                }

                $subscription->update([
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                    // Status stays expired — webhook sets 'active' after payment
                ]);
            } else {
                $subscription = Subscription::create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $plan->id,
                    'status' => 'expired',
                    'billing_cycle' => $billingCycle,
                    'starts_at' => now(),
                ]);
            }

            $invoice = app(InvoiceService::class)->createInvoice($subscription, 'midtrans');

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
        return Plan::where('is_active', true)->orderBy('price_monthly')->get()->toArray();
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
