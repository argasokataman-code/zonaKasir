<?php

namespace App\Policies\Tenants;

use App\Models\Tenants\User;
use App\Models\Tenants\Withdrawal;
use Illuminate\Auth\Access\HandlesAuthorization;

class WithdrawalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('read withdrawal');
    }

    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->can('read withdrawal');
    }

    public function create(User $user): bool
    {
        return $user->can('create withdrawal');
    }

    public function update(User $user, Withdrawal $withdrawal): bool
    {
        return $user->can('update withdrawal');
    }

    public function approve(User $user, Withdrawal $withdrawal): bool
    {
        return $user->can('update withdrawal') && $withdrawal->status === 'pending';
    }

    public function reject(User $user, Withdrawal $withdrawal): bool
    {
        return $user->can('update withdrawal') && $withdrawal->status === 'pending';
    }

    public function delete(User $user, Withdrawal $withdrawal): bool
    {
        return $user->can('delete withdrawal');
    }
}
