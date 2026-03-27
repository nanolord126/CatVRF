<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Policies;

use App\Domains\Sports\Fitness\Models\Gym;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final /**
 * GymPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GymPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Gym $gym): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_gyms') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, Gym $gym): Response
    {
        return $user->hasPermissionTo('update_gyms') ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Gym $gym): Response
    {
        return $user->hasPermissionTo('delete_gyms') ? $this->response->allow() : $this->response->deny();
    }
}
