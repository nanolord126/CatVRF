<?php declare(strict_types=1);

namespace Modules\Fashion\Policies;

use App\Models\User;
use App\Domains\Fashion\Models\FashionProduct;

final class FashionProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, FashionProduct $product): bool
    {
        return true;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->can('create_fashion_product');
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, FashionProduct $product): bool
    {
        return $user->id === $product->store->user_id || $user->can('update_any_fashion_product');
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, FashionProduct $product): bool
    {
        return $user->id === $product->store->user_id || $user->can('delete_any_fashion_product');
    }

    /**
     * Determine if the user can update product stock.
     */
    public function updateStock(User $user, FashionProduct $product): bool
    {
        return $user->id === $product->store->user_id || $user->can('update_any_fashion_product');
    }
}
