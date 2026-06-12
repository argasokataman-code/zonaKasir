<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;

class SeedDigitalPaymentMethods extends Command
{
    protected $signature = 'payments:seed-methods {--tenant= : Specific tenant ID to seed}';

    protected $description = 'Seed digital payment methods (GoPay, QRIS, etc.) for all existing tenants';

    public function handle(): int
    {
        $tenants = $this->option('tenant')
            ? Tenant::where('id', $this->option('tenant'))->get()
            : Tenant::all();

        $count = 0;
        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                $this->call('db:seed', ['--class' => 'DigitalPaymentMethodSeeder', '--force' => true]);
                $count++;
                $this->info("Seeded: {$tenant->id}");
            } catch (\Throwable $e) {
                $this->error("Failed for {$tenant->id}: {$e->getMessage()}");
            }
        }

        $this->info("Seeded {$count} tenant(s) with digital payment methods.");
        return self::SUCCESS;
    }
}
