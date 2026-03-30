<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierServicePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
