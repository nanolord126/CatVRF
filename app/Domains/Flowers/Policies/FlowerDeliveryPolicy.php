<?php declare(strict_types=1);

namespace App\Domains\Flowers\Policies;

use App\Domains\Flowers\Models\FlowerDelivery;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FlowerDeliveryPolicy
{
    public function view(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->order->user_id || $user->id === $delivery->shop->user_id) {
            return Response::allow();
        }

        return Response::deny('You cannot view this delivery');
    }

    public function track(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->order->user_id) {
            return Response::allow();
        }

        return Response::deny('You cannot track this delivery');
    }

    public function update(User $user, FlowerDelivery $delivery): Response
    {
        if ($user->id === $delivery->shop->user_id && in_array($delivery->status, ['assigned', 'in_transit'])) {
            return Response::allow();
        }

        return Response::deny('You cannot update this delivery');
    }
}
