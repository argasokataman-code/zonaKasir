<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\SettlementResource\Pages;
use App\Models\Tenants\Settlement;
use App\Traits\HasTranslatableResource;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettlementResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = Settlement::class;

    protected static ?string $label = 'Settlement';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Settlements';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_start', 'desc')
            ->columns([
                TextColumn::make('period_start')
                    ->label('Periode')
                    ->date('d M Y'),
                TextColumn::make('total_gross')
                    ->label('Total Gross')
                    ->money('IDR'),
                TextColumn::make('total_fee_midtrans')
                    ->label('Fee Midtrans')
                    ->money('IDR'),
                TextColumn::make('total_fee_platform')
                    ->label('Fee Platform')
                    ->money('IDR'),
                TextColumn::make('total_net')
                    ->label('Net Amount')
                    ->money('IDR')
                    ->color('success'),
                TextColumn::make('transaction_count')
                    ->label('Transaksi'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'approved',
                        'success' => 'disbursed',
                        'danger' => 'failed',
                    ]),
            ])
            ->defaultSort('period_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
        ];
    }
}
