<?php

namespace App\Filament\Admin\Resources;

use App\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $pluralLabel = 'Tenants';

    protected static ?string $slug = 'tenants';

    public static function getNavigationBadge(): ?string
    {
        return (string) Tenant::count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Tenant')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('domains.domain')
                    ->label('Domain')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('data->full_name')
                    ->label('Name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('data->email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('tenancy_db_name')
                    ->label('Database')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Suspended',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))),
            ])
            ->actions([
                Action::make('impersonate')
                    ->label('Login as')
                    ->icon('heroicon-o-user-circle')
                    ->color('gray')
                    ->action(fn (Tenant $record) => static::impersonate($record))
                    ->visible(fn ($record) => $record->is_active),
                Tables\Actions\ViewAction::make()
                    ->form(fn ($record) => static::getViewFormSchema($record)),
                Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Suspend' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => static::toggleActive($record)),
                DeleteAction::make()
                    ->before(function (Tenant $record) {
                        $record->run(function () {
                            \App\Models\Tenants\User::query()->delete();
                        });
                        $record->domains()->delete();
                        activity()
                            ->causedBy(auth('admin')->user())
                            ->performedOn($record)
                            ->event('deleted')
                            ->log('Tenant deleted');
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            $record->run(function () {
                                \App\Models\Tenants\User::query()->delete();
                            });
                            $record->domains()->delete();
                            $record->delete();
                        }
                    }),
            ]);
    }

    public static function impersonate(Tenant $tenant): void
    {
        $domain = $tenant->domains()->first()?->domain;
        if (! $domain) {
            Notification::make()->danger()->title('No domain found')->send();
            return;
        }

        $url = "https://{$domain}/member/login";
        Notification::make()
            ->success()
            ->title('Login as ' . ($tenant->data['full_name'] ?? $tenant->id))
            ->body("Click to open: {$url}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('open')
                    ->label('Open Tenant')
                    ->url($url, shouldOpenInNewTab: true),
            ])
            ->send();
    }

    public static function toggleActive(Tenant $tenant): void
    {
        $tenant->is_active = ! $tenant->is_active;
        if ($tenant->is_active) {
            $tenant->suspended_at = null;
            $tenant->suspension_reason = null;
        } else {
            $tenant->suspended_at = now();
        }
        $tenant->save();

        Notification::make()
            ->success()
            ->title($tenant->is_active ? 'Tenant activated' : 'Tenant suspended')
            ->send();
    }

    public static function getViewFormSchema(?Tenant $record = null): array
    {
        return [
            Forms\Components\TextInput::make('id')
                ->label('Tenant ID'),
            Forms\Components\TextInput::make('is_active')
                ->label('Status')
                ->formatStateUsing(fn ($record) => $record->is_active ? 'Active' : 'Suspended'),
            Forms\Components\TextInput::make('tenancy_db_name')
                ->label('Database'),
            Forms\Components\TextInput::make('data.full_name')
                ->label('Name'),
            Forms\Components\TextInput::make('data.email')
                ->label('Email'),
            Forms\Components\TextInput::make('data.business_type')
                ->label('Business Type'),
            Forms\Components\KeyValue::make('domains')
                ->label('Domains')
                ->formatStateUsing(fn ($record) => $record->domains->pluck('domain', 'id')->toArray()),
            Forms\Components\Textarea::make('suspension_reason')
                ->label('Suspension Reason'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\TenantResource\Pages\ListTenants::route('/'),
            'view' => \App\Filament\Admin\Resources\TenantResource\Pages\ViewTenant::route('/{record}'),
        ];
    }
}
