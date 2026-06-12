<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\MidtransPayment;
use App\Models\Tenants\Selling;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SnapBi\SnapBi;
use SnapBi\SnapBiConfig;

class SnapBiQrisService
{
    /**
     * Create QRIS payment via SnapBi API, returns qr_string for QR code generation.
     */
    public function createPayment(Selling $selling): array
    {
        $config = config('midtrans.snapbi');
        $this->validateConfig($config);

        // Resolve Closure values
        $privateKey = is_callable($config['private_key']) ? $config['private_key']() : $config['private_key'];

        // Configure SnapBi
        SnapBiConfig::$isProduction = config('midtrans.environment') === 'production';
        SnapBiConfig::$snapBiClientId = $config['client_id'];
        SnapBiConfig::$snapBiClientSecret = $config['client_secret'];
        SnapBiConfig::$snapBiPrivateKey = $privateKey;
        SnapBiConfig::$snapBiPartnerId = $config['partner_id'];
        SnapBiConfig::$snapBiMerchantId = $config['merchant_id'];
        SnapBiConfig::$snapBiChannelId = $config['channel_id'] ?? '';
        SnapBiConfig::$enableLogging = config('app.debug', false);

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
        $required = ['client_id', 'client_secret', 'partner_id', 'merchant_id'];
        foreach ($required as $key) {
            $value = $config[$key];
            if (is_callable($value)) {
                $value = $value();
            }
            if (empty($value)) {
                throw new \RuntimeException("SnapBi {$key} belum dikonfigurasi di .env");
            }
        }
        // Private key must be loaded from file
        $privateKey = $config['private_key'];
        if (is_callable($privateKey)) {
            $privateKey = $privateKey();
        }
        if (empty($privateKey)) {
            throw new \RuntimeException("SnapBi private_key belum ada di storage/app/private-key.pem");
        }
    }
}
