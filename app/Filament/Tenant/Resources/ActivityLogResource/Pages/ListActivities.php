<?php

namespace App\Filament\Tenant\Resources\ActivityLogResource\Pages;

use App\Filament\Tenant\Resources\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
