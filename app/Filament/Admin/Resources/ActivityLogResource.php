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

    private static array $modelLabels = [
        'User' => 'User',
        'Role' => 'Role',
        'Product' => 'Product',
        'Category' => 'Category',
        'Selling' => 'Selling',
        'Stock' => 'Stock',
        'Member' => 'Member',
        'Supplier' => 'Supplier',
        'PaymentMethod' => 'Payment Method',
        'Withdrawal' => 'Withdrawal',
        'Settlement' => 'Settlement',
        'MidtransPayment' => 'Midtrans Payment',
        'Voucher' => 'Voucher',
        'CashDrawer' => 'Cash Drawer',
        'About' => 'Shop Info',
        'Profile' => 'Profile',
        'LedgerEntry' => 'Ledger',
        'Subscription' => 'Subscription',
    ];

    private static function resolveSubject(Activity $record): string
    {
        $modelName = class_basename($record->subject_type ?? '');
        $label = self::$modelLabels[$modelName] ?? $modelName;
        $id = $record->subject_id ?? '?';

        // Try to get a meaningful name from properties
        $props = $record->properties?->toArray() ?? [];
        $attrs = $props['attributes'] ?? [];
        $old = $props['old'] ?? [];

        $name = $attrs['name']
            ?? $attrs['email']
            ?? $attrs['shop_name']
            ?? $attrs['code']
            ?? $attrs['description']
            ?? $attrs['full_name']
            ?? null;

        if ($name) {
            return "{$label} #{$id}: {$name}";
        }

        // Show what changed for updates
        if ($record->event === 'updated' && ! empty($old)) {
            $field = array_key_first($attrs);
            $oldVal = $old[$field] ?? '?';
            $newVal = $attrs[$field] ?? '?';
            $fieldLabel = ucwords(str_replace('_', ' ', $field));
            return "{$label} #{$id}: {$fieldLabel} \"{$oldVal}\" → \"{$newVal}\"";
        }

        return "{$label} #{$id}";
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, $record) => self::resolveSubject($record))
                    ->searchable()
                    ->limit(60),
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
                TextColumn::make('causer.email')
                    ->label('By')
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
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
                        'App\\Models\\Tenants\\About' => 'Shop Info',
                        'App\\Models\\Tenants\\Profile' => 'Profile',
                        'App\\Models\\Tenants\\LedgerEntry' => 'Ledger',
                        'App\\Models\\Subscription' => 'Subscription',
                    ])
                    ->searchable(),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('By User')
                    ->options(fn () => \App\Models\Tenants\User::pluck('email', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when($data['created_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateRemoveUsing(fn (array $data): string => match (true) {
                        filled($data['created_from'] ?? null) && filled($data['created_until'] ?? null)
                            => 'From ' . $data['created_from'] . ' to ' . $data['created_until'],
                        filled($data['created_from'] ?? null)
                            => 'From ' . $data['created_from'],
                        filled($data['created_until'] ?? null)
                            => 'Until ' . $data['created_until'],
                        default => '',
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\ActivityLogResource\Pages\ListActivities::route('/'),
        ];
    }
}
