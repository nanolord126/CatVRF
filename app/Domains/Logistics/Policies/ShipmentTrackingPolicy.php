<?php

declare(strict_types=1);


namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\ShipmentTracking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * ShipmentTrackingPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentTrackingPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_tracking') ? $this->response->allow() : $this->response->deny();
    }

    public function view(User $user, ShipmentTracking $tracking): Response
    {
        return $user->id === $tracking->shipment->customer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
