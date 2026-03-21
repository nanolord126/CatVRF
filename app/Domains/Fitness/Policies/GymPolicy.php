<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\Gym;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class GymPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Gym $gym): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_gyms') ? Response::allow() : Response::deny();
    }

    public function update(User $user, Gym $gym): Response
    {
        return $user->hasPermissionTo('update_gyms') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Gym $gym): Response
    {
        return $user->hasPermissionTo('delete_gyms') ? Response::allow() : Response::deny();
    }
}
