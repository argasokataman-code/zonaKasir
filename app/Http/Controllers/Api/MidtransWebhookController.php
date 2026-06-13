<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\IdempotencyLog;
use App\Services\Tenants\MidtransGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? '';

        Log::info('Midtrans webhook received', ['payload' => $payload]);

        // Extract tenant ID from order_id prefix format: T{tenant_id}-{microtime}-{random(4)}
        $tenantId = $this->extractTenantId($orderId);

        if ($tenantId) {
            return $this->processWithTenantId($tenantId, $payload, $orderId);
        }

        // Legacy format: no tenant_id in order_id — try all tenants
        return $this->processBySearchingTenants($orderId, $payload);
    }

    private function processWithTenantId(int $tenantId, array $payload, string $orderId): JsonResponse
    {
        try {
            $tenant = \App\Tenant::find($tenantId);
            if (!$tenant) {
                Log::error('Midtrans webhook: tenant not found', ['tenant_id' => $tenantId]);
                return response()->json(['error' => 'Tenant not found'], 404);
            }

            tenancy()->initialize($tenant);
            app(MidtransGatewayService::class)->handleWebhook($payload);

            return response()->json(['status' => 'ok'], 200);

        } catch (\Throwable $e) {
            Log::error('Midtrans webhook processing failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function processBySearchingTenants(string $orderId, array $payload): JsonResponse
    {
        // Search across all tenants for this order_id
        $tenants = \App\Tenant::all();
        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                $found = MidtransPayment::where('order_id', $orderId)->exists();
                if ($found) {
                    Log::info('Midtrans webhook: found payment in tenant', [
                        'order_id' => $orderId,
                        'tenant_id' => $tenant->id,
                    ]);
                    app(MidtransGatewayService::class)->handleWebhook($payload);
                    return response()->json(['status' => 'ok'], 200);
                }
            } catch (\Throwable $e) {
                // Continue searching next tenant
            }
        }

        Log::error('Midtrans webhook: payment not found in any tenant', ['order_id' => $orderId]);
        return response()->json(['status' => 'not_found'], 404);
    }

    /**
     * Extract tenant ID from order_id format: T{tenant_id}-{microtime}-{random(4)}
     * Also handles legacy format: T-{microtime}-{random(4)} (no tenant_id)
     */
    private function extractTenantId(string $orderId): ?int
    {
        if (!str_starts_with($orderId, 'T')) {
            return null;
        }

        $parts = explode('-', substr($orderId, 1));

        // New format: T{tenant_id}-{microtime}-{random}
        if (isset($parts[0]) && is_numeric($parts[0]) && (int) $parts[0] > 0) {
            return (int) $parts[0];
        }

        // Legacy format: T-{microtime}-{random} — search all tenants for this payment
        return null;
    }
}
