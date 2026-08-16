<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\TableResource\Pages;
use App\Models\Tenants\Table as TableModel;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TableResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = TableModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('number')
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                    ->translateLabel(),
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(0)
                    ->translateLabel(),
                ToggleButtons::make('zone')
                    ->options([
                        'indoor' => __('Indoor'),
                        'outdoor' => __('Outdoor'),
                        'vip' => __('VIP'),
                    ])
                    ->default('indoor')
                    ->translateLabel(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->translateLabel(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->translateLabel(),
                TextColumn::make('zone')
                    ->translateLabel(),
                TextColumn::make('capacity')
                    ->translateLabel(),
                TextColumn::make('sort_order')
                    ->translateLabel(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(function (TableModel $record) {
                        // FR-2.6: meja dgn bill aktif tidak bisa dihapus
                        if ($record->activeSelling()) {
                            throw new \RuntimeException('Cannot delete a table with an active bill');
                        }
                        $record->delete();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTables::route('/'),
            'create' => Pages\CreateTable::route('/create'),
            'edit' => Pages\EditTable::route('/{record}/edit'),
        ];
    }
}
