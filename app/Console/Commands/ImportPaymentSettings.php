<?php

namespace App\Console\Commands;

use App\Models\PaymentSetting;
use Illuminate\Console\Command;

class ImportPaymentSettings extends Command
{
    protected $signature = 'app:import-payment-settings';

    protected $description = 'Import payment settings from .env/config into payment_settings table';

    public function handle(): void
    {
        // Midtrans
        PaymentSetting::saveGroup('midtrans', [
            'environment' => config('midtrans.environment', 'sandbox'),
            'merchant_id' => config('midtrans.merchant_id'),
            'client_key' => config('midtrans.client_key'),
            'server_key' => config('midtrans.server_key'),
            'webhook_ips' => config('midtrans.webhook_ip_whitelist') ? implode(',', config('midtrans.webhook_ip_whitelist')) : null,
        ]);

        // SnapBi
        PaymentSetting::saveGroup('snapbi', [
            'client_id' => config('midtrans.snapbi.client_id'),
            'client_secret' => config('midtrans.snapbi.client_secret'),
            'partner_id' => config('midtrans.snapbi.partner_id'),
            'channel_id' => config('midtrans.snapbi.channel_id'),
            'merchant_id' => config('midtrans.snapbi.merchant_id'),
            'private_key' => config('midtrans.snapbi.private_key'),
            'public_key' => config('midtrans.snapbi.public_key'),
        ]);

        // Flip
        PaymentSetting::saveGroup('flip', [
            'secret_key' => config('flip.secret_key'),
            'webhook_token' => config('flip.webhook_token'),
            'webhook_secret' => config('flip.webhook_secret'),
            'base_url' => config('flip.base_url', 'https://big.flip.id/api/v2'),
        ]);

        $this->info('Payment settings imported successfully.');
    }
}
