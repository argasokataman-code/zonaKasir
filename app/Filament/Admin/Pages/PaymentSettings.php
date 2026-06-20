<?php

namespace App\Filament\Admin\Pages;

use App\Models\PaymentSetting;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;

class PaymentSettings extends Page implements HasActions, HasForms
{
    use InteractsWithFormActions, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.admin.pages.payment-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data['midtrans_environment'] = PaymentSetting::get('midtrans', 'environment') ?? 'sandbox';
        $this->data['midtrans_sandbox_merchant_id'] = PaymentSetting::get('midtrans', 'sandbox_merchant_id') ?: PaymentSetting::get('midtrans', 'merchant_id');
        $this->data['midtrans_sandbox_client_key'] = PaymentSetting::get('midtrans', 'sandbox_client_key') ?: PaymentSetting::get('midtrans', 'client_key');
        $this->data['midtrans_sandbox_server_key'] = PaymentSetting::get('midtrans', 'sandbox_server_key') ?: PaymentSetting::get('midtrans', 'server_key');
        $this->data['midtrans_production_merchant_id'] = PaymentSetting::get('midtrans', 'production_merchant_id');
        $this->data['midtrans_production_client_key'] = PaymentSetting::get('midtrans', 'production_client_key');
        $this->data['midtrans_production_server_key'] = PaymentSetting::get('midtrans', 'production_server_key');
        $this->data['midtrans_webhook_ips'] = PaymentSetting::get('midtrans', 'webhook_ips');

        $this->data['flip_sandbox_secret_key'] = PaymentSetting::get('flip', 'sandbox_secret_key') ?: PaymentSetting::get('flip', 'secret_key');
        $this->data['flip_sandbox_webhook_token'] = PaymentSetting::get('flip', 'sandbox_webhook_token') ?: PaymentSetting::get('flip', 'webhook_token');
        $this->data['flip_sandbox_base_url'] = PaymentSetting::get('flip', 'sandbox_base_url') ?: (PaymentSetting::get('flip', 'base_url') ?? 'https://bigflip.id/api');
        $this->data['flip_production_secret_key'] = PaymentSetting::get('flip', 'production_secret_key');
        $this->data['flip_production_webhook_token'] = PaymentSetting::get('flip', 'production_webhook_token');
        $this->data['flip_production_base_url'] = PaymentSetting::get('flip', 'production_base_url') ?? 'https://big.flip.id/api/v2';

        $this->data['withdrawal_auto_approve_max'] = '5000000';
        $this->data['withdrawal_single_admin_max'] = '25000000';
        $this->data['withdrawal_fee_amount'] = '2500';
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('PaymentSettings')
                    ->tabs([
                        Tab::make('Midtrans')
                            ->id('midtrans')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Environment')
                                    ->headerActions([
                                        FormAction::make('testMidtrans')
                                            ->label('Test Connection')
                                            ->icon('heroicon-o-signal')
                                            ->action(function () {
                                                $data = $this->form->getState();
                                                $env = $data['midtrans_environment'] ?? 'sandbox';
                                                $prefix = $env === 'production' ? 'production_' : 'sandbox_';
                                                $merchantId = $data["midtrans_{$prefix}merchant_id"] ?? '';
                                                $serverKey = $data["midtrans_{$prefix}server_key"] ?? '';

                                                if (!$merchantId || !$serverKey) {
                                                    Notification::make()->title('Fill credentials first')->warning()->send();
                                                    return;
                                                }

                                                $url = $env === 'production'
                                                    ? 'https://api.midtrans.com/v2'
                                                    : 'https://api.sandbox.midtrans.com/v2';

                                                try {
                                                    $response = Http::withBasicAuth($serverKey, '')
                                                        ->get("{$url}/test-connection-" . time() . "/status");
                                                    if ($response->status() === 404) {
                                                        Notification::make()->title('✅ Connected! Server Key is valid.')->success()->send();
                                                    } elseif ($response->status() === 401) {
                                                        Notification::make()->title('❌ Invalid Server Key. Check your credentials.')->danger()->send();
                                                    } elseif ($response->status() === 403) {
                                                        Notification::make()->title('❌ Forbidden. Merchant ID mismatch.')->danger()->send();
                                                    } else {
                                                        Notification::make()->title("⚠️ Unexpected: HTTP {$response->status()}")->warning()->send();
                                                    }
                                                } catch (\Exception $e) {
                                                    Notification::make()->title("❌ Connection failed: {$e->getMessage()}")->danger()->send();
                                                }
                                            }),
                                    ])
                                    ->schema([
                                        Select::make('midtrans_environment')
                                            ->label('Active Environment')
                                            ->options([
                                                'sandbox' => 'Sandbox',
                                                'production' => 'Production',
                                            ])
                                            ->required()
                                            ->helperText('Credentials below are used based on this selection.'),
                                    ]),
                                Section::make('Sandbox Credentials')
                                    ->description('Used when environment is set to Sandbox')
                                    ->schema([
                                        TextInput::make('midtrans_sandbox_merchant_id')
                                            ->label('Merchant ID'),
                                        TextInput::make('midtrans_sandbox_client_key')
                                            ->label('Client Key')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('midtrans_sandbox_server_key')
                                            ->label('Server Key')
                                            ->password()
                                            ->revealable(),
                                    ]),
                                Section::make('Production Credentials')
                                    ->description('Used when environment is set to Production')
                                    ->schema([
                                        TextInput::make('midtrans_production_merchant_id')
                                            ->label('Merchant ID'),
                                        TextInput::make('midtrans_production_client_key')
                                            ->label('Client Key')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('midtrans_production_server_key')
                                            ->label('Server Key')
                                            ->password()
                                            ->revealable(),
                                    ]),
                                Section::make('Webhook')
                                    ->schema([
                                        TextInput::make('midtrans_webhook_ips')
                                            ->label('IP Whitelist')
                                            ->helperText('Comma-separated IPs. Example: 52.76.155.198,52.76.156.139')
                                            ->placeholder('52.76.155.198,52.76.156.139'),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('midtrans_webhook_url')
                                                    ->label('Notification URL')
                                                    ->default(url('/api/webhooks/midtrans'))
                                                    ->readOnly(),
                                                TextInput::make('midtrans_finish_url')
                                                    ->label('Finish Redirect URL')
                                                    ->default(url('/member'))
                                                    ->readOnly(),
                                            ]),
                                    ]),
                            ]),
                        Tab::make('SnapBi')
                            ->id('snapbi')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Section::make('BI-SNAP (QRIS / GoPay / ShopeePay)')
                                    ->description('Same credentials are used for both sandbox and production.')
                                    ->schema([
                                        TextInput::make('snapbi_client_id')
                                            ->label('Client ID'),
                                        TextInput::make('snapbi_client_secret')
                                            ->label('Client Secret')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('snapbi_partner_id')
                                            ->label('Partner ID'),
                                        TextInput::make('snapbi_channel_id')
                                            ->label('Channel ID'),
                                        TextInput::make('snapbi_merchant_id')
                                            ->label('SnapBi Merchant ID'),
                                        TextInput::make('snapbi_private_key')
                                            ->label('Private Key')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('snapbi_public_key')
                                            ->label('Public Key')
                                            ->password()
                                            ->revealable(),
                                    ]),
                            ]),
                        Tab::make('Flip (Payout)')
                            ->id('flip')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Environment')
                                    ->schema([
                                        Select::make('flip_environment')
                                            ->label('Active Environment')
                                            ->options([
                                                'sandbox' => 'Sandbox',
                                                'production' => 'Production',
                                            ])
                                            ->default('production')
                                            ->helperText('Flip uses the same env as Midtrans by default. Override here if needed.'),
                                    ]),
                                Section::make('Sandbox Credentials')
                                    ->schema([
                                        TextInput::make('flip_sandbox_secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('flip_sandbox_webhook_token')
                                            ->label('Webhook Token')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('flip_sandbox_base_url')
                                            ->label('Base URL')
                                            ->default('https://bigflip.id/api'),
                                    ]),
                                Section::make('Production Credentials')
                                    ->schema([
                                        TextInput::make('flip_production_secret_key')
                                            ->label('Secret Key')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('flip_production_webhook_token')
                                            ->label('Webhook Token')
                                            ->password()
                                            ->revealable(),
                                        TextInput::make('flip_production_base_url')
                                            ->label('Base URL')
                                            ->default('https://big.flip.id/api/v2'),
                                    ]),
                                Section::make('Webhook')
                                    ->schema([
                                        TextInput::make('flip_webhook_url')
                                            ->label('Disbursement Webhook URL')
                                            ->default(url('/api/webhooks/flip'))
                                            ->readOnly(),
                                    ]),
                            ]),
                        Tab::make('Withdrawal Rules')
                            ->id('withdrawal')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Approval Thresholds')
                                    ->description('These values are hardcoded in config files.')
                                    ->schema([
                                        TextInput::make('withdrawal_auto_approve_max')
                                            ->label('Auto Approve (≤)')
                                            ->prefix('Rp')
                                            ->readOnly(),
                                        TextInput::make('withdrawal_single_admin_max')
                                            ->label('Single Admin (≤)')
                                            ->prefix('Rp')
                                            ->readOnly(),
                                        TextInput::make('withdrawal_fee_amount')
                                            ->label('Fee per Transaction')
                                            ->prefix('Rp')
                                            ->readOnly(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ]);
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save All Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PaymentSetting::saveGroup('midtrans', [
            'environment' => $data['midtrans_environment'],
            'sandbox_merchant_id' => $data['midtrans_sandbox_merchant_id'],
            'sandbox_client_key' => $data['midtrans_sandbox_client_key'],
            'sandbox_server_key' => $data['midtrans_sandbox_server_key'],
            'production_merchant_id' => $data['midtrans_production_merchant_id'],
            'production_client_key' => $data['midtrans_production_client_key'],
            'production_server_key' => $data['midtrans_production_server_key'],
            'webhook_ips' => $data['midtrans_webhook_ips'],
        ]);

        PaymentSetting::saveGroup('flip', [
            'sandbox_secret_key' => $data['flip_sandbox_secret_key'],
            'sandbox_webhook_token' => $data['flip_sandbox_webhook_token'],
            'sandbox_base_url' => $data['flip_sandbox_base_url'],
            'production_secret_key' => $data['flip_production_secret_key'],
            'production_webhook_token' => $data['flip_production_webhook_token'],
            'production_base_url' => $data['flip_production_base_url'],
        ]);

        Notification::make()
            ->title('Payment settings saved')
            ->success()
            ->send();
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
