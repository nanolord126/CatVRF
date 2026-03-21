<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceDispute;
use Illuminate\Auth\Access\Response;

final class ServiceDisputePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function view(User $user, ServiceDispute $dispute): Response
    {
        return $user->id === $dispute->initiator_id || $user->id === $dispute->job->contractor->user_id || $user->hasPermissionTo('view_disputes') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function resolve(User $user, ServiceDispute $dispute): Response
    {
        return $user->hasPermissionTo('resolve_disputes') ? Response::allow() : Response::deny('Unauthorized');
    }
}
