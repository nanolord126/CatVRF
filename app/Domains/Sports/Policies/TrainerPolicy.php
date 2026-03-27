<?php

declare(strict_types=1);


namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Trainer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * TrainerPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TrainerPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, Trainer $trainer): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_trainers') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, Trainer $trainer): Response
    {
        return ($user->id === $trainer->user_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Trainer $trainer): Response
    {
        return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
    }
}
