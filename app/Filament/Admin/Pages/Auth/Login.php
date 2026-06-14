<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Facades\Filament;

class Login extends \Filament\Pages\Auth\Login
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        // On some shared hosting (LiteSpeed/cPanel), the Livewire POST response
        // Set-Cookie header may be stripped by WAF/ModSecurity. This causes the
        // browser to send the old session ID on the redirect, losing the auth data.
        //
        // Fix: save the session to disk with auth data BEFORE regenerating.
        // This way, even if the browser uses the old session ID, it will still
        // find the admin auth token and authentication will succeed.
        session()->save();

        // Now regenerate (sends new cookie to browser if not stripped).
        session()->regenerate();

        return app(LoginResponse::class);
    }
}
