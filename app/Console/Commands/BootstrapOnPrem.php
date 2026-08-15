<?php

namespace App\Console\Commands;

use App\Constants\Role;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Services\TenantContext;
use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class BootstrapOnPrem extends Command
{
    protected $signature = 'onprem:bootstrap
                            {--name= : Tenant id / slug, default uniqid }
                            {--email= : Owner email, required }
                            {--password= : Owner password, default random }';

    protected $description = 'Bootstrap a single on-prem tenant with permanent active subscription';

    public function handle(): int
    {
        $name = $this->option('name') ?: uniqid();
        $email = $this->option('email');
        $password = $this->option('password') ?: \Illuminate\Support\Str::random(16);

        if (! $email) {
            $this->error('--email is required');

            return Command::FAILURE;
        }

        if (Tenant::whereKey($name)->exists()) {
            $this->error("Tenant '{$name}' already exists");

            return Command::FAILURE;
        }

        TenantContext::set($name);

        Tenant::unguarded(fn () => Tenant::create([
            'id' => $name,
            'tenancy_email' => $email,
        ]));

        User::create([
            'tenant_id' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
            'is_owner' => true,
        ]);

        About::create([
            'tenant_id' => $name,
            'shop_name' => $this->option('name') ?: $name,
        ]);

        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
        Artisan::call('db:seed', ['--class' => 'PaymentMethodSeeder']);
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        $role = \App\Models\Tenants\Role::firstOrCreate(['name' => Role::admin, 'guard_name' => 'web']);
        if (! $role->tenant_id) {
            $role->update(['tenant_id' => $name]);
        }

        $user = User::where('tenant_id', $name)->where('email', $email)->first();
        if (! $user->hasRole(Role::admin)) {
            $user->assignRole(Role::admin);
        }

        $plan = Plan::select('id', 'name')->where('is_active', true)->orderByDesc('price_monthly')->first();
        if (! $plan) {
            $this->error('No active plan found. Run PlanSeeder first: php artisan db:seed --class=PlanSeeder');

            return Command::FAILURE;
        }

        Subscription::create([
            'tenant_id' => $name,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => null,
        ]);

        $this->info("On-prem tenant '{$name}' bootstrapped.");
        $this->line("  Email:    {$email}");
        $this->line("  Password: {$password}");
        $this->line("  Plan:     {$plan->name} (permanent active)");

        return Command::SUCCESS;
    }
}
