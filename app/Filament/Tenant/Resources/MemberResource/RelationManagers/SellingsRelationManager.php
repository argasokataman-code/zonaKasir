<?php

namespace App\Filament\Tenant\Resources\MemberResource\RelationManagers;

use App\Models\Tenants\Member;
use App\Models\Tenants\Setting;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SellingsRelationManager extends RelationManager
{
    protected static string $relationship = 'sellings';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model|Member $ownerRecord, string $pageClass): bool
    {
        return Filament::auth()->user()?->can('read selling') ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $currency = Setting::get('currency', 'IDR');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Invoice'))
                    ->prefix('#')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total_price')
                    ->label(__('Total'))
                    ->money($currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label(__('Items'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label(__('Payment'))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('cashDrawer.opened_by')
                    ->label(__('Cashier'))
                    ->placeholder('-'),
            ]);
    }
}
