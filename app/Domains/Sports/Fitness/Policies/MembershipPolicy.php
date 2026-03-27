<?php

declare(strict_types=1);


namespace App\Domains\Sports\Fitness\Policies;

use App\Domains\Sports\Fitness\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final /**
 * MembershipPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MembershipPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny();
    }

    public function view(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('view_memberships') ? $this->response->allow() : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->auth() ? $this->response->allow() : $this->response->deny();
    }

    public function cancel(User $user, Membership $membership): Response
    {
        return $user->id === $membership->member_id || $user->hasPermissionTo('cancel_memberships') ? $this->response->allow() : $this->response->deny();
    }
}
