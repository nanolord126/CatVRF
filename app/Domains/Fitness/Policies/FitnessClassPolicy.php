<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\FitnessClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class FitnessClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, FitnessClass $class): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_classes') ? Response::allow() : Response::deny();
    }

    public function update(User $user, FitnessClass $class): Response
    {
        return $user->hasPermissionTo('update_classes') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FitnessClass $class): Response
    {
        return $user->hasPermissionTo('delete_classes') ? Response::allow() : Response::deny();
    }
}
