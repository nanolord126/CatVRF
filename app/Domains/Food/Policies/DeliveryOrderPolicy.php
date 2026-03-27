<?php

declare(strict_types=1);


namespace App\Domains\Food\Policies;

use App\Models\User;
use App\Domains\Food\Models\DeliveryOrder;
use Illuminate\Auth\Access\Response;

/**
 * Policy для DeliveryOrder.
 * Production 2026.
 */
final class DeliveryOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeliveryOrder $delivery): bool
    {
        return $user->id === $delivery->order->client_id || $user->isAdmin();
    }

    public function track(User $user, DeliveryOrder $delivery): Response
    {
        if ($user->id !== $delivery->order->client_id && $user->id !== $delivery->courier_id && !$user->isAdmin()) {
            return $this->response->deny('Вы не можете отслеживать эту доставку');
        }

        return $this->response->allow();
    }
}
