<?php

namespace App\Filament\Tenant\Resources\MemberResource\Pages;

use App\Filament\Tenant\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return can('read member');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return '/member/members';
    }
}
