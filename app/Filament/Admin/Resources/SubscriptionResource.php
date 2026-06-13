<?php

namespace App\Filament\Admin\Resources;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $pluralLabel = 'Subscriptions';

    protected static ?string $slug = 'subscriptions';

    public static function form(Form $form): Form
    {
        $cn = Config::get('tenancy.database.central_connection', 'mysql');
        $plans = Plan::on($cn)->pluck('name', 'id')->toArray();

        return $form
            ->schema([
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->options($plans)
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\Select::make('billing_cycle')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('trial_ends_at'),
                Forms\Components\DateTimePicker::make('ends_at'),
            ]);
    }

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
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'active' || $record->status === 'trialing')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'active'])),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->hidden(fn ($record) => $record->status === 'expired' || $record->status === 'cancelled' || $record->status === 'past_due')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'past_due'])),
                Tables\Actions\Action::make('expire')
                    ->label('Expire')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->hidden(fn ($record) => $record->status === 'expired' || $record->status === 'cancelled')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'expired'])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
