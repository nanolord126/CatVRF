<?php declare(strict_types=1);

namespace Modules\Finances\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinancePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;
    
        public function viewAny(User $user): bool
        {
            return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'accountant']) &&
                   $user->tenant_id !== null;
        }
    
        public function view(User $user, PaymentTransaction $transaction): bool
        {
            if ($user->tenant_id !== $transaction->tenant_id) {
                return false;
            }
    
            return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'accountant']) ||
                   $transaction->user_id === $user->id;
        }
    
        public function create(User $user): bool
        {
            return $user->tenant_id !== null &&
                   $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'accountant']);
        }
    
        public function update(User $user, PaymentTransaction $transaction): bool
        {
            if ($user->tenant_id !== $transaction->tenant_id) {
                return false;
            }
    
            return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']) &&
                   in_array($transaction->status, ['pending', 'processing']);
        }
    
        public function delete(User $user, PaymentTransaction $transaction): bool
        {
            if ($user->tenant_id !== $transaction->tenant_id) {
                return false;
            }
    
            return $user->hasAnyRole(['admin', 'tenant-owner']) &&
                   $transaction->status === 'pending';
        }
    
        public function restore(User $user, PaymentTransaction $transaction): bool
        {
            if ($user->tenant_id !== $transaction->tenant_id) {
                return false;
            }
    
            return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
        }
    
        public function forceDelete(User $user, PaymentTransaction $transaction): bool
        {
            if ($user->tenant_id !== $transaction->tenant_id) {
                return false;
            }
    
            return $user->hasRole('admin');
        }
}
