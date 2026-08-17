<?php

namespace App\Filament\Tenant\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\View;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use App\Filament\Tenant\Pages\Responses\TenantLoginResponse;

class TenantLogin extends Login
{
    protected static string $view = 'filament.tenant.pages.tenant-login-custom';

    public function render(): \Illuminate\Contracts\View\View
    {
        return view($this->getView(), $this->getViewData())
            ->layout('filament-panels::components.layout.base', [
                'livewire' => $this,
                'maxContentWidth' => null,
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (\DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $auth = Auth::guard('web');

        if (! $auth->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        /** @var \App\Models\Tenants\User|null $user */
        $user = $auth->user();

        if (! $user || ! $user->can('access web app')) {
            $auth->logout();

            throw ValidationException::withMessages([
                'data.email' => 'You do not have permission to access the web app',
            ]);

            return null;
        }

        \Illuminate\Support\Facades\Log::info('Tenant login success', [
            'user_id' => $user->id,
            'email' => $user->email,
            'tenant_id' => $user->tenant_id,
        ]);

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

        // Filament::auth() resolves to the admin panel on Livewire POST
        // (URL is /livewire/update, not /member/*), so redirect explicitly.
        $this->redirect(route('filament.tenant.pages.dashboard'), navigate: false);

        return app(TenantLoginResponse::class);
    }

    public function mount(): void
    {
        if (Auth::guard('web')->check()) {
            $this->redirect(route('filament.tenant.pages.dashboard'), navigate: false);
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
        ]);
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
