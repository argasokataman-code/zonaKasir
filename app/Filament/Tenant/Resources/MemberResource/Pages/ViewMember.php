<?php

namespace App\Filament\Tenant\Resources\MemberResource\Pages;

use App\Filament\Tenant\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return can('read member');
    }
}
