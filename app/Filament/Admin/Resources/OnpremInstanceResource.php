<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OnpremInstanceResource\Pages;
use App\Models\OnpremInstance;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class OnpremInstanceResource extends Resource
{
    protected static ?string $model = OnpremInstance::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'On-Prem Instances';

    protected static ?string $pluralLabel = 'On-Prem Instances';

    protected static ?string $slug = 'onprem-instances';

    public static function getNavigationBadge(): ?string
    {
        $offline = OnpremInstance::where('last_seen_at', '<', now()->subDays(7))->count();

        return $offline > 0 ? (string) $offline : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('license_key')
                    ->label('License Key')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('app_version')
                    ->label('Version')
                    ->toggleable(),
                TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (OnpremInstance $record) => match ($record->status()) {
                        'online' => 'success',
                        'stale' => 'warning',
                        'offline' => 'danger',
                        'missing' => 'gray',
                    })
                    ->formatStateUsing(fn (OnpremInstance $record) => match ($record->status()) {
                        'online' => 'Online',
                        'stale' => 'Ragu',
                        'offline' => 'Hilang',
                        'missing' => 'Belum pernah',
                    }),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'online' => 'Online (≤2 hari)',
                        'stale' => 'Ragu (2-7 hari)',
                        'offline' => 'Hilang (>7 hari)',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value']) {
                        'online' => $query->where('last_seen_at', '>=', now()->subDays(2)),
                        'stale' => $query->whereBetween('last_seen_at', [now()->subDays(7), now()->subDays(2)]),
                        'offline' => $query->where('last_seen_at', '<', now()->subDays(7))->orWhereNull('last_seen_at'),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOnpremInstances::route('/'),
        ];
    }
}
