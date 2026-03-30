<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MembershipPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
