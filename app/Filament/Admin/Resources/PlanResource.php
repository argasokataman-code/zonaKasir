<?php

namespace App\Filament\Admin\Resources;

use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\DeleteAction;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $pluralLabel = 'Plans';

    protected static ?string $slug = 'plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('price_monthly')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('price_yearly')
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('max_stores')
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('max_users')
                    ->numeric()
                    ->default(1),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\TagsInput::make('features')
                    ->label('Features (one per tag)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('price_monthly')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('price_yearly')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('max_stores')
                    ->label('Stores'),
                TextColumn::make('max_users')
                    ->label('Users'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('price_monthly')
            ->actions([
                Tables\Actions\EditAction::make(),
                DeleteAction::make(),
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
            'index' => \App\Filament\Admin\Resources\PlanResource\Pages\ListPlans::route('/'),
            'create' => \App\Filament\Admin\Resources\PlanResource\Pages\CreatePlan::route('/create'),
            'edit' => \App\Filament\Admin\Resources\PlanResource\Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
