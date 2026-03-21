<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\Trainer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class TrainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Trainer $trainer): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_trainers') ? Response::allow() : Response::deny();
    }

    public function update(User $user, Trainer $trainer): Response
    {
        return $user->id === $trainer->user_id || $user->hasPermissionTo('update_trainers') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Trainer $trainer): Response
    {
        return $user->hasPermissionTo('delete_trainers') ? Response::allow() : Response::deny();
    }
}
