<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\CartItem;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use App\Models\Tenants\LedgerEntry;
use App\Models\Tenants\IdempotencyLog;
use App\Models\Tenants\PaymentMethod;
use App\Services\Tenants\SellingService;
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
     * Create payment intent without a Selling model (Midtrans flow).
     * Store cart_data and generate Snap token. Selling created later on payment confirmation.
     */
    public function createPaymentIntent(array $cartData, string $midtransType): array
    {
        $this->validateCredentials();

        $orderId = $this->generateOrderId();
        $grossAmount = (int) ($cartData['total_price'] ?? 0);
        $serverKey = config('midtrans.server_key');
        $memberName = $cartData['member_label'] ?? 'Guest';

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $memberName,
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
                'order_id' => $orderId,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat pembayaran Midtrans: ' . $this->getErrorMessage($response));
        }

        $data = $response->json();

        $payment = MidtransPayment::create([
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'payment_type' => $midtransType,
            'status' => 'pending',
            'cart_data' => $cartData,
        ]);

        return [
            'order_id' => $orderId,
            'token' => $data['token'],
            'redirect_url' => $data['redirect_url'],
            'payment_type' => $midtransType,
            'midtrans_payment_id' => $payment->id,
        ];
    }

    /**
     * Generate Snap transaction token from an existing Selling model.
     */
    public function createSnapToken(Selling $selling, string $midtransType): array
    {
        $this->validateCredentials();

        $orderId = $this->generateOrderId();
        $grossAmount = (int) $selling->total_price;
        $serverKey = config('midtrans.server_key');

        return $this->createSnapTokenForPayment($selling, $orderId, $grossAmount, $midtransType, $serverKey);
    }

    private function chargeCoreApiQris(
        string $orderId,
        int $grossAmount,
        Selling $selling,
        string $serverKey,
    ): array {
        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post(self::CORE_API_URLS[config('midtrans.environment', 'sandbox')] . '/charge', $params);

        if ($response->failed()) {
            Log::error('Midtrans Core API QRIS charge failed', [
                'selling_id' => $selling->id,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            throw new \RuntimeException('Gagal membuat QRIS: ' . $this->getErrorMessage($response));
        }

        $data = $response->json();

        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $orderId,
            'gross_amount' => $selling->total_price,
            'payment_type' => 'qris',
            'status' => 'pending',
        ]);

        // Core API QRIS returns qr_code_url (image) and redirect_url
        return [
            'token' => null,
            'redirect_url' => $data['redirect_url'] ?? '',
            'qr_code_url' => $data['qr_code_url'] ?? null,
            'qr_string' => null,
            'payment_type' => 'qris',
            'midtrans_payment_id' => $payment->id,
            'api' => 'core_api',
        ];
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
            ->post(self::CORE_API_URLS[config('midtrans.environment', 'sandbox')] . '/charge', $params);

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

        // If no selling yet (frontend Snap callback didn't fire or failed), create it now
        if (! $payment->selling_id && $payment->cart_data) {
            $this->createSellingFromCart($payment);
        }

        if (! $payment->selling) {
            Log::warning('finalizeSettlement: no selling found', ['order_id' => $payment->order_id]);
            return;
        }

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

    /**
     * Create a Selling from stored cart_data when frontend callback missed or failed.
     */
    private function createSellingFromCart(MidtransPayment $payment): void
    {
        $cartData = $payment->cart_data;
        if (! $cartData) {
            return;
        }

        $sellingService = app(SellingService::class);
        $data = array_merge($cartData, $sellingService->mapProductRequest($cartData));
        $selling = $sellingService->create($data);

        $payment->update(['selling_id' => $selling->id]);

        CartItem::query()->cashier()->delete();

        Log::info('createSellingFromCart: selling created from webhook', [
            'order_id' => $payment->order_id,
            'selling_id' => $selling->id,
        ]);
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
