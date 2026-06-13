<?php

return [
    'secret_key' => env('FLIP_SECRET_KEY'),
    'webhook_token' => env('FLIP_WEBHOOK_TOKEN'),
    'base_url' => env('FLIP_BASE_URL', 'https://big.flip.id/api/v2'),

    /*
     * Withdrawal approval thresholds for Flip payouts
     */
    'withdrawal_approval' => [
        'auto_approve_max' => env('FLIP_WITHDRAWAL_AUTO_APPROVE_MAX', 5000000),      // < 5jt auto-approve (trusted)
        'single_admin_max' => env('FLIP_WITHDRAWAL_SINGLE_ADMIN_MAX', 25000000),     // 5-25jt single admin
        // > 25jt requires 2 admin approvals
    ],
];
