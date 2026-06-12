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

    /**
     * Generate Snap transaction token for a selling.
     */
    public function createSnapToken(Selling $selling, string $midtransType): array
    {
        $about = About::first();
        $this->validateCredentials();

        $params = [
            'transaction_details' => [
                'order_id' => $this->generateOrderId(),
                'gross_amount' => (int) $selling->total_price,
            ],
            'customer_details' => [
                'first_name' => $selling->member?->name ?? 'Guest',
            ],
            'credit_card' => $midtransType === 'credit_card' ? ['secure' => true] : null,
        ];

        // Remove null credit_card if not applicable
        if ($params['credit_card'] === null) {
            unset($params['credit_card']);
        }

        $serverKey = config('midtrans.server_key');

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

        // Create payment record
        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $params['transaction_details']['order_id'],
            'gross_amount' => $selling->total_price,
            'status' => 'pending',
        ]);

        return [
            'token' => $data['token'],
            'redirect_url' => $data['redirect_url'],
            'midtrans_payment_id' => $payment->id,
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

    private function getApiUrl(?string $merchantId): string
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
