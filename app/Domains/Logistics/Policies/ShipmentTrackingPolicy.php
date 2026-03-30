<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentTrackingPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->hasPermissionTo('view_tracking') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, ShipmentTracking $tracking): Response
        {
            return $user->id === $tracking->shipment->customer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
