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

        // Capture old session ID BEFORE attempt().
        // Auth::attempt() internally calls session()->migrate(true) which:
        //   1. Deletes the old session file
        //   2. Changes the session ID to a new one
        // On LiteSpeed/cPanel shared hosting, WAF strips Set-Cookie headers
        // from Livewire POST responses, so the browser never receives the new
        // session cookie and keeps sending the old (now deleted) session ID.
        // Fix: after attempt(), restore the old session ID so the auth data
        // is written there — the browser's existing cookie still points to it.
        $originalSessionId = session()->getId();

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

        // Restore the original session ID so auth data persists where the
        // browser's existing cookie already points. This works even when the
        // new session cookie is stripped by LiteSpeed WAF.
        session()->setId($originalSessionId);
        session()->save();

        return app(LoginResponse::class);
    }
}
