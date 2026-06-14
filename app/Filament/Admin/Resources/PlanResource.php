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
use Filament\Forms\Components\Section;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $pluralLabel = 'Plans';

    protected static ?string $slug = 'plans';

    private static function featureGroups(): array
    {
        $all = config('plans.features', []);

        return [
            'Point of Sale' => array_intersect_key($all, array_flip(['pos', 'report', 'total_revenue', 'payment_shortcut'])),
            'Products' => array_intersect_key($all, array_flip(['product_barcode', 'product_sku', 'product_type', 'product_expired', 'product_initial_price', 'product_import', 'selling_tax'])),
            'Inventory' => array_intersect_key($all, array_flip(['stock_management', 'stock_opname'])),
            'Transactions' => array_intersect_key($all, array_flip(['print_selling_a5', 'print_product_label', 'voucher'])),
            'Customers' => array_intersect_key($all, array_flip(['member_management'])),
            'Purchasing' => array_intersect_key($all, array_flip(['purchasing', 'receivable', 'supplier'])),
            'Management' => array_intersect_key($all, array_flip(['user_management', 'role_permission', 'edit_profile', 'multi_store'])),
            'Integration' => array_intersect_key($all, array_flip(['api_access', 'export_csv', 'custom_print'])),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Plan name, identifier, and activation status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),
                Section::make('Pricing')
                    ->description('Set monthly and yearly prices. Leave yearly empty for monthly-only billing.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('price_monthly')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->required()
                            ->helperText('Set 0 for free plan'),
                        Forms\Components\TextInput::make('price_yearly')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->placeholder('Optional'),
                    ]),
                Section::make('Limits')
                    ->description('Maximum stores and users allowed')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('max_stores')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('Number of outlets/locations'),
                        Forms\Components\TextInput::make('max_users')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('Number of user accounts'),
                    ]),
                Section::make('Features')
                    ->description('Select which features this plan includes')
                    ->schema(collect(static::featureGroups())->map(fn ($features, $group) =>
                        Forms\Components\CheckboxList::make("features_{$group}")
                            ->label($group)
                            ->options($features)
                            ->columns(2)
                            ->statePath('features')
                    )->values()->toArray()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Active'),
                TextColumn::make('price_monthly')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->label('Monthly'),
                TextColumn::make('price_yearly')
                    ->money('IDR', locale: 'id')
                    ->label('Yearly')
                    ->placeholder('—'),
                TextColumn::make('max_stores')
                    ->label('Stores')
                    ->icon('heroicon-o-building-storefront')
                    ->alignment('center'),
                TextColumn::make('max_users')
                    ->label('Users')
                    ->icon('heroicon-o-users')
                    ->alignment('center'),
                TextColumn::make('features')
                    ->label('Features')
                    ->formatStateUsing(fn ($state) => collect(
                        is_string($state) ? json_decode($state, true) ?? [] : ($state ?? [])
                    )
                        ->map(fn ($f) => config('plans.features.'.$f, $f))
                        ->take(4)
                        ->implode(', ').((is_countable($state) ? count($state) : 0) > 4 ? ' +'.((is_countable($state) ? count($state) : 0) - 4).' more' : '')),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
