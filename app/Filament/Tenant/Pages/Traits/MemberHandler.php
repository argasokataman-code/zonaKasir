<?php

namespace App\Filament\Tenant\Pages\Traits;

trait MemberHandler
{
    protected function fillMember(): void
    {
        $member = $this->members->filter(function (string $value, int $key) {
            return $key == $this->cartDetail['member_id'];
        })->first();
        $this->cartDetail['member_label'] = $member;
    }
}