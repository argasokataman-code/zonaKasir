<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SnapBi\SnapBi;

class SnapBiQrisService
{
    /**
     * Create QRIS payment via SnapBi API, returns qr_string for QR code generation.
     */
    public function createPayment(Selling $selling): array
    {
        $config = config('midtrans.snapbi');
        $this->validateConfig($config);

        // Configure SnapBi
        SnapBi\Config::$isProduction = config('midtrans.environment') === 'production';
        SnapBi\Config::$snapBiClientId = $config['client_id'];
        SnapBi\Config::$snapBiClientSecret = $config['client_secret'];
        SnapBi\Config::$snapBiPrivateKey = $config['private_key'];
        SnapBi\Config::$snapBiPartnerId = $config['partner_id'];
        SnapBi\Config::$snapBiMerchantId = $config['merchant_id'];
        SnapBi\Config::$snapBiChannelId = $config['channel_id'] ?? '';
        SnapBi\Config::$enableLogging = config('app.debug', false);

        $orderId = 'Q-' . (int) (microtime(true) * 1000) . '-' . random_int(1000, 9999);

        $qrisBody = [
            'partnerReferenceNo' => $orderId,
            'amount' => [
                'value' => (string) $selling->total_price,
                'currency' => 'IDR',
            ],
            'merchantId' => $config['merchant_id'],
            'validityPeriod' => now()->addMinutes(30)->toIso8601String(),
            'additionalInfo' => [
                'items' => $this->buildItems($selling),
            ],
        ];

        $response = SnapBi::qris()
            ->withBody($qrisBody)
            ->createPayment($orderId);

        // Create payment record
        $payment = MidtransPayment::create([
            'selling_id' => $selling->id,
            'order_id' => $orderId,
            'gross_amount' => $selling->total_price,
            'payment_type' => 'qris',
            'status' => 'pending',
        ]);

        return [
            'qr_string' => $response->qr_string ?? null,
            'qr_code_url' => $response->qr_code_url ?? null,
            'order_id' => $orderId,
            'payment_id' => $payment->id,
            'amount' => $selling->total_price,
        ];
    }

    private function buildItems(Selling $selling): array
    {
        return $selling->sellingDetails->map(fn ($detail) => [
            'id' => (string) $detail->product_id,
            'price' => ['value' => (string) $detail->price, 'currency' => 'IDR'],
            'quantity' => $detail->qty,
            'name' => substr($detail->product?->name ?? 'Item', 0, 50),
            'brand' => 'ZonaKasir',
            'category' => 'POS',
            'merchantName' => $selling->about?->shop_name ?? 'ZonaKasir',
        ])->toArray();
    }

    private function validateConfig(array $config): void
    {
        $required = ['client_id', 'client_secret', 'private_key', 'partner_id', 'merchant_id'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \RuntimeException("SnapBi {$key} belum dikonfigurasi di .env");
            }
        }
    }
}
