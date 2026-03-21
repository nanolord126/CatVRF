<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\Entertainer;
use Illuminate\Auth\Access\Response;

final class EntertainerPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Entertainer $entertainer): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainers')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, Entertainer $entertainer): Response
    {
        return $user->id === $entertainer->user_id || $user->hasPermissionTo('update_entertainers')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, Entertainer $entertainer): Response
    {
        return $user->hasPermissionTo('delete_entertainers')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
