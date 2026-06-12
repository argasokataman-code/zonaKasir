<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains Midtrans payment gateway configuration including
    | MDR rates per payment method, webhook IP whitelist, and default values.
    |
    */

    /*
     * Midtrans Webhook IP Whitelist
     * Comma-separated list of allowed IPs from Midtrans.
     * Update via .env: MIDTRANS_WEBHOOK_IPS="52.76.155.198,52.76.156.139,..."
     */
    'webhook_ip_whitelist' => explode(',', env('MIDTRANS_WEBHOOK_IPS', '')),

    /*
     * Midtrans Merchant ID (Production/Sandbox)
     */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

    /*
     * Midtrans Server Key (Production/Sandbox)
     */
    'server_key' => env('MIDTRANS_SERVER_KEY'),

    /*
     * Midtrans Client Key (Production/Sandbox)
     */
    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    /*
     * Environment: 'sandbox' or 'production'
     */
    'environment' => env('MIDTRANS_ENVIRONMENT', 'sandbox'),

    /*
     * SnapBi (BI-SNAP) Configuration
     * Required for QRIS, GoPay, ShopeePay via SnapBi API
     * Private key is loaded from storage/app/private-key.pem (not from .env)
     */
    'snapbi' => [
        'client_id' => env('MIDTRANS_SNAPBI_CLIENT_ID'),
        'client_secret' => env('MIDTRANS_SNAPBI_CLIENT_SECRET'),
        'private_key' => function () {
            $path = storage_path('app/private-key.pem');
            return file_exists($path) ? file_get_contents($path) : null;
        },
        'partner_id' => env('MIDTRANS_SNAPBI_PARTNER_ID'),
        'channel_id' => env('MIDTRANS_SNAPBI_CHANNEL_ID'),
        'merchant_id' => env('MIDTRANS_SNAPBI_MERCHANT_ID'),
    ],

    /*
     * MDR Fee rates per payment method
     * type: 'percentage' | 'flat'
     */
    'fees' => [
        'credit_card'   => ['type' => 'percentage', 'percentage' => 2.95],
        'debit_card'    => ['type' => 'percentage', 'percentage' => 1.95],
        'gopay'         => ['type' => 'percentage', 'percentage' => 1.50],
        'shopeepay'     => ['type' => 'percentage', 'percentage' => 1.50],
        'bank_transfer' => ['type' => 'flat', 'amount' => 2500], // BCA/Mandiri/BNI/BRI
        'qris'          => ['type' => 'percentage', 'percentage' => 0.70],
        'indomaret'     => ['type' => 'flat', 'amount' => 2500],
        'alfamart'      => ['type' => 'flat', 'amount' => 2500],
        'kredivo'       => ['type' => 'percentage', 'percentage' => 3.00],
        'akulaku'       => ['type' => 'percentage', 'percentage' => 3.00],
    ],

    /*
     * Default platform fee percentage (per tenant, overridable in abouts table)
     */
    'platform_fee_percent_default' => 1.00,

    /*
     * Minimum withdrawal amount
     */
    'min_withdrawal_amount' => 50000,

    /*
     * Maximum withdrawal percentage of balance (95% buffer for refunds)
     */
    'max_withdrawal_percentage' => 95,

    /*
     * Auto-approval thresholds for withdrawals
     */
    'withdrawal_approval' => [
        'auto_approve_max' => 5000000,      // < 5jt auto-approve (trusted)
        'single_admin_max' => 25000000,     // 5-25jt single admin
        // > 25jt requires 2 admin approvals
    ],
];
