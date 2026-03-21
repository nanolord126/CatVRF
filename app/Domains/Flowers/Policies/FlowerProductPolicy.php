<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\FlowerProduct;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FlowerProductPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, FlowerProduct $product): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        if ($user->isBusiness()) {
            return Response::allow();
        }

        return Response::deny('Only business users can create products');
    }

    public function update(User $user, FlowerProduct $product): Response
    {
        if ($user->id === $product->shop->user_id && $user->isBusiness()) {
            return Response::allow();
        }

        return Response::deny('You cannot update this product');
    }

    public function delete(User $user, FlowerProduct $product): Response
    {
        if ($user->id === $product->shop->user_id && $user->isBusiness()) {
            return Response::allow();
        }

        return Response::deny('You cannot delete this product');
    }
}
