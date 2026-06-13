<?php

namespace App\Filament\Tenant\Resources\MemberResource\RelationManagers;

use App\Models\Tenants\Member;
use App\Models\Tenants\Setting;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VouchersRelationManager extends RelationManager
{
    protected static string $relationship = 'vouchers';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model|Member $ownerRecord, string $pageClass): bool
    {
        return Filament::auth()->user()?->can('read voucher') ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label(__('Nominal')),
                Tables\Columns\TextColumn::make('kuota')
                    ->label(__('Kuota')),
                Tables\Columns\TextColumn::make('minimal_buying')
                    ->label(__('Min. Purchase'))
                    ->money(Setting::get('currency', 'IDR')),
                Tables\Columns\TextColumn::make('expired')
                    ->label(__('Expired'))
                    ->date(),
            ]);
    }
}
