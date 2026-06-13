<?php

namespace App\Filament\Tenant\Pages;

use App\Models\License as LicenseModel;
use App\Services\LicenseService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class License extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $title = 'License';

    protected static string $view = 'filament.tenant.pages.license';

    protected static string $layout = 'filament-panels::components.layout.base';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?LicenseModel $license = null;

    public function mount(LicenseService $licenseService): void
    {
        $tenantId = tenant('id');
        $this->license = $licenseService->getActiveLicense($tenantId);

        // If the license exists but is expired, mark it
        if ($this->license && $this->license->isExpired()) {
            $this->license->update(['status' => 'expired']);
            $this->license->refresh();
        }
    }

    public function activate(LicenseService $licenseService): void
    {
        $data = $this->form->getState();
        $tenantId = tenant('id');

        $license = $licenseService->validateAndActivate($data['key'], $tenantId);

        if (! $license) {
            Notification::make('invalid_key')
                ->title('Invalid license key')
                ->danger()
                ->send();

            return;
        }

        $this->license = $license;

        Notification::make('activated')
            ->title('License activated!')
            ->success()
            ->send();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('key')
                    ->label('License Key')
                    ->placeholder('ZK-XXXXXXXX')
                    ->required()
                    ->string(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Activate License')
                ->submit('activate'),
        ];
    }
}
