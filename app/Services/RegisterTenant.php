<?php

namespace App\Services;

use App\Constants\Role;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RegisterTenant
{
    public function create(array $data): string
    {
        $name = $data['name'] ?? uniqid();

        TenantContext::set($name);

        \App\Tenant::unguarded(fn () => \App\Tenant::create([
            'id' => $name,
            'tenancy_email' => $data['email'],
        ]));

        $user = User::create([
            'tenant_id' => $name,
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'email_verified_at' => now(),
            'is_owner' => true,
        ]);

        About::create([
            'tenant_id' => $name,
            'shop_name' => $data['full_name'] ?? null,
            'business_type' => $data['business_type'],
            'other_business_type' => $data['other_business_type'] ?? null,
        ]);

        Artisan::call('db:seed', ['--class' => 'PermissionSeeder']);
        Artisan::call('db:seed', ['--class' => 'PaymentMethodSeeder']);
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        $role = \App\Models\Tenants\Role::firstOrCreate(['name' => Role::admin, 'guard_name' => 'web']);
        if ($name && ! $role->tenant_id) {
            $role->update(['tenant_id' => $name]);
        }

        if (! $user->hasRole(Role::admin)) {
            $user->assignRole(Role::admin);
        }

        $trialDays = $data['trial_days'] ?? 7;
        $coupon = null;
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::select('id', 'code', 'type', 'trial_days', 'is_valid', 'expired_at')->where('code', $data['coupon_code'])->first();
            if ($coupon && $coupon->isValid() && $coupon->type === 'trial_extension') {
                $trialDays += $coupon->trial_days ?? 0;
            }
        }

        $cheapestPlan = Plan::select('id', 'price_monthly')->where('is_active', true)
            ->where('price_monthly', '>', 0)
            ->orderBy('price_monthly')
            ->first() ?? Plan::select('id')->first();

        Subscription::create([
            'tenant_id' => $name,
            'plan_id' => $cheapestPlan?->id ?? 1,
            'status' => 'trialing',
            'billing_cycle' => 'monthly',
            'trial_ends_at' => now()->addDays($trialDays),
            'starts_at' => now(),
        ]);

        if ($coupon) {
            $coupon->increment('used_count');
        }

        return $name;
    }
}
