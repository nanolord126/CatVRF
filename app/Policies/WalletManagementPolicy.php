<?php declare(strict_types=1);

namespace App\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletManagementPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
