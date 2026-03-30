<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Shop\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShopProductPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HandlesAuthorization;

        public function view(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id;
        }

        public function update(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole(['business_owner', 'manager']);
        }

        public function delete(User $user, ShopProduct $product): bool
        {
            return $user->tenant_id === $product->tenant_id && $user->hasRole('business_owner');
        }
}
