<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class VerifyLicense extends Command
{
    protected $signature = 'onprem:verify';

    protected $description = 'Verify on-prem license locally (warning only, kasir never blocked)';

    public function handle(LicenseService $license): int
    {
        $key = config('onprem.license.key');
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url');

        if (! $key) {
            $this->warn('No ONPREM_LICENSE_KEY configured. License not checked (warning only).');

            return Command::SUCCESS;
        }

        $result = $license->verify($key, $domain);

        if ($result['valid']) {
            $this->info("License valid for domain '{$domain}' until {$result['payload']['expires_at']}.");

            return Command::SUCCESS;
        }

        $this->warn("License INVALID for domain '{$domain}': {$result['reason']}");
        $this->warn('Contact your vendor. Kasir keeps working.');

        return Command::SUCCESS;
    }
}
