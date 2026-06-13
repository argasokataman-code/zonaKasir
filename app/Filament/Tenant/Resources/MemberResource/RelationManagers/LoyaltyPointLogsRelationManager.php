<?php

namespace App\Filament\Tenant\Resources\MemberResource\RelationManagers;

use App\Models\Tenants\Member;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPointLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'loyaltyPointLogs';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model|Member $ownerRecord, string $pageClass): bool
    {
        return Filament::auth()->user()?->can('read member') ?? false;
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
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'earn' => 'success',
                        'redeem' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->label(__('Points'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label(__('Balance After'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label(__('Note'))
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->date()
                    ->sortable(),
            ]);
    }
}
