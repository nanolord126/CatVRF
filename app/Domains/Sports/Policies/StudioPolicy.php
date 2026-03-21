<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Studio;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class StudioPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Studio $studio): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_studios') ? Response::allow() : Response::deny();
    }

    public function update(User $user, Studio $studio): Response
    {
        return ($user->id === $studio->owner_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Studio $studio): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
