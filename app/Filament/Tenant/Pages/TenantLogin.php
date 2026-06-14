<?php

namespace App\Filament\Tenant\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\View;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class TenantLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $guard = Filament::getCurrentPanel()?->getAuthGuard();
        $attempt = Auth::guard($guard)->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $data['remember'] ?? false,
        );
        Log::debug('TenantLogin debug', [
            'panel' => Filament::getCurrentPanel()?->getId(),
            'guard' => $guard,
            'email' => $data['email'] ?? 'MISSING',
            'attempt_result' => $attempt ? 'true' : 'false',
        ]);
        if (! $attempt) {
            $this->throwFailureValidationException();
        }
        $loginResponse = app(LoginResponse::class);
        /** @var \App\Models\Tenants\User|null $user */
        $user = Filament::auth()->user();

        // If authentication did not produce a user (invalid credentials),
        // let the parent class handle the validation failure. Otherwise,
        // ensure we don't call methods on null.
        if (! $user || ! $user->can('access web app')) {
            throw ValidationException::withMessages([
                'data.email' => 'You do not have permission to access the web app',
            ]);

            return null;
        }
        $user->profile()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
            ],
            [
                'timezone' => 'Asia/Jakarta',
            ]
        );

        return $loginResponse;
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
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
