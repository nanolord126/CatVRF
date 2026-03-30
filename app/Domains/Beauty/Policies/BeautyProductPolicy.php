<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyProductPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return (bool) filament()->getTenant();
        }

        public function view(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant('id');
        }

        public function create(User $user): bool
        {
            return (bool) filament()->getTenant() && $user->can('create_products');
        }

        public function update(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant('id') && $user->can('update_products');
        }

        public function delete(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant('id') && $user->can('delete_products');
        }

        public function restore(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant('id') && $user->can('restore_products');
        }

        public function forceDelete(User $user, BeautyProduct $product): bool
        {
            return $product->tenant_id === tenant('id') && $user->can('force_delete_products');
        }
}
