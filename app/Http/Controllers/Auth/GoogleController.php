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
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Try to find user by google_id across all tenants
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                tenancy()->initialize($user->tenant_id);
                Auth::login($user, true);

                return redirect()->intended('/member');
            }

            // Check if email already exists
            $existingUser = User::where('email', $googleUser->getEmail())->first();
            if ($existingUser) {
                $existingUser->update(['google_id' => $googleUser->getId()]);

                tenancy()->initialize($existingUser->tenant_id);
                Auth::login($existingUser, true);

                return redirect()->intended('/member');
            }

            // New user → auto-create tenant with defaults
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

            $user = User::where('email', $googleUser->getEmail())->first();
            if ($user) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            Auth::login($user, true);

            return redirect()->intended('/member');
        } catch (\Throwable $e) {
            report($e);

            return redirect('/member/login')
                ->withErrors(['google' => 'Google authentication failed. Please try again.']);
        }
    }
}
