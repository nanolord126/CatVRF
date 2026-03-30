<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalonPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return (bool) filament()->getTenant();
        }

        public function view(User $user, BeautySalon $salon): bool
        {
            return $salon->tenant_id === tenant('id');
        }

        public function create(User $user): bool
        {
            return (bool) filament()->getTenant() && $user->can('create_salons');
        }

        public function update(User $user, BeautySalon $salon): bool
        {
            return $salon->tenant_id === tenant('id') && $user->can('update_salons');
        }

        public function delete(User $user, BeautySalon $salon): bool
        {
            return $salon->tenant_id === tenant('id') && $user->can('delete_salons');
        }

        public function restore(User $user, BeautySalon $salon): bool
        {
            return $salon->tenant_id === tenant('id') && $user->can('restore_salons');
        }

        public function forceDelete(User $user, BeautySalon $salon): bool
        {
            return $salon->tenant_id === tenant('id') && $user->can('force_delete_salons');
        }
}
