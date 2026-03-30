<?php declare(strict_types=1);

namespace Modules\Beauty\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): bool
        {
            return true;
        }
    
        public function view(User $user, Salon $salon): bool
        {
            return $user->tenant_id === $salon->tenant_id;
        }
    
        public function create(User $user): bool
        {
            return true;
        }
    
        public function update(User $user, Salon $salon): bool
        {
            return $user->id === $salon->owner_id && $user->tenant_id === $salon->tenant_id;
        }
    
        public function delete(User $user, Salon $salon): bool
        {
            return $user->id === $salon->owner_id && $user->tenant_id === $salon->tenant_id;
        }
}
