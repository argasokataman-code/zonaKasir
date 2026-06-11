<?php

namespace App\Filament\Admin\Resources;

use App\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;

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
                    ->label('Tenant ID')
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
                Tables\Actions\ViewAction::make()
                    ->form(fn ($record) => static::getViewFormSchema($record)),
                DeleteAction::make()
                    ->before(function (Tenant $record) {
                        $record->run(function () {
                            \App\Models\Tenants\User::query()->delete();
                        });
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getViewFormSchema(?Tenant $record = null): array
    {
        return [
            Forms\Components\TextInput::make('id')
                ->label('Tenant ID'),
            Forms\Components\TextInput::make('tenancy_db_name')
                ->label('Database'),
            Forms\Components\TextInput::make('data.full_name')
                ->label('Name'),
            Forms\Components\TextInput::make('data.email')
                ->label('Email'),
            Forms\Components\TextInput::make('data.business_type')
                ->label('Business Type'),
            Forms\Components\Textarea::make('data')
                ->label('Raw Data')
                ->formatStateUsing(fn ($record) => json_encode($record->data, JSON_PRETTY_PRINT))
                ->columnSpanFull(),
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
