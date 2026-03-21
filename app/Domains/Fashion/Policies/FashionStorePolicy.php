<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionStore;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FashionStorePolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, FashionStore $store): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('create_fashion_store') ? Response::allow() : Response::deny();
    }

    public function update(User $user, FashionStore $store): Response
    {
        return $user->id === $store->owner_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FashionStore $store): Response
    {
        return $user->isAdmin() ? Response::allow() : Response::deny();
    }
}
