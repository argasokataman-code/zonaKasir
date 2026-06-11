<?php

namespace App\Filament\Admin\Resources;

use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\DeleteAction;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?string $pluralLabel = 'Coupons';

    protected static ?string $slug = 'coupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique()
                    ->maxLength(50),
                Forms\Components\Select::make('type')
                    ->options([
                        'percentage' => 'Percentage (%)',
                        'nominal' => 'Nominal (Rp)',
                        'trial_extension' => 'Trial Extension (days)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->prefix(fn ($get) => $get('type') === 'percentage' ? '%' : 'Rp'),
                Forms\Components\TextInput::make('trial_days')
                    ->numeric()
                    ->label('Extra Trial Days'),
                Forms\Components\TextInput::make('max_redemptions')
                    ->numeric()
                    ->label('Max Redemptions'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Value'),
                TextColumn::make('used_count')
                    ->label('Used / Max')
                    ->formatStateUsing(fn ($record) => $record->used_count . '/' . ($record->max_redemptions ?? '∞')),
                IconColumn::make('is_valid')
                    ->label('Valid')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isValid()),
                TextColumn::make('expires_at')
                    ->dateTime('d M Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\CouponResource\Pages\ListCoupons::route('/'),
            'create' => \App\Filament\Admin\Resources\CouponResource\Pages\CreateCoupon::route('/create'),
            'edit' => \App\Filament\Admin\Resources\CouponResource\Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
