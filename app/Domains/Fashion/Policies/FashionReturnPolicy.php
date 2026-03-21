<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionReturn;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FashionReturnPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('view_returns') ? Response::allow() : Response::deny();
    }

    public function view(User $user, FashionReturn $return): Response
    {
        return $user->id === $return->customer_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('request_return') ? Response::allow() : Response::deny();
    }

    public function update(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FashionReturn $return): Response
    {
        return $user->isAdmin() ? Response::allow() : Response::deny();
    }
}
