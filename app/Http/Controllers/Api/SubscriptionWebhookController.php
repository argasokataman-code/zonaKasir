<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? '';

        Log::info('Subscription webhook received', ['order_id' => $orderId]);

        if (! str_starts_with($orderId, 'SUB-')) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $parts = explode('-', $orderId);
        $subscriptionId = $parts[1] ?? null;

        if (! $subscriptionId || ! is_numeric($subscriptionId)) {
            Log::error('Invalid subscription order ID format', ['order_id' => $orderId]);

            return response()->json(['error' => 'Invalid order ID'], 400);
        }

        $serverKey = config('midtrans.server_key');

        if (! $this->verifySignature($payload, $serverKey)) {
            Log::critical('Subscription webhook signature verification failed', $payload);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $transactionId = $payload['transaction_id'] ?? '';

        $subscription = Subscription::find((int) $subscriptionId);

        if (! $subscription) {
            Log::error('Subscription not found', ['subscription_id' => $subscriptionId]);

            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $invoice = $subscription->invoices()->latest()->first();

        if (! $invoice) {
            Log::error('Invoice not found for subscription', [
                'subscription_id' => $subscriptionId,
            ]);

            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice->update([
            'midtrans_order_id' => $orderId,
            'midtrans_transaction_id' => $transactionId,
            'midtrans_notification_payload' => $payload,
        ]);

        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            app(InvoiceService::class)->markAsPaid($invoice);

            $subscription->update([
                'starts_at' => $subscription->ends_at ?? now(),
                'ends_at' => $subscription->billing_cycle === 'yearly'
                    ? now()->addYear()
                    : now()->addMonth(),
            ]);

            Log::info('Subscription invoice paid, subscription extended', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'new_ends_at' => $subscription->fresh()->ends_at,
            ]);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $invoice->update(['status' => 'failed']);

            Log::info('Subscription payment failed', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscriptionId,
                'status' => $transactionStatus,
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function verifySignature(array $payload, string $serverKey): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $provided = $payload['signature_key'] ?? '';

        return hash_equals($signature, $provided);
    }
}
