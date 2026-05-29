<?php

namespace App\Filament\Tenant\Resources\MemberResource\Pages;

use App\Filament\Tenant\Resources\MemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return can('create member');
    }

    protected function getRedirectUrl(): string
    {
        return '/member/members';
    }
}
