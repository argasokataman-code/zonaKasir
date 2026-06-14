<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlipWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $webhookToken = config('flip.webhook_token');

        $incomingToken = $payload['token'] ?? null;

        if (! $webhookToken || $incomingToken !== $webhookToken) {
            Log::warning('Flip webhook: Invalid token', [
                'payload' => $payload,
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $disbursementId = $payload['id'];
        $status = $payload['status'];

        $withdrawal = Withdrawal::where('disburse_id', $disbursementId)->first();

        if (! $withdrawal) {
            Log::warning('Flip webhook: Withdrawal not found', [
                'disbursement_id' => $disbursementId,
                'payload' => $payload,
            ]);
            return response()->json(['message' => 'Withdrawal not found'], 404);
        }

        $newStatus = match ($status) {
            'DONE' => 'completed',
            'CANCELLED', 'FAILED' => 'failed',
            default => $withdrawal->status,
        };

        if ($newStatus !== $withdrawal->status) {
            $withdrawal->update([
                'status' => $newStatus,
                'disburse_response' => $payload,
                'processed_at' => now(),
            ]);
            Log::info('Flip webhook: Withdrawal status updated', [
                'withdrawal_id' => $withdrawal->id,
                'new_status' => $newStatus,
            ]);
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
