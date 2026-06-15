<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Withdrawal;
use App\Notifications\TransferReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FlipWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // ── Verify HMAC signature (if Flip provides it) ──
        $signature = $request->header('X-Flip-Signature');
        $webhookSecret = config('flip.webhook_secret');

        if ($signature && $webhookSecret) {
            $expected = hash_hmac('sha256', $request->getContent(), $webhookSecret);
            if (! hash_equals($expected, $signature)) {
                Log::warning('Flip webhook: Invalid HMAC signature');
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        } else {
            // ── Fallback: verify token in body ──
            $webhookToken = config('flip.webhook_token');
            $incomingToken = $payload['token'] ?? null;

            if (! $webhookToken || $incomingToken !== $webhookToken) {
                Log::warning('Flip webhook: Invalid token');
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        // ── Validate required fields ──
        $disbursementId = $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $disbursementId || ! $status) {
            Log::warning('Flip webhook: Missing required fields', ['payload' => $payload]);
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        $withdrawal = Withdrawal::where('disburse_id', $disbursementId)->first();

        if (! $withdrawal) {
            Log::warning('Flip webhook: Withdrawal not found', [
                'disbursement_id' => $disbursementId,
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

            // ── Send notification to tenant on completion ──
            if ($newStatus === 'completed') {
                try {
                    Notification::send(
                        $withdrawal->requestedBy,
                        new TransferReceived($withdrawal)
                    );
                } catch (\Throwable $e) {
                    Log::warning('Flip webhook: Failed to send notification', [
                        'withdrawal_id' => $withdrawal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Webhook received'], 200);
    }
}
