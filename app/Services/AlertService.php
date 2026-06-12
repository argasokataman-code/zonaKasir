<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlertService
{
    /**
     * Send P0 critical alert (Slack/Email).
     * Called when:
     * - Ledger invariant violation
     * - Reconciliation mismatch > Rp 10k
     * - Disbursement API failure (3+ in 5 min)
     * - Webhook signature verification fail
     * - Database deadlock on ledger
     */
    public function critical(string $title, string $message, array $context = []): void
    {
        Log::channel('finance')->critical($title, array_merge($context, [
            'alert_level' => 'P0',
            'timestamp' => now()->toIso8601String(),
        ]));

        // Send to Slack if webhook URL configured
        $webhook = config('services.alerts.slack_webhook');
        if ($webhook) {
            Http::post($webhook, [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => ['type' => 'plain_text', 'text' => '🚨 P0: ' . $title],
                    ],
                    [
                        'type' => 'section',
                        'text' => ['type' => 'mrkdwn', 'text' => $message],
                    ],
                    [
                        'type' => 'context',
                        'elements' => [
                            ['type' => 'mrkdwn', 'text' => '🕐 ' . now()->format('Y-m-d H:i:s')],
                        ],
                    ],
                ],
            ]);
        }
    }

    /**
     * Send P1 warning alert (Slack).
     * Called when:
     * - Reconciliation mismatch < Rp 10k
     * - Withdrawal failed (single)
     * - Webhook processing delayed > 5 min
     * - Idempotency key collision (> 1/min)
     * - Tenant balance near zero (< Rp 100k)
     */
    public function warning(string $title, string $message, array $context = []): void
    {
        Log::channel('finance')->warning($title, array_merge($context, [
            'alert_level' => 'P1',
            'timestamp' => now()->toIso8601String(),
        ]));

        $webhook = config('services.alerts.slack_webhook');
        if ($webhook) {
            Http::post($webhook, [
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => ['type' => 'plain_text', 'text' => '⚠️ P1: ' . $title],
                    ],
                    [
                        'type' => 'section',
                        'text' => ['type' => 'mrkdwn', 'text' => $message],
                    ],
                ],
            ]);
        }
    }
}
