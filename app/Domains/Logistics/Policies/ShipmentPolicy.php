<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->hasPermissionTo('view_shipments') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, Shipment $shipment): Response
        {
            return $user->id === $shipment->customer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_shipment') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, Shipment $shipment): Response
        {
            return $user->id === $shipment->customer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, Shipment $shipment): Response
        {
            return $user->id === $shipment->customer_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
