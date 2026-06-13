<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Console\Command;

class LicenseGenerate extends Command
{
    protected $signature = 'license:generate
                            {--plan=starter : Plan name (trial, starter, business)}
                            {--days=365 : License duration in days}
                            {--tenant= : Tenant ID to assign}
                            {--count=1 : Number of licenses to generate}';

    protected $description = 'Generate license keys for tenants';

    public function handle(LicenseService $licenseService): int
    {
        $plan = $this->option('plan');
        $days = (int) $this->option('days');
        $tenantId = $this->option('tenant');
        $count = (int) $this->option('count');

        $licenses = [];

        for ($i = 0; $i < $count; $i++) {
            if ($tenantId) {
                $license = $licenseService->create($tenantId, $plan, $days);
            } else {
                $license = License::create([
                    'key' => License::generateKey(),
                    'plan' => $plan,
                    'status' => 'active',
                    'expires_at' => now()->addDays($days),
                ]);
            }
            $licenses[] = $license;
        }

        $this->table(
            ['Key', 'Plan', 'Tenant', 'Expires'],
            collect($licenses)->map(fn ($l) => [
                $l->key,
                $l->plan,
                $l->tenant_id ?? '(unassigned)',
                $l->expires_at?->format('Y-m-d H:i'),
            ])->toArray()
        );

        $this->info("Generated {$count} license(s).");

        return self::SUCCESS;
    }
}
