<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Trainer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class TrainerPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Trainer $trainer): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_trainers') ? Response::allow() : Response::deny();
    }

    public function update(User $user, Trainer $trainer): Response
    {
        return ($user->id === $trainer->user_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Trainer $trainer): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
