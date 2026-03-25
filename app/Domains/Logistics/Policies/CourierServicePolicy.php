declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\CourierService;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * CourierServicePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourierServicePolicy
{
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, CourierService $courierService): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_courier_service') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, CourierService $courierService): Response
    {
        return $user->id === $courierService->user_id || $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, CourierService $courierService): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
