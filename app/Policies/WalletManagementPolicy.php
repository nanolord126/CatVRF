<?php

declare(strict_types=1);


namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

/**
 * WalletManagementPolicy — управление кошельком и вывод средств.
 * Только владелец кошелька может видеть баланс и выводить средства.
 */
final class WalletManagementPolicy
{
    use HandlesAuthorization;

    public function view(User $user, $wallet): bool
    {
        return $user->tenant_id === ($wallet->tenant_id ?? null);
    }

    public function viewBalance(User $user, $wallet): bool
    {
        return $user->tenant_id === ($wallet->tenant_id ?? null);
    }

    public function withdraw(User $user, $wallet): bool
    {
        return $user->tenant_id === ($wallet->tenant_id ?? null)
            && ($user->isBusinessOwner() || $user->hasAbility('finance'));
    }
}
