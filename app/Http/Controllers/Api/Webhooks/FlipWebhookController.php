<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\LedgerEntry;
use App\Notifications\WithdrawalApproved;
use App\Services\Tenants\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FlipWebhookController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Flip webhook received', [
            'payload' => $payload,
        ]);

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
            $incomingToken = $payload['token'] ?? $payload['secret'] ?? $request->query('token') ?? null;

            if (! $webhookToken || $incomingToken !== $webhookToken) {
                Log::warning('Flip webhook: Invalid token', [
                    'expected_token' => $webhookToken ? substr($webhookToken, 0, 8).'...' : '(none)',
                    'incoming_token' => $incomingToken ? substr((string) $incomingToken, 0, 8).'...' : '(none)',
                    'payload_keys' => array_keys($payload),
                ]);
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        // ── Flip sends {data: {id, status, ...}, token: "..."} ──
        $disbursementData = $payload['data'] ?? $payload;
        $disbursementId = $disbursementData['id'] ?? null;
        $status = $disbursementData['status'] ?? null;

        if (! $disbursementId || ! $status) {
            Log::warning('Flip webhook: Missing required fields', [
                'has_id' => ! is_null($disbursementId),
                'has_status' => ! is_null($status),
            ]);
            return response()->json(['message' => 'Missing required fields'], 400);
        }

        $withdrawal = Withdrawal::where('disburse_id', $disbursementId)->first();

        if (! $withdrawal) {
            Log::warning('Flip webhook: Withdrawal not found — saving for retry', [
                'disbursement_id' => $disbursementId,
                'status' => $status,
            ]);
            // Simpan orphaned webhook untuk recovery via cron/job
            $this->storeOrphanedWebhook($payload);
            return response()->json(['message' => 'Accepted for retry'], 200);
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

            // ── Ledger handling ──
            if ($newStatus === 'failed') {
                // Rollback ledger: restore tenant balance
                try {
                    $this->ledger->entry(
                        ledgerableType: Withdrawal::class,
                        ledgerableId: $withdrawal->id,
                        entryType: 'credit',
                        amount: $withdrawal->amount,
                        description: "Withdrawal rollback #{$withdrawal->id}: webhook {$status}",
                        referenceType: 'withdrawal_rollback',
                        referenceId: $withdrawal->id,
                    );
                } catch (\Throwable $e) {
                    Log::error('Flip webhook: Failed to rollback ledger', [
                        'withdrawal_id' => $withdrawal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($newStatus === 'completed') {
                // Update ledger reference: withdrawal_request → withdrawal_complete
                LedgerEntry::where('reference_type', 'withdrawal_request')
                    ->where('reference_id', $withdrawal->id)
                    ->update(['reference_type' => 'withdrawal_complete']);
            }

            // ── Send notification to tenant on completion ──
            if ($newStatus === 'completed') {
                try {
                    Notification::send(
                        $withdrawal->requestedBy,
                        new WithdrawalApproved($withdrawal)
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

    /**
     * Store orphaned webhook payload for later reconciliation.
     * Diproses via cron/command jika withdrawal tetap tidak ditemukan.
     */
    private function storeOrphanedWebhook(array $payload): void
    {
        $disbursementId = $payload['id'] ?? 'unknown';
        $path = storage_path("app/orphaned-webhooks/{$disbursementId}.json");
        @mkdir(dirname($path), 0755, true);
        file_put_contents(
            $path,
            json_encode([
                'received_at' => now()->toISOString(),
                'payload' => $payload,
            ], JSON_PRETTY_PRINT),
        );
    }
}
