<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.admin.pages.payment-settings';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'MIDTRANS_MERCHANT_ID' => config('midtrans.merchant_id'),
            'MIDTRANS_CLIENT_KEY' => config('midtrans.client_key'),
            'MIDTRANS_SERVER_KEY' => config('midtrans.server_key'),
            'MIDTRANS_ENVIRONMENT' => config('midtrans.environment'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Midtrans Configuration')
                    ->description('Settings are managed via .env file on the server. These values are read-only and shown for reference.')
                    ->schema([
                        TextInput::make('MIDTRANS_MERCHANT_ID')
                            ->label('Merchant ID')
                            ->readOnly()
                            ->password()
                            ->revealable(),
                        TextInput::make('MIDTRANS_CLIENT_KEY')
                            ->label('Client Key')
                            ->readOnly()
                            ->password()
                            ->revealable(),
                        TextInput::make('MIDTRANS_SERVER_KEY')
                            ->label('Server Key')
                            ->readOnly()
                            ->password()
                            ->revealable(),
                        TextInput::make('MIDTRANS_ENVIRONMENT')
                            ->label('Environment')
                            ->readOnly(),
                    ]),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }

    public function getTitle(): string
    {
        return __('Payment Settings');
    }
}
