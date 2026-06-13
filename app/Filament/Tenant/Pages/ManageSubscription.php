<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PlanAccessService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManageSubscription extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.tenant.pages.subscription';

    protected static ?string $title = 'Subscription';

    protected static ?string $navigationLabel = 'Subscription';

    protected static ?string $slug = 'subscription';

    public ?array $data = [];

    public ?string $snapRedirectUrl = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $plans = Plan::where('is_active', true)->orderBy('price_monthly')->get();

        $planOptions = $plans->mapWithKeys(fn ($p) => [
            $p->id => "{$p->name} — Rp ".number_format($p->price_monthly, 0, ',', '.').'/bln'.($p->price_yearly ? ' (Rp '.number_format($p->price_yearly, 0, ',', '.').'/thn)' : ''),
        ])->toArray();

        return $form
            ->schema([
                Section::make('Pilih Paket')
                    ->schema([
                        Radio::make('data.plan_id')
                            ->label('Available Plans')
                            ->options($planOptions)
                            ->required(),
                        Select::make('data.billing_cycle')
                            ->label('Billing Cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->default('monthly')
                            ->required(),
                    ]),
            ]);
    }

    public function subscribe(): void
    {
        $data = $this->form->getState()['data'];

        $planId = $data['plan_id'];
        $billingCycle = $data['billing_cycle'];
        $tenantId = auth()->user()->tenant_id;

        try {
            $this->snapRedirectUrl = null;

            $plan = Plan::findOrFail($planId);

            $subscription = Subscription::where('tenant_id', $tenantId)
                ->whereIn('status', ['trialing', 'active'])
                ->latest()
                ->first();

            if (! $subscription) {
                throw new \RuntimeException('No active subscription found');
            }

            $invoice = app(InvoiceService::class)->createInvoice($subscription, 'midtrans');

            $subscription->update([
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'starts_at' => now(),
                'ends_at' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);

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
