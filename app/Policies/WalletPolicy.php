<?php

namespace App\Policies;

use App\Models\Wallet;
use App\Models\User;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id === tenant()->id;
    }

    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id || $user->hasPermissionTo('view_all_wallets');
    }

    public function withdraw(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id && $user->hasPermissionTo('withdraw');
    }

    public function deposit(User $user, Wallet $wallet): bool
    {
        return $user->hasPermissionTo('deposit');
    }
}
