<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FlowerOrderPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, FlowerOrder $order): Response
    {
        if ($user->id === $order->user_id || $user->id === $order->shop->user_id) {
            return Response::allow();
        }

        return Response::deny('You cannot view this order');
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, FlowerOrder $order): Response
    {
        if ($user->id === $order->shop->user_id && in_array($order->status, ['pending', 'confirmed'])) {
            return Response::allow();
        }

        return Response::deny('You cannot update this order');
    }

    public function delete(User $user, FlowerOrder $order): Response
    {
        if ($user->id === $order->shop->user_id && $order->status === 'pending') {
            return Response::allow();
        }

        return Response::deny('You cannot delete this order');
    }
}
