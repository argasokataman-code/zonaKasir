<?php

namespace App\Filament\Tenant\Resources\ProductResource\Pages;

use App\Features\ProductImport;
use App\Filament\Tenant\Resources\ProductResource;
use App\Imports\ProductImport as ImportsProductImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('download-template')
                ->label(__('Download Template'))
                ->color('gray')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(feature(ProductImport::class))
                ->action(function () {
                    $headers = ['category', 'name', 'sku', 'unit', 'stock', 'initial_price', 'selling_price', 'type', 'barcode', 'other_price'];
                    $sample = ['Makanan', 'Nasi Goreng Spesial', 'NG-001', 'PCS', '50', '8000', '12000', 'product', '8901234560001', ''];

                    $callback = function () use ($headers, $sample) {
                        $handle = fopen('php://output', 'w');
                        fputcsv($handle, $headers);
                        fputcsv($handle, $sample);
                        fclose($handle);
                    };

                    return response()->stream($callback, 200, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
                    ]);
                }),
            Action::make('import-product')
                ->label(__('Import product'))
                ->color('gray')
                ->visible(feature(ProductImport::class))
                ->form([
                    FileUpload::make('attachment')
                        ->disk(config('filesystems.upload_disk'))
                        ->placeholder(__('Tarik dan lepas file di sini atau klik untuk mencari file'))
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'text/csv'])
                        ->maxSize(config('upload.livewire_max_size')),
                ])->action(function (array $data) {
                    $uploadDisk = config('filesystems.upload_disk');
                    $filePath = $data['attachment'];
                    $driver = config('filesystems.disks.' . $uploadDisk . '.driver');

                    if ($driver === 'local' || $driver === 'public') {
                        $fullPath = Storage::disk($uploadDisk)->path($filePath);
                        Excel::import(new ImportsProductImport, $fullPath);
                    } else {
                        $tmpPath = tempnam(sys_get_temp_dir(), 'lakasir_import_');
                        try {
                            file_put_contents($tmpPath, Storage::disk($uploadDisk)->get($filePath));
                            Excel::import(new ImportsProductImport, $tmpPath);
                        } finally {
                            @unlink($tmpPath);
                        }
                    }

                    Notification::make()
                        ->title(__('Import completed'))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/member/products';
    }
}