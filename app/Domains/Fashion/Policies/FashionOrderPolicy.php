<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FashionOrderPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('view_orders') ? Response::allow() : Response::deny();
    }

    public function view(User $user, FashionOrder $order): Response
    {
        return $user->id === $order->customer_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('create_order') ? Response::allow() : Response::deny();
    }

    public function update(User $user, FashionOrder $order): Response
    {
        return $user->id === $order->customer_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FashionOrder $order): Response
    {
        return $user->isAdmin() ? Response::allow() : Response::deny();
    }
}
