<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ShipmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_shipments') ? Response::allow() : Response::deny();
    }

    public function view(User $user, Shipment $shipment): Response
    {
        return $user->id === $shipment->customer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_shipment') ? Response::allow() : Response::deny();
    }

    public function update(User $user, Shipment $shipment): Response
    {
        return $user->id === $shipment->customer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Shipment $shipment): Response
    {
        return $user->id === $shipment->customer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
