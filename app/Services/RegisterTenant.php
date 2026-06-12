<?php

namespace App\Services;

use App\Constants\Role;
use App\Models\Coupon;
use App\Models\Subscription;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Notifications\DomainCreated;
use App\Tenant;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Exceptions\TenantDatabaseAlreadyExistsException;

class RegisterTenant
{
    public function create(array $data): Tenant
    {
        $name = $data['name'] ?? null;
        // If tenant already exists, return it instead of attempting to recreate the database
        $existing = Tenant::find($name);
        if ($existing) {
            if ($existing->domains()->count() === 0) {
                $existing->domains()->create([
                    'domain' => $data['domain'],
                ]);
            }

            return $existing;
        }

        try {
            /** @var Tenant */
            $tenant = Tenant::create([
                'id' => $name,
                'tenancy_db_name' => 'lakasir_'.$name,
                'tenancy_email' => $data['email'],
            ]);

            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);
        } catch (TenantDatabaseAlreadyExistsException $e) {
            $tenant = Tenant::where('id', $name)->first();
            if (! $tenant) {
                throw $e;
            }
        }

        $tenant->run(function () use ($data) {
            // Create user only if not exists to make tenant creation idempotent in tests
            $user = User::where('email', $data['email'])->first();
            if (! $user) {
                $user = User::create([
                    'email' => $data['email'],
                    'password' => bcrypt($data['password']),
                    'email_verified_at' => now(),
                    'is_owner' => true,
                ]);
            }

            About::create([
                'shop_name' => $data['full_name'] ?? null,
                'business_type' => $data['business_type'],
                'other_business_type' => $data['other_business_type'] ?? null,
            ]);

            $user->notify(new DomainCreated($data['domain']));

            Artisan::call('db:seed', [
                '--class' => 'PermissionSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'PaymentMethodSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
            ]);
            if (! $user->hasRole(Role::admin)) {
                $user->assignRole(Role::admin);
            }
        });

        // Create trial subscription in central DB (outside tenant context)
        $trialDays = 14;
        $coupon = null;
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])->first();
            if ($coupon && $coupon->isValid() && $coupon->type === 'trial_extension') {
                $trialDays += $coupon->trial_days ?? 0;
            }
        }

        Subscription::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => null,
                'status' => 'trialing',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays($trialDays),
                'starts_at' => now(),
            ]
        );

        if ($coupon) {
            $coupon->increment('used_count');
        }

        return $tenant;
    }
}
