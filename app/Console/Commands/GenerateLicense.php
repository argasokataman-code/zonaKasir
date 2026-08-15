<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class GenerateLicense extends Command
{
    protected $signature = 'onprem:license
                            {--customer= : Customer name (human readable) }
                            {--domain= : Domain this license is bound to, required }
                            {--days=365 : Validity in days }';

    protected $description = 'Generate an on-prem license key (run on vendor side, requires private key)';

    public function handle(LicenseService $license): int
    {
        $domain = $this->option('domain');
        $customer = $this->option('customer') ?: 'CUSTOMER';
        $days = (int) $this->option('days');

        $privateKey = env('ONPREM_PRIVATE_KEY') ?: config('onprem.license.private_key');

        if (! $domain) {
            $this->error('--domain is required');

            return Command::FAILURE;
        }

        if (! $privateKey) {
            $this->error('ONPREM_PRIVATE_KEY not set (vendor side secret, never ship to client)');

            return Command::FAILURE;
        }

        $payload = [
            'customer' => $customer,
            'domain' => $domain,
            'issued_at' => now()->toDateString(),
            'expires_at' => now()->addDays($days)->toDateString(),
        ];

        $key = $license->sign($payload, $privateKey);
        $slug = strtoupper(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $customer)));

        $this->info("License for {$customer} ({$domain}), {$days} days:");
        $this->line('');
        $this->line("    ZONA-{$slug}-{$key}");
        $this->line('');

        return Command::SUCCESS;
    }
}
