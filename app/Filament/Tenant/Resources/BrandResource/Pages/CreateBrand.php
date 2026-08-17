<?php

namespace App\Filament\Tenant\Resources\BrandResource\Pages;

use App\Filament\Tenant\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function getRedirectUrl(): string
    {
        return '/member/brands';
    }
}