<?php

namespace App\Filament\Tenant\Resources\TableResource\Pages;

use App\Filament\Tenant\Resources\TableResource;
use App\Models\Tenants\About;
use App\Models\Tenants\Table;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListTables extends ListRecords
{
    protected static string $resource = TableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('QR Menu')
                ->icon('heroicon-s-qr-code')
                ->modalHeading(__('QR Menu Digital'))
                ->modalSubmitAction(false)
                ->modalContent(view('filament.components.qr-menu', [
                    'about' => About::select('menu_token')->firstOrFail(),
                    'tables' => Table::select('number')->orderBy('number')->get(),
                ])),
            Actions\CreateAction::make(),
        ];
    }
}
