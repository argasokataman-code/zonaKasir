<?php

namespace App\Services;

use App\Constants\Role;
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

            // --force is required when APP_ENV=production; without it db:seed
            // aborts and the owner ends up without the admin role
            Artisan::call('db:seed', [
                '--class' => 'PermissionSeeder',
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => 'PaymentMethodSeeder',
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
                '--force' => true,
            ]);
            if (! $user->hasRole(Role::admin)) {
                $user->assignRole(Role::admin);
            }
        });

        return $tenant;
    }
}
