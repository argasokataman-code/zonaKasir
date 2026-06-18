<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Models\Tenants\MidtransPayment;
use App\Services\Tenants\MidtransGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? '';

        Log::info('Midtrans webhook received', ['order_id' => $orderId]);

        // Handle subscription payments (order_id: SUB-{id}-...)
        if (str_starts_with($orderId, 'SUB-')) {
            return $this->handleSubscription($payload);
        }

        // Handle selling payments (order_id: T{tenant_id}-...)
        $tenantId = $this->extractTenantId($orderId);

        if ($tenantId) {
            try {
                app(MidtransGatewayService::class)->handleWebhook($payload);

                return response()->json(['status' => 'ok'], 200);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
            } catch (\Throwable $e) {
                Log::error('Midtrans webhook processing failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['status' => 'not_found'], 404);
    }

    private function handleSubscription(array $payload): JsonResponse
    {
        $orderId = $payload['order_id'] ?? '';
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

        $subscription = Subscription::select('id', 'tenant_id', 'plan_id', 'status', 'billing_cycle', 'ends_at', 'starts_at')->find((int) $subscriptionId);

        if (! $subscription) {
            Log::error('Subscription not found', ['subscription_id' => $subscriptionId]);
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $invoice = $subscription->invoices()->select('id', 'midtrans_order_id', 'midtrans_transaction_id', 'midtrans_notification_payload', 'status')->latest()->first();

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
                'status' => 'active',
                'starts_at' => $subscription->ends_at && $subscription->ends_at->isPast() ? now() : ($subscription->starts_at ?? now()),
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

    private function extractTenantId(string $orderId): ?int
    {
        if (!str_starts_with($orderId, 'T')) {
            return null;
        }

        $parts = explode('-', substr($orderId, 1));

        if (isset($parts[0]) && is_numeric($parts[0]) && (int) $parts[0] > 0) {
            return (int) $parts[0];
        }

        return null;
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
