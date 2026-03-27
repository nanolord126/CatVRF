<?php

declare(strict_types=1);


namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalTestOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * MedicalTestOrderPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalTestOrderPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_test_orders') ? $this->response->allow() : $this->response->deny();
    }

    public function view(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->id === $testOrder->patient_id || $user->hasRole('admin')
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_test_order') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, MedicalTestOrder $testOrder): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
