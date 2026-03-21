<?php declare(strict_types=1);

namespace App\Domains\Fitness\Policies;

use App\Domains\Fitness\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class MembershipPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny();
    }

    public function view(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('view_memberships') ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny();
    }

    public function cancel(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('cancel_memberships') ? Response::allow() : Response::deny();
    }
}
