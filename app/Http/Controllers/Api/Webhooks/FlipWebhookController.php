<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class FlipWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('X-Signature'); // Flip might use different header
        $webhookToken = config('flip.webhook_token');

        // Basic token check for now, can be expanded to full signature verification
        if (! $webhookToken || ! Hash::check($payload['id'] . ':' . $payload['status'], $webhookToken)) {
            // Simplified check based on common patterns, adjust based on Flip docs
            Log::warning('Flip webhook: Invalid signature or token', [
                'payload' => $payload,
                'signature' => $signature,
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $disbursementId = $payload['id'];
        $status = $payload['status'];
        $remark = $payload['remark'] ?? null;

        $withdrawal = Withdrawal::where('disburse_id', $disbursementId)->first();

        if (!$withdrawal) {
            Log::warning('Flip webhook: Withdrawal not found', [
                'disbursement_id' => $disbursementId,
                'payload' => $payload,
            ]);
            return response()->json(['message' => 'Withdrawal not found'], 404);
        }

        // Update withdrawal status based on Flip's webhook status
        $newStatus = match ($status) {
            'DONE' => 'completed',
            'FAILED' => 'failed',
            default => $withdrawal->status, // Keep current status if unknown
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
