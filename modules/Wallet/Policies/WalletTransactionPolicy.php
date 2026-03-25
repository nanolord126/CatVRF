declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Modules\Wallet\Policies;

use App\Modules\Wallet\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy для WalletTransaction.
 * Production 2026.
 */
final class WalletTransactionPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, WalletTransaction $transaction): bool
    {
        if (!$user) {
            return false;
        }

        return $user->id === $transaction->user_id 
            && $user->tenant_id === $transaction->tenant_id;
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function delete(?User $user, WalletTransaction $transaction): bool
    {
        return false; // Транзакции нельзя удалять
    }

    public function restore(?User $user, WalletTransaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(?User $user, WalletTransaction $transaction): bool
    {
        return false;
    }
}
