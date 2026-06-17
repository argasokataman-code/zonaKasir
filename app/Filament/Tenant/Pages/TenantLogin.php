<?php

namespace App\Filament\Tenant\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\View;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class TenantLogin extends Login
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

        /** @var \App\Models\Tenants\User|null $user */
        $user = Filament::auth()->user();

        if (! $user || ! $user->can('access web app')) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => 'You do not have permission to access the web app',
            ]);

            return null;
        }

        if ($user) {
            \Illuminate\Support\Facades\Log::info('Tenant login success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
            ]);
        }

        $user->profile()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
            ],
            [
                'timezone' => 'Asia/Jakarta',
                'locale' => 'en',
            ]
        );

        // Skip session()->regenerate() — LiteSpeed/WAF strips Set-Cookie
        // from Livewire POST responses, so browser never gets the new cookie.
        // Auth::attempt() already stored auth in the current session.
        session()->save();

        return app(LoginResponse::class);
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $this->redirect(Filament::getUrl());
        }

        if (app()->environment('demo')) {
            $this->form->fill([
                'email' => 'demo@zonakasir.com',
                'password' => 'passwordsangatrahasia'
            ]);
        }
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            ...parent::form($form)->getComponents(),
            $this->getGoogleLoginButton(),
        ]);
    }

    protected function getGoogleLoginButton(): Component
    {
        return View::make('filament.tenant.pages.google-login-button');
    }
}
