<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
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
            $googleId = $googleUser->getId();

            // Find tenant by google_id in central DB
            $tenant = \App\Tenant::where('google_id', $googleId)->first();

            if ($tenant) {
                tenancy()->initialize($tenant);
                $user = User::where('google_id', $googleId)->first();

                if ($user) {
                    Auth::login($user, true);
                    return redirect()->intended('/member');
                }
            }

            // Check email in tenants table
            $tenant = \App\Tenant::where('tenancy_email', $googleUser->getEmail())->first();
            if ($tenant) {
                tenancy()->initialize($tenant);
                $user = User::where('email', $googleUser->getEmail())->first();
                if ($user) {
                    $user->update(['google_id' => $googleId]);
                    $tenant->update(['google_id' => $googleId]);
                    Auth::login($user, true);
                    return redirect()->intended('/member');
                }
            }

            // No existing account → redirect to register
            return redirect()->route('auth.register');
        } catch (\Throwable $e) {
            report($e);
            return redirect('/member/login')
                ->withErrors(['google' => 'Google authentication failed. Please try again.']);
        }
    }
}
