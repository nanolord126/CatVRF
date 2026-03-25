declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Shop\Policies;

use App\Models\User;
use App\Domains\Shop\Models\ShopProduct;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Канон 2026: Изоляция доступа по tenant_id (Section 2: Shop)
 */
final class ShopProductPolicy
{
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
