<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\Contractor;
use Illuminate\Auth\Access\Response;

final class ContractorPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Contractor $contractor): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_contractors') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function update(User $user, Contractor $contractor): Response
    {
        return $user->id === $contractor->user_id || $user->hasPermissionTo('update_contractors') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function delete(User $user, Contractor $contractor): Response
    {
        return $user->hasPermissionTo('delete_contractors') ? Response::allow() : Response::deny('Unauthorized');
    }
}
