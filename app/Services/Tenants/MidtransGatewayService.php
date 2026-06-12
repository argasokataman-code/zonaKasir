<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use App\Models\Tenants\LedgerEntry;
use App\Models\Tenants\IdempotencyLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MidtransGatewayService
{
    private const SNAP_API_URLS = [
        'sandbox' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        'production' => 'https://app.midtrans.com/snap/v1/transactions',
    ];

    public function __construct(
        private readonly MidtransFeeCalculator $feeCalculator,
        private readonly LedgerService $ledger,
    ) {}

    private const CORE_API_URLS = [
        'sandbox' => 'https://api.sandbox.midtrans.com/v2',
        'production' => 'https://api.midtrans.com/v2',
    ];

    /**
     * Generate Snap transaction token for credit_card, or Core API charge for QRIS/GoPay.
     */
    public function createSnapToken(Selling $selling, string $midtransType): array
    {
        $about = About::first();
        $this->validateCredentials();

        $orderId = $this->generateOrderId();
        $grossAmount = (int) $selling->total_price;

        $serverKey = config('midtrans.server_key');

        // QRIS and GoPay use Core API charge (not Snap) to get QR code directly
        if (in_array($midtransType, ['qris', 'gopay', 'shopeepay', 'bank_transfer'])) {
            return $this->chargeCoreApi($orderId, $grossAmount, $midtransType, $selling, $serverKey);
        }

        // Credit card and others use Snap
        return $this->createSnapTokenForPayment($selling, $orderId, $grossAmount, $midtransType, $serverKey);
    }

    private function chargeCoreApi(
        string $orderId,
        int $grossAmount,
        string $midtransType,
        Selling $selling,
        string $serverKey,
    ): array {
        $params = [
            'payment_type' => $midtransType,
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
        ];

        // For QRIS, add optional acquirer
        if ($midtransType === 'qris') {
            $params['acquirer'] = 'gopay';
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post(self::CORE_API_URLS[config('midtrans.environment', 'sandbox')] . '/' . $orderId . '/charge', $params);

        if ($response->failed()) {
            Log::error('Midtrans Core API charge failed', [
                'selling_id' => $selling->id,
                'payment_type' => $midtransType,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat pembayaran Midtrans: ' . $this->getErrorMessage($response));
        }

        $data = $response->json();

        // Create payment record
        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $orderId,
            'gross_amount' => $selling->total_price,
            'payment_type' => $midtransType,
            'status' => 'pending',
        ]);

        // Core API response contains qr_code_url for QRIS
        return [
            'token' => null,
            'redirect_url' => $data['qr_code_url'] ?? $data['redirect_url'] ?? '',
            'qr_string' => $data['qr_string'] ?? null,
            'payment_type' => $midtransType,
            'midtrans_payment_id' => $payment->id,
            'api' => 'core_api',
        ];
    }

    private function createSnapTokenForPayment(
        Selling $selling,
        string $orderId,
        int $grossAmount,
        string $midtransType,
        string $serverKey,
    ): array {
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $selling->member?->name ?? 'Guest',
            ],
        ];

        if ($midtransType === 'credit_card') {
            $params['credit_card'] = ['secure' => true];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($this->getApiUrl(), $params);

        if ($response->failed()) {
            Log::error('Midtrans Snap token failed', [
                'selling_id' => $selling->id,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat pembayaran Midtrans: ' . $this->getErrorMessage($response));
        }

        $data = $response->json();

        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $orderId,
            'gross_amount' => $selling->total_price,
            'payment_type' => $midtransType,
            'status' => 'pending',
        ]);

        return [
            'token' => $data['token'],
            'redirect_url' => $data['redirect_url'],
            'qr_string' => null,
            'payment_type' => $midtransType,
            'midtrans_payment_id' => $payment->id,
            'api' => 'snap',
        ];
    }

    /**
     * Handle incoming webhook from Midtrans.
     */
    public function handleWebhook(array $payload): void
    {
        // 1. Verify IP whitelist
        $whitelist = config('midtrans.webhook_ip_whitelist', []);
        if (!empty($whitelist) && !in_array(request()->ip(), $whitelist)) {
            Log::warning('Midtrans webhook IP mismatch', ['ip' => request()->ip()]);
            abort(403, 'Unauthorized IP');
        }

        // 2. Find payment record
        $payment = MidtransPayment::where('order_id', $payload['order_id'])->first();
        if (!$payment) {
            Log::error('Midtrans webhook: payment not found', ['order_id' => $payload['order_id']]);
            return;
        }

        // 3. Verify signature
        $about = About::first();
        $serverKey = $about->midtrans_server_key ?? config('midtrans.server_key');
        if (!$this->verifySignature($payload, $serverKey)) {
            Log::critical('Midtrans webhook signature verification failed', ['order_id' => $payload['order_id']]);
            abort(401, 'Invalid signature');
        }

        // 4. Idempotency check via existing idempotency_logs table
        $idemKey = $payload['transaction_id'] ?? $payload['order_id'];
        $idemLog = IdempotencyLog::firstOrCreate(
            ['idempotency_key' => $idemKey],
            [
                'status' => 'processing',
                'endpoint' => '/api/webhooks/midtrans',
                'method' => 'POST',
            ]
        );

        if ($idemLog->status === 'completed') {
            return; // Already processed
        }

        // 5. Process payment status update
        $this->processStatusUpdate($payment, $payload, $idemLog);
    }

    private function processStatusUpdate(MidtransPayment $payment, array $payload, IdempotencyLog $idemLog): void
    {
        DB::transaction(function () use ($payment, $payload, $idemLog) {
            $oldStatus = $payment->status;
            $newStatus = $payload['transaction_status'];

            $payment->update([
                'status' => $newStatus,
                'payment_type' => $payload['payment_type'] ?? $payment->payment_type,
                'payment_channel' => $payload['payment_type'] ?? $payload['bank'] ?? null,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                'paid_at' => in_array($newStatus, ['settlement', 'capture']) ? now() : $payment->paid_at,
                'notification_payload' => $payload,
            ]);

            // On settlement: update selling fee + ledger
            if (in_array($newStatus, ['settlement', 'capture']) && $oldStatus !== 'settlement') {
                $this->finalizeSettlement($payment, $payload);
            }

            // Mark idempotency as completed
            $idemLog->update([
                'status' => 'completed',
                'response' => json_encode($payload),
            ]);
        });
    }

    private function finalizeSettlement(MidtransPayment $payment, array $payload): void
    {
        $about = About::first();
        $fees = $this->feeCalculator->calculate(
            paymentType: $payload['payment_type'],
            grossAmount: $payment->gross_amount,
            platformFeePercent: $about->platform_fee_percent ?? 1.0,
        );

        // Save fee breakdown to payment
        $payment->forceFill([
            'fee_midtrans' => $fees['fee_midtrans'],
            'fee_platform' => $fees['fee_platform'],
            'net_amount' => $fees['net_amount'],
        ])->save();

        // Update selling.fee (existing column)
        $payment->selling->update([
            'is_paid' => true,
            'fee' => $payment->selling->fee + $fees['fee_midtrans'] + $fees['fee_platform'],
        ]);

        // Ledger: credit from sale
        $this->ledger->entry(
            ledgerableType: Selling::class,
            ledgerableId: $payment->selling->id,
            entryType: 'credit',
            amount: $payment->gross_amount,
            description: "Payment {$payment->order_id} via {$payment->payment_type}",
            referenceType: 'selling',
            referenceId: $payment->selling->id,
        );

        // Ledger: debit midtrans fee
        if ($fees['fee_midtrans'] > 0) {
            $this->ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $payment->selling->id,
                entryType: 'debit',
                amount: $fees['fee_midtrans'],
                description: "MDR {$payment->payment_type} ({$payment->order_id})",
                referenceType: 'fee_midtrans',
                referenceId: $payment->id,
                feeRateType: $fees['fee_midtrans_rate_type'],
                feeRateValue: $fees['fee_midtrans_rate_value'],
            );
        }

        // Ledger: debit platform fee
        if ($fees['fee_platform'] > 0) {
            $this->ledger->entry(
                ledgerableType: Selling::class,
                ledgerableId: $payment->selling->id,
                entryType: 'debit',
                amount: $fees['fee_platform'],
                description: "Platform fee ({$payment->order_id})",
                referenceType: 'fee_platform',
                referenceId: $payment->selling->id,
                feeRateType: 'percentage',
                feeRateValue: $about->platform_fee_percent ?? 1.0,
            );
        }
    }

    /**
     * Verify Midtrans HMAC SHA512 signature.
     */
    private function verifySignature(array $payload, string $serverKey): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $provided = $payload['signature_key'] ?? '';

        return hash_equals($signature, $provided);
    }

    private function generateOrderId(): string
    {
        $microtime = (int) (microtime(true) * 1000);
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        return "T{$microtime}-{$random}";
    }

    private function getApiUrl(): string
    {
        $environment = config('midtrans.environment', 'sandbox');
        return self::SNAP_API_URLS[$environment] ?? self::SNAP_API_URLS['sandbox'];
    }

    private function getErrorMessage($response): string
    {
        return $response->json('error_messages.0') ?? 'Unknown error';
    }

    private function validateCredentials(): void
    {
        $serverKey = config('midtrans.server_key');
        $merchantId = config('midtrans.merchant_id');

        if (empty($serverKey) || empty($merchantId)) {
            throw new \RuntimeException('Midtrans server key atau merchant ID belum dikonfigurasi di .env');
        }
    }
}
