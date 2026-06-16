<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $pluralLabel = 'Activity Log';

    protected static ?string $slug = 'logs';

    protected static ?string $recordTitleAttribute = 'description';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Action')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        'activated' => 'success',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label('Subject'),
                TextColumn::make('causer.email')
                    ->label('By')
                    ->formatStateUsing(fn ($state, $record) => $record->causer?->email ?? '-'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'activated' => 'Activated',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options([
                        'App\\Models\\Tenants\\User' => 'User',
                        'App\\Models\\Tenants\\Role' => 'Role',
                        'App\\Models\\Tenants\\Product' => 'Product',
                        'App\\Models\\Tenants\\Category' => 'Category',
                        'App\\Models\\Tenants\\Selling' => 'Selling',
                        'App\\Models\\Tenants\\Stock' => 'Stock',
                        'App\\Models\\Tenants\\Member' => 'Member',
                        'App\\Models\\Tenants\\Supplier' => 'Supplier',
                        'App\\Models\\Tenants\\PaymentMethod' => 'Payment Method',
                        'App\\Models\\Tenants\\Withdrawal' => 'Withdrawal',
                        'App\\Models\\Tenants\\Settlement' => 'Settlement',
                        'App\\Models\\Tenants\\MidtransPayment' => 'Midtrans Payment',
                        'App\\Models\\Tenants\\Voucher' => 'Voucher',
                        'App\\Models\\Tenants\\CashDrawer' => 'Cash Drawer',
                        'App\\Models\\Tenants\\About' => 'About',
                        'App\\Models\\Tenants\\Profile' => 'Profile',
                        'App\\Models\\Tenants\\LedgerEntry' => 'Ledger Entry',
                    ])
                    ->searchable(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From')
                            ->placeholder('Start date'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('To')
                            ->placeholder('End date'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateRemoveUsing(fn (array $data): string => filled($data['created_from'] ?? null)
                        ? 'From ' . $data['created_from'] . ($data['created_until'] ? ' to ' . $data['created_until'] : '')
                        : ($data['created_until'] ? 'Until ' . $data['created_until'] : '')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ActivityLogResource\Pages\ListActivities::route('/'),
        ];
    }
}
