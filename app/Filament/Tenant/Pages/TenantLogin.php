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
        \Illuminate\Support\Facades\Log::debug('TenantLogin auth check', [
            'panel' => Filament::getCurrentPanel()?->getId(),
            'guard' => Filament::getCurrentPanel()?->getAuthGuard(),
        ]);
        $loginResponse = parent::authenticate();
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
