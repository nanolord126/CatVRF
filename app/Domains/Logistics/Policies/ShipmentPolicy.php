declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\Shipment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * ShipmentPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ShipmentPolicy
{
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
