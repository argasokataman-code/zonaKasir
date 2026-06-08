<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\Product;
use App\Models\Tenants\Setting;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

trait TableProduct
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Product filtering logic:
                // • Hide products when type is 'product' AND stock is 0 (unless is_non_stock = true)
                // • Show products when type is 'service' (regardless of stock)  
                // • Show products when type is 'product' AND stock > 0 OR is_non_stock = true
                Product::query()
                    ->where(function ($query) {
                        $query->where('type', 'product')
                            ->where(function ($query) {
                                $query->whereHas('stocks', function ($query) {
                                    $query->where('is_ready', 1)
                                        ->where('type', 'in')
                                        ->where('stock', '>', 0);
                                })
                                ->orWhere('is_non_stock', true);
                            })
                        ->orWhere('type', 'service');
                    })
                    ->where('show', true)
                    // ->orWhere('type', 'service')
            )
            ->defaultPaginationPageOption(12)
            ->paginationPageOptions([12])
            ->columns([
                Stack::make([
                    ImageColumn::make('hero_image')
                        ->getStateUsing(function (Product $record): ?string {
                            $images = $record->hero_images;

                            if (is_array($images) && count($images) > 0 && filled($images[0])) {
                                return $images[0];
                            }

                            return null;
                        })
                        ->defaultImageUrl('https://cdn4.iconfinder.com/data/icons/picture-sharing-sites/32/No_Image-1024.png')
                        ->translateLabel()
                        ->alignCenter()
                        ->extraAttributes([
                            'class' => 'py-0',
                        ])
                        ->extraImgAttributes([
                            'class' => 'mb-4 object-cover -mt-4 xl:w-[200px] md:w-[180px] w-[150px]',
                        ])
                        ->height(100),
                    TextColumn::make('selling_price')
                        ->color('primary')
                        ->money(Setting::get('currency', 'IDR'))
                        ->columnStart(0),
                    TextColumn::make('name')
                        ->size('lg')
                        ->searchable(query: function ($query, string $search): void {
                            $query->where('sku', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhereHas('barcodes', function ($query) use ($search) {
                                    $query->where('code', 'like', "%{$search}%")
                                        ->where('is_active', true);
                                });
                        })
                        ->extraAttributes([
                            'class' => 'font-bold',
                        ]),
                    TextColumn::make('stock')
                        ->hidden(function (Product $product) {
                            return $product->is_non_stock;
                        })
                        ->icon(function (Product $product) {
                            if ($product->is_non_stock) {
                                return '';
                            }

                            return $product->stock < Setting::get('minimum_stock_nofication', 10)
                                    ? 'heroicon-s-information-circle'
                                : '';
                        })
                        ->iconColor('danger')
                        ->extraAttributes([
                            'class' => 'font-bold',
                        ])
                        ->formatStateUsing(fn (Product $product) => __('Stock').': '.$product->stock_calculate),
                ]),
            ])
            ->contentGrid([
                'md' => 3,
                'xl' => 4,
            ])
            ->headerActionsPosition(HeaderActionsPosition::Bottom)
            ->searchPlaceholder(__('Search (SKU, name, barcode)'))
            ->actions([
                Action::make('insert_amount')
                    ->translateLabel()
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->form([
                        TextInput::make('amount')
                            ->translateLabel()
                            ->extraAttributes([
                                'focus',
                            ])
                            ->rules([
                                function (Product $product) {
                                    return function (string $attribute, $value, Closure $fail) use ($product) {
                                        if (! $this->validateStock($product, $value)) {
                                            $fail('Stock is out');
                                        }
                                    };
                                },
                            ])
                            ->default(1),
                    ])
                    ->extraAttributes([
                        'class' => 'mr-auto',
                    ])
                    ->action(fn (Product $product, array $data) => $this->addCart($product, $data))
                    ->hiddenLabel(),
                Action::make('cart')
                    ->label(function (Product $product) {
                        return $product->CartItems()->first()?->qty ?? '';
                    })
                    ->color('white')
                    ->icon('heroicon-o-shopping-bag')
                    ->hidden(fn (Product $product) => ! $product->CartItems()->exists()),
            ]);
    }
}
