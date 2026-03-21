<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionProduct;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FashionProductPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, FashionProduct $product): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('create_product') ? Response::allow() : Response::deny();
    }

    public function update(User $user, FashionProduct $product): Response
    {
        return $user->id === $product->store->owner_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FashionProduct $product): Response
    {
        return $user->isAdmin() ? Response::allow() : Response::deny();
    }
}
