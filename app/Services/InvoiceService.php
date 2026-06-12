<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class InvoiceService
{
    public function createInvoice(
        Subscription $subscription,
        string $paymentMethod = 'manual',
        ?string $notes = null
    ): Invoice {
        return Tenancy::central(function () use ($subscription, $paymentMethod, $notes) {
            return DB::transaction(function () use ($subscription, $paymentMethod, $notes) {
                $subscription->refresh();
                $plan = $subscription->plan;

                if (! $plan) {
                    throw new Exception('Subscription has no associated plan');
                }

                $billingCycle = $subscription->billing_cycle ?? 'monthly';
                $amount = $billingCycle === 'yearly'
                    ? ($plan->price_yearly ?? $plan->price_monthly * 10)
                    : $plan->price_monthly;

                $invoice = Invoice::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'number' => 'INV-' . Str::upper(Str::random(10)),
                    'amount' => $amount,
                    'status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'notes' => $notes,
                ]);

                return $invoice;
            });
        });
    }

    public function markAsPaid(Invoice $invoice): Invoice
    {
        return Tenancy::central(function () use ($invoice) {
            if ($invoice->status === 'paid') {
                return $invoice;
            }

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $subscription = $invoice->subscription;

            if ($subscription && $subscription->status === 'trialing') {
                $subscription->update([
                    'status' => 'active',
                    'trial_ends_at' => null,
                ]);
            }

            return $invoice->fresh();
        });
    }

    public function markAsFailed(Invoice $invoice, ?string $reason = null): Invoice
    {
        return Tenancy::central(function () use ($invoice, $reason) {
            $invoice->update([
                'status' => 'failed',
                'notes' => $reason ? ($invoice->notes ? $invoice->notes . "\n" : '') . "Failed: {$reason}" : $invoice->notes,
            ]);

            return $invoice->fresh();
        });
    }

    public function processPayment(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'pending') {
            throw new Exception('Invoice is not in pending status');
        }

        try {
            return $this->markAsPaid($invoice);
        } catch (Exception $e) {
            return $this->markAsFailed($invoice, $e->getMessage());
        }
    }
}