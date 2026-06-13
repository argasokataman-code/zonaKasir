<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\WithdrawalResource\Pages;
use App\Models\Tenants\Withdrawal;
use App\Traits\HasTranslatableResource;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = Withdrawal::class;

    protected static ?string $label = 'Withdrawal';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';

    protected static ?string $navigationLabel = 'Withdrawals';

    protected static ?string $slug = 'withdrawals';

    public static function getBreadcrumb(): string
    {
        return __('Withdrawals');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('bank_name')
                    ->label(__('Bank')),
                TextColumn::make('bank_account_name')
                    ->label(__('Account Name')),
                TextColumn::make('bank_account_number')
                    ->label(__('Account Number')),
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
                TextColumn::make('requestedBy.name')
                    ->label(__('Requested By')),
                TextColumn::make('processed_at')
                    ->label(__('Processed At'))
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->label(__('Requested At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Withdrawal $record) => self::approveWithdrawal($record)),
                Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Withdrawal $record) => self::rejectWithdrawal($record)),
            ]);
    }

    protected static function approveWithdrawal(Withdrawal $withdrawal): void
    {
        try {
            app(\App\Services\Tenants\WithdrawalService::class)->approve($withdrawal->id, auth()->id());
            Notification::make()->title(__('Withdrawal approved'))->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title(__('Failed: ' . $e->getMessage()))->danger()->send();
        }
    }

    protected static function rejectWithdrawal(Withdrawal $withdrawal): void
    {
        \App\Services\Tenants\WithdrawalService::class;
        $withdrawal->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejection_reason' => 'Rejected by admin',
        ]);
        Notification::make()->title(__('Withdrawal rejected'))->warning()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }
}
