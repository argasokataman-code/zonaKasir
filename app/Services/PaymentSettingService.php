<?php

namespace App\Services;

use App\Models\PaymentSetting;
use Illuminate\Support\Str;

class PaymentSettingService
{
    public function boot(): void
    {
        try {
            $this->loadMidtrans();
            $this->loadFlip();
            $this->loadSnapBi();
            $this->loadWithdrawal();
        } catch (\Throwable $e) {
            // DB not ready yet (migrating, fresh install, etc.)
        }
    }

    protected function loadMidtrans(): void
    {
        $env = PaymentSetting::get('midtrans', 'environment', 'sandbox');
        config()->set('midtrans.environment', $env);

        // Load env-specific credentials as active config
        $prefix = $env === 'production' ? 'production_' : 'sandbox_';
        $map = [
            "{$prefix}merchant_id" => 'merchant_id',
            "{$prefix}client_key" => 'client_key',
            "{$prefix}server_key" => 'server_key',
        ];
        foreach ($map as $dbKey => $configKey) {
            $val = PaymentSetting::get('midtrans', $dbKey);
            if ($val !== null) {
                config()->set("midtrans.{$configKey}", $val);
            }
        }

        // Load flat settings (webhook_ips, etc.)
        foreach (['webhook_ips'] as $key) {
            $val = PaymentSetting::get('midtrans', $key);
            if ($val !== null) {
                config()->set("midtrans.{$key}", $val);
            }
        }
    }

    protected function loadFlip(): void
    {
        $env = PaymentSetting::get('flip', 'environment', 'production');
        config()->set('flip.environment', $env);

        $prefix = $env === 'production' ? 'production_' : 'sandbox_';
        $map = [
            "{$prefix}secret_key" => 'secret_key',
            "{$prefix}webhook_token" => 'webhook_token',
            "{$prefix}base_url" => 'base_url',
        ];
        foreach ($map as $dbKey => $configKey) {
            $val = PaymentSetting::get('flip', $dbKey);
            if ($val !== null) {
                config()->set("flip.{$configKey}", $val);
            }
        }
    }

    protected function loadSnapBi(): void
    {
        $settings = PaymentSetting::getGroup('snapbi');
        if (empty($settings)) return;

        foreach ($settings as $key => $value) {
            if ($value !== null) {
                config()->set("midtrans.snapbi.{$key}", $value);
            }
        }
    }

    protected function loadWithdrawal(): void
    {
        // Hardcoded, no DB storage needed
    }
}
