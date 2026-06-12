<?php

namespace App\Filament\Admin\Resources;

use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $pluralLabel = 'Subscriptions';

    protected static ?string $slug = 'subscriptions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'trialing' => 'info',
                        'active' => 'success',
                        'past_due' => 'warning',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('billing_cycle')
                    ->badge(),
                TextColumn::make('starts_at')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\SubscriptionResource\Pages\ListSubscriptions::route('/'),
        ];
    }
}
