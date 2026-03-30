<?php declare(strict_types=1);

namespace App\Policies\Logistics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return $user->can('view_logistics');
        }

        public function view(User $user, Courier $courier): bool
        {
            return $courier->tenant_id === $user->tenant_id;
        }

        public function create(User $user): bool
        {
            return $user->can('manage_logistics');
        }

        public function update(User $user, Courier $courier): bool
        {
            return $courier->tenant_id === $user->tenant_id && $user->can('manage_logistics');
        }

        public function delete(User $user, Courier $courier): bool
        {
            return $courier->tenant_id === $user->tenant_id && $user->can('manage_logistics');
        }
}
