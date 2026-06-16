<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WithdrawalApprovalResource\Pages;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\WithdrawalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;

class WithdrawalApprovalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'Withdrawal Approval';

    protected static ?string $label = 'Withdrawal';

    protected static ?string $pluralLabel = 'Withdrawals';

    protected static ?string $slug = 'withdrawal-approval';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Withdrawal::withoutGlobalScope('tenant')->with('requestedBy'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('fee_amount')
                    ->label('Fee')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable(),
                TextColumn::make('bank_account_name')
                    ->label('Account Name')
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->label('Account Number')
                    ->searchable(),
                TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'approved' => 'primary',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'rejected' => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'approved' => 'Approved',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'tenant_request' => 'Tenant Request',
                        'admin_direct' => 'Admin Direct',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when($data['created_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Withdrawal')
                    ->modalDescription('Are you sure you want to approve this withdrawal? Funds will be disbursed via Flip.')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        try {
                            $adminId = auth('admin')->id();
                            app(WithdrawalService::class)->approve($record->id, $adminId);

                            Notification::make()
                                ->title('Withdrawal approved & disbursed via Flip')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Withdrawal')
                    ->modalDescription('Are you sure you want to reject this withdrawal?')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $adminId = auth('admin')->id();
                            app(WithdrawalService::class)->reject($record->id, $adminId, $data['reason']);

                            Notification::make()
                                ->title('Withdrawal rejected')
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }
}
