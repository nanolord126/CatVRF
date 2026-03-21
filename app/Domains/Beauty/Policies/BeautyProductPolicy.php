<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\BeautyProduct;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика для товаров красоты.
 * Production 2026.
 */
final class BeautyProductPolicy
{
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
