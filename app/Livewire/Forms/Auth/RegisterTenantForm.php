<?php

namespace App\Livewire\Forms\Auth;

use App\Services\RegisterTenant;
use App\Services\TenantContext;
use App\Services\TurnstileService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.components.layouts.guest')]
class RegisterTenantForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public ?string $turnstileToken = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Akun Pemilik'))
                    ->description(__('Data login untuk akses panel'))
                    ->icon('heroicon-o-user')
                    ->columns(['md' => 2, 'sm' => 1])
                    ->schema([
                        TextInput::make('full_name')
                            ->label(__('Full Name'))
                            ->string()
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->rules(['confirmed', Password::defaults()])
                            ->columnSpan(1),
                        TextInput::make('password_confirmation')
                            ->label(__('Password Confirmation'))
                            ->password()
                            ->columnSpan(1),
                    ]),
                Section::make(__('Data Toko'))
                    ->description(__('Informasi usaha Anda'))
                    ->icon('heroicon-o-shopping-bag')
                    ->columns(['md' => 2, 'sm' => 1])
                    ->schema([
                        TextInput::make('shop_name')
                            ->label(__('Shop Name'))
                            ->string()
                            ->required()
                            ->columnSpan(1),
                        Select::make('business_type')
                            ->label(__('Business Type'))
                            ->options([
                                'retail' => __('Retail'),
                                'wholesale' => __('Wholesale'),
                                'fnb' => __('F&B'),
                                'fashion' => __('Fashion'),
                                'pharmacy' => __('Pharmacy'),
                                'other' => __('Other'),
                            ])
                            ->live()
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('shop_location')
                            ->label(__('Shop Location'))
                            ->string()
                            ->columnSpan(['md' => 2, 'sm' => 1]),
                        TextInput::make('other_business_type')
                            ->label('Lainnya')
                            ->visible(fn (Get $get): bool => $get('business_type') == 'other')
                            ->required(fn (Get $get): bool => $get('business_type') == 'other')
                            ->string()
                            ->columnSpan(['md' => 2, 'sm' => 1]),
                    ]),
                Section::make(__('Kupon (Opsional)'))
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        TextInput::make('coupon_code')
                            ->label('Kode Kupon')
                            ->placeholder('Masukkan kode kupon jika ada')
                            ->maxLength(50),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(RegisterTenant $registerTenant): void
    {
        $data = $this->form->getState();

        // Validate Turnstile if enabled
        $turnstile = app(TurnstileService::class);
        if ($turnstile->isEnabled()) {
            if (empty($this->turnstileToken)) {
                $this->addError('turnstile', 'Please complete the verification.');

                return;
            }
            if (! $turnstile->validate($this->turnstileToken, TurnstileService::getVisitorIp())) {
                $this->addError('turnstile', 'Verification failed. Please try again.');

                return;
            }
        }

        $tenantId = $registerTenant->create(array_merge($data, [
            'name' => strtolower(str_replace(' ', '_', $data['full_name'])).'_'.uniqid(),
        ]));

        TenantContext::set($tenantId);
        Auth::login(\App\Models\Tenants\User::where('email', $data['email'])->first());
        session()->save();

        $this->dispatch('redirect-after-register');
    }

    public function render()
    {
        return view('livewire.forms.auth.register');
    }
}
