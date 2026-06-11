<?php

namespace App\Filament\Admin\Resources\TenantResource\Pages;

use App\Filament\Admin\Resources\TenantResource;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;
}
