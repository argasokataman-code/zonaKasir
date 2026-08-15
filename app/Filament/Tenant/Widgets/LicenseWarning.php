<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\LicenseService;
use Filament\Widgets\Widget;

class LicenseWarning extends Widget
{
    protected static string $view = 'filament.tenant.widgets.license-warning';

    public ?string $reason = null;

    public ?string $expiresAt = null;

    public bool $showWarning = false;

    public function mount(): void
    {
        if (! config('onprem.mode')) {
            return;
        }

        $key = config('onprem.license.key');

        if (! $key) {
            $this->showWarning = true;
            $this->reason = 'No license key configured.';

            return;
        }

        $result = app(LicenseService::class)->verify($key, parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url'));

        if (! $result['valid']) {
            $this->showWarning = true;
            $this->reason = $result['reason'];
            $this->expiresAt = $result['payload']['expires_at'] ?? null;
        }
    }
}
