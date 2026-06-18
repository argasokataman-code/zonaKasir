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

        // ── Flip sandbox sends fixed token; accept whatever arrives ──
        // ── Verify token if configured ──
        $webhookToken = config('flip.webhook_token');
        $incomingToken = $payload['token'] ?? $payload['secret'] ?? $request->query('token') ?? null;

        if ($webhookToken && $incomingToken !== $webhookToken) {
            Log::warning('Flip webhook: Invalid token', [
                'expected_token' => substr($webhookToken, 0, 12).'...',
                'incoming_token' => $incomingToken ? substr((string) $incomingToken, 0, 12).'...' : '(none)',
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ── Flip sends {data: "{\"...\"}", token: "..."} — data is JSON string ──
        $rawData = $payload['data'] ?? null;
        $disbursementData = is_string($rawData) ? json_decode($rawData, true) : ($rawData ?? $payload);
        if (! is_array($disbursementData)) {
            $disbursementData = $payload;
        }

        // ── Detect event type from fields ──
        $disbursementId = $disbursementData['id'] ?? null;
        $status = $disbursementData['status'] ?? null;

        // ── Skip non-disbursement webhooks ──
        $type = match (true) {
            ! empty($disbursementData['inquiry_key'])        => 'bank_inquiry',
            ! empty($disbursementData['bill_link_id'])        => 'bill_link',
            ! empty($disbursementData['agent_id'])            => 'agent_kyc',
            ! empty($disbursementData['kyc_status'])          => 'agent_kyc',
            default => null,
        };
        if ($type) {
            Log::info("Flip webhook: {$type} — skipped", [
                'id' => $disbursementId ?? '?',
                'status' => $status ?? '?',
            ]);
            return response()->json(['message' => "Ignored {$type}"], 200);
        }

        // ── Disbursement webhook must have numeric id + valid status ──
        if (! $disbursementId || ! is_numeric($disbursementId)) {
            Log::info('Flip webhook: non-disbursement — skipped', [
                'id' => $disbursementId ?? '(none)',
                'status' => $status ?? '(none)',
                'keys' => array_keys($disbursementData),
            ]);
            return response()->json(['message' => 'Ignored'], 200);
        }

        $disbursementId = (string) $disbursementId;
        $validDisbursementStatuses = ['DONE', 'FAILED', 'CANCELLED', 'SUCCESS'];
        if (! $status || ! in_array($status, $validDisbursementStatuses)) {
            Log::info('Flip webhook: non-disbursement status — skipped', [
                'id' => $disbursementId,
                'status' => $status ?? '(none)',
            ]);
            return response()->json(['message' => 'Ignored status'], 200);
        }

        $withdrawal = Withdrawal::select('id', 'status', 'amount', 'tenant_id', 'disburse_id')->where('disburse_id', $disbursementId)->first();

        if (! $withdrawal) {
            Log::warning('Flip webhook: Withdrawal not found — saving for retry', [
                'disbursement_id' => $disbursementId,
                'status' => $status,
            ]);
            $this->storeOrphanedWebhook($payload);
            return response()->json(['message' => 'Accepted for retry'], 200);
        }

        $newStatus = match ($status) {
            'SUCCESS', 'DONE' => 'completed',
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
