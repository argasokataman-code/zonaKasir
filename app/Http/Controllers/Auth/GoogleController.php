<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
use App\Services\RegisterTenant;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->with(['prompt' => 'select_account'])
            ->stateless()
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $googleId = $googleUser->getId();

            // Find tenant by google_id in central DB
            $tenant = \App\Tenant::where('google_id', $googleId)->first();

            if ($tenant) {
                TenantContext::set($tenant->id);
                $user = User::where('google_id', $googleId)->first();

                if ($user) {
                    Auth::login($user, true);
                    request()->session()->save();
                    return redirect()->intended('/member');
                }
            }

            // Fallback: find user directly by google_id (handles missing central record)
            $user = User::withoutGlobalScopes()->where('google_id', $googleId)->first();
            if ($user) {
                TenantContext::set($user->tenant_id);
                // Ensure central tenant record exists (unguarded because Tenant $guarded = ['id'])
                \App\Tenant::unguarded(fn () => \App\Tenant::firstOrCreate(
                    ['id' => $user->tenant_id],
                    ['tenancy_email' => $googleUser->getEmail()]
                ))->update(['google_id' => $googleId]);
                Auth::login($user, true);
                request()->session()->save();
                return redirect()->intended('/member');
            }

            // Check email in tenants table
            $tenant = \App\Tenant::where('tenancy_email', $googleUser->getEmail())->first();
            if ($tenant) {
                TenantContext::set($tenant->id);
                $user = User::where('email', $googleUser->getEmail())->first();
                if ($user) {
                    $user->update(['google_id' => $googleId]);
                    $tenant->update(['google_id' => $googleId]);
                    Auth::login($user, true);
                    request()->session()->save();
                    return redirect()->intended('/member');
                }
            }

            // Fallback: search user across all tenants by email (handles missing central record)
            $user = User::withoutGlobalScopes()->where('email', $googleUser->getEmail())->first();
            if ($user) {
                TenantContext::set($user->tenant_id);
                // Ensure central tenant record exists (unguarded because Tenant $guarded = ['id'])
                $tenant = \App\Tenant::unguarded(fn () => \App\Tenant::firstOrCreate(
                    ['id' => $user->tenant_id],
                    ['tenancy_email' => $googleUser->getEmail()]
                ));
                $user->update(['google_id' => $googleId]);
                $tenant->update(['google_id' => $googleId]);
                Auth::login($user, true);
                request()->session()->save();
                return redirect()->intended('/member');
            }

            // New user → create tenant + user
            $tenantName = strtolower(
                str_replace(' ', '_', $googleUser->getName() ?? $googleUser->getNickname() ?? 'user')
            ).'_'.uniqid();

            $registerTenant = app(RegisterTenant::class);
            $tenantId = $registerTenant->create([
                'name' => $tenantName,
                'full_name' => $googleUser->getName() ?? 'My Shop',
                'email' => $googleUser->getEmail(),
                'password' => uniqid('ggl_', true),
                'business_type' => 'retail',
                'trial_days' => 7,
            ]);

            // Store google_id in both tenant user and central tenants table
            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->update(['google_id' => $googleId]);
            }
            \App\Tenant::where('id', $tenantId)->update(['google_id' => $googleId]);

            // Set welcome session for new users
            session(['welcome_type' => 'trial']);
            session(['welcome_data' => []]);

            Auth::login($user, true);
            request()->session()->save();
            return redirect()->intended('/member');
        } catch (\Throwable $e) {
            report($e);
            return redirect('/member/login')
                ->withErrors(['google' => 'Google authentication failed. Please try again.']);
        }
    }
}
