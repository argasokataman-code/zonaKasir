<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenants\SellingDetail;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TodaysBestSellingProduct extends BaseWidget
{
    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->product_id;
    }

    public function table(Table $table): Table
    {
        $startDate = today()->startOfDay();
        $endDate = today()->endOfDay();

        $bestSellingProduct = SellingDetail::query()
            ->select(
                'product_id',
                DB::raw('SUM(qty) as total_qty')
            )
            ->whereHas('selling', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->limit(5)
            ->groupBy('product_id')
            ->orderBy('total_qty', 'desc')
            ->with('product:id,name');

        return $table
            ->query(
                $bestSellingProduct
            )
            ->columns([
                TextColumn::make('product.name')
                    ->translateLabel(),
                TextColumn::make('total_qty')
                    ->translateLabel(),
            ])
            ->paginated(false);
    }
}
