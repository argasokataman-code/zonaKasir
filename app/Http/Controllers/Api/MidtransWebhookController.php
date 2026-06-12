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

        // Extract tenant ID from order_id prefix format: T{tenant_id}-{microtime}-{random(4)}
        $tenantId = $this->extractTenantId($orderId);

        if (!$tenantId) {
            Log::error('Midtrans webhook: invalid order_id format', ['order_id' => $orderId]);
            return response()->json(['error' => 'Invalid order_id format'], 400);
        }

        // Initialize tenant context (stancl/tenancy)
        // Note: abouts table is per-tenant, need tenant DB to verify
        try {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant) {
                Log::error('Midtrans webhook: tenant not found', ['tenant_id' => $tenantId]);
                return response()->json(['error' => 'Tenant not found'], 404);
            }

            // Initialize the tenant database
            tenancy()->initialize($tenant);

            // Delegate to gateway service
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

    /**
     * Extract tenant ID from order_id format: T{tenant_id}-{microtime}-{random(4)}
     */
    private function extractTenantId(string $orderId): ?int
    {
        if (!str_starts_with($orderId, 'T')) {
            return null;
        }

        $parts = explode('-', substr($orderId, 1));
        if (empty($parts[0]) || !is_numeric($parts[0])) {
            return null;
        }

        return (int) $parts[0];
    }
}
