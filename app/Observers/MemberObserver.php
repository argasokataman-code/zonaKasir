<?php

namespace App\Observers;

use App\Models\Tenants\Member;
use App\Models\Tenants\User;
use App\Notifications\MemberRegistered;

class MemberObserver
{
    public function creating(Member $member)
    {
        $lastMember = Member::select('code')->orderBy('code', 'desc')->first();
        $lastCount = $lastMember ? (int) substr($lastMember->code, 3) : 0;

        if (! $member->code) {
            // Generate the new customer code
            $member->code = 'CUS'.str_pad($lastCount + 1, 4, '0', STR_PAD_LEFT);
        }
    }

    public function created(Member $member): void
    {
        User::select('id')->each(function ($user) use ($member) {
            $user->notify(new MemberRegistered($member));
        });
    }
}
