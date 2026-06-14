<?php

namespace App\Livewire\Forms\Auth;

use App\Services\RegisterTenant;
use App\Services\TurnstileService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
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
                Wizard::make([
                    Wizard\Step::make(__('Owner\'s Account'))
                        ->schema([
                            TextInput::make('full_name')
                                ->label(__('Full Name'))
                                ->string()
                                ->required(),
                            TextInput::make('email')
                                ->label(__('Email'))
                                ->email()
                                ->required(),
                            TextInput::make('password')
                                ->password()
                                ->required()
                                ->rules(['confirmed', Password::defaults()]),
                            TextInput::make('password_confirmation')
                                ->label(__('Password Confirmation'))
                                ->password(),
                        ])
                        ->columns(1)
                        ->icon('heroicon-o-user'),
                    Wizard\Step::make(__('Shop Detail'))
                        ->schema([
                            TextInput::make('shop_name')
                                ->label(__('Shop Name'))
                                ->string()
                                ->required(),
                            TextInput::make('shop_location')
                                ->label(__('Shop Location'))
                                ->string(),
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
                                ->required(),
                            TextInput::make('other_business_type')
                                ->label('Lainnya')
                                ->visible(fn (Get $get): bool => $get('business_type') == 'other')
                                ->required(fn (Get $get): bool => $get('business_type') == 'other')
                                ->string(),
                        ])
                        ->icon('heroicon-o-shopping-bag'),
                    Wizard\Step::make(__('Coupon'))
                        ->description(__('Have a coupon code? Enter it here (optional)'))
                        ->schema([
                            TextInput::make('coupon_code')
                                ->label('Kode Kupon (Opsional)')
                                ->placeholder('Masukkan kode kupon jika ada')
                                ->maxLength(50),
                        ])
                        ->icon('heroicon-o-ticket'),
                ])
                    ->submitAction(new HtmlString(
                        Blade::render(<<<'BLADE'
                                    <x-filament::button
                                        size="sm"
                                        wire:click="create"
                                    >
                                        Submit
                                    </x-filament::button>
                                  BLADE))),
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

        $this->redirect('/member/login');
    }

    public function render()
    {
        return view('livewire.forms.auth.register');
    }
}
