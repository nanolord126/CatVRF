<?php declare(strict_types=1);

namespace Modules\Wallet\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Wallet\Models\WalletTransaction;

final class WalletTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WalletTransaction $transaction): bool
    {
        // A user can view a transaction if it belongs to their wallet and tenant.
        return $user->id === $transaction->wallet->user_id
            && $user->tenant_id === $transaction->wallet->tenant_id;
    }

    public function create(User $user): bool
    {
        // Creation is handled by services, not direct user action via policy.
        return false;
    }

    public function update(User $user, WalletTransaction $transaction): bool
    {
        // Transactions are immutable.
        return false;
    }

    public function delete(User $user, WalletTransaction $transaction): bool
    {
        // Transactions are immutable.
        return false;
    }

    public function restore(User $user, WalletTransaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, WalletTransaction $transaction): bool
    {
        return false;
    }
}

