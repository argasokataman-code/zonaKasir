<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Tenants\Withdrawal;
use App\Models\Tenants\About;
use App\Services\Tenants\LedgerService;
use App\Services\Tenants\WithdrawalService;
use App\Services\Tenants\InsufficientBalanceException;
use App\Traits\HasTranslatableResource;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class WithdrawalPage extends Page implements HasActions, HasForms, HasTable
{
    use HasTranslatableResource,
        InteractsWithFormActions,
        InteractsWithForms,
        InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static string $view = 'filament.tenant.pages.withdrawal';

    protected static ?string $title = 'Withdrawal';

    protected static ?string $navigationLabel = 'Withdrawal';

    public $amount = '';

    public function mount(): void
    {
        $about = About::first();
        if ($about) {
            $this->form->fill([
                'amount' => '',
            ]);
        }
    }

    public function form(Form $form): Form
    {
        $maxWithdrawal = 0;
        $balance = app(LedgerService::class)->getCurrentBalance();
        $maxAllowed = (int) ($balance * 0.95);

        return $form
            ->schema([
                TextInput::make('amount')
                    ->label(__('Withdrawal Amount'))
                    ->prefix('Rp')
                    ->required()
                    ->numeric()
                    ->minValue(50000)
                    ->maxValue($maxAllowed < 50000 ? 0 : $maxAllowed)
                    ->placeholder('Min. Rp 50.000')
                    ->translateLabel(),
                Actions::make([
                    Action::make('submit_withdrawal')
                        ->label(__('Request Withdrawal'))
                        ->action('submitWithdrawal')
                        ->requiresConfirmation()
                        ->color('success')
                        ->icon('heroicon-o-check'),
                ]),
            ]);
    }

    public function submitWithdrawal(): void
    {
        $data = $this->form->getState();
        $amount = (float) $data['amount'];

        try {
            $idempotencyKey = 'wd-' . now()->timestampMicrosecond() . '-' . substr(md5(random_bytes(8)), 0, 8);

            app(WithdrawalService::class)->request(
                amount: $amount,
                idempotencyKey: $idempotencyKey,
            );

            Notification::make()
                ->title(__('Withdrawal requested successfully'))
                ->success()
                ->send();

            $this->form->fill(['amount' => '']);
        } catch (InsufficientBalanceException $e) {
            Notification::make()
                ->title(__($e->getMessage()))
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('Error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Withdrawal::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime(),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label(__('Bank')),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'processing',
                        'success' => 'completed',
                        'gray' => 'failed',
                    ]),
                TextColumn::make('processed_at')
                    ->label(__('Processed'))
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('rejection_reason')
                    ->label(__('Reason'))
                    ->placeholder('-')
                    ->limit(30),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('Withdrawal');
    }
}
