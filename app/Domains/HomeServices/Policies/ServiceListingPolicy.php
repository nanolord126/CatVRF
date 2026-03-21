<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceListing;
use Illuminate\Auth\Access\Response;

final class ServiceListingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, ServiceListing $listing): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_listings') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function update(User $user, ServiceListing $listing): Response
    {
        return $user->id === $listing->contractor->user_id || $user->hasPermissionTo('update_listings') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function delete(User $user, ServiceListing $listing): Response
    {
        return $user->id === $listing->contractor->user_id || $user->hasPermissionTo('delete_listings') ? Response::allow() : Response::deny('Unauthorized');
    }
}
