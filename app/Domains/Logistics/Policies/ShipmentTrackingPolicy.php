<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\ShipmentTracking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ShipmentTrackingPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_tracking') ? Response::allow() : Response::deny();
    }

    public function view(User $user, ShipmentTracking $tracking): Response
    {
        return $user->id === $tracking->shipment->customer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
