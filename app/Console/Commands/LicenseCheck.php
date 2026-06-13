<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseCheck extends Command
{
    protected $signature = 'license:check {tenant : Tenant ID}';

    protected $description = 'Check license status for a tenant';

    public function handle(LicenseService $licenseService): int
    {
        $tenantId = $this->argument('tenant');
        $license = $licenseService->getActiveLicense($tenantId);

        if (! $license) {
            $this->warn("No active license for tenant: {$tenantId}");

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['Key', $license->key],
                ['Plan', $license->plan],
                ['Status', $license->status],
                ['Activated', $license->activated_at?->format('Y-m-d') ?? '-'],
                ['Expires', $license->expires_at?->format('Y-m-d') ?? 'Never'],
                ['Days Left', $license->daysLeft()],
            ]
        );

        return self::SUCCESS;
    }
}
