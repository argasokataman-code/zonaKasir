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

        // On shared hosting (LiteSpeed/cPanel/WAF), the Livewire POST response
        // Set-Cookie header gets STRIPPED before reaching the browser.
        //
        // This means the browser never receives the new session cookie after
        // regenerate(), so it keeps sending the OLD session ID which gets
        // invalidated — auth data lost.
        //
        // Fix: Skip session()->regenerate(). Auth::attempt() already stored
        // auth data in the CURRENT session (set by StartSession middleware on
        // the GET /admin/login page). That session ID + cookie still work.
        // Just save to persist the auth data to disk.
        session()->save();

        return app(LoginResponse::class);
    }
}
