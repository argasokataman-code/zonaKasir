<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        Log::info('Midtrans webhook received', ['payload' => $payload]);

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
}
