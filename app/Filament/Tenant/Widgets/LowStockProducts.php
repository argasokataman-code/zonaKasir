<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProducts extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Products';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $products = Product::with('stocks')
            ->get()
            ->filter(function (Product $product) {
                $stockIn = $product->stocks->where('type', 'in')->sum('stock');
                $stockOut = $product->stocks->where('type', 'out')->sum('stock');
                $remaining = $stockIn - $stockOut;

                return $remaining > 0 && $remaining <= 5;
            })
            ->sortBy(function (Product $product) {
                $stockIn = $product->stocks->where('type', 'in')->sum('stock');
                $stockOut = $product->stocks->where('type', 'out')->sum('stock');
                return $stockIn - $stockOut;
            })
            ->take(10)
            ->values();

        return $table
            ->query(
                Product::query()
                    ->whereIn('id', $products->pluck('id')->toArray())
            )
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('remaining_stock')
                    ->label(__('Remaining Stock'))
                    ->getStateUsing(function (Product $product) {
                        $stockIn = $product->stocks->where('type', 'in')->sum('stock');
                        $stockOut = $product->stocks->where('type', 'out')->sum('stock');
                        return $stockIn - $stockOut;
                    })
                    ->color(fn ($state) => $state <= 2 ? 'danger' : 'warning')
                    ->weight('bold'),
            ])
            ->paginated(false)
            ->recordUrl(function (Product $product) {
                return '/member/products/' . $product->getKey() . '/edit';
            });
    }
}
