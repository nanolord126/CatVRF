<?php declare(strict_types=1);

namespace Modules\Wallet\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WalletTransactionPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
