<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelTransportation;
use Illuminate\Auth\Access\Response;

final class TravelTransportationPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_transportation')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($transportation->agency && $transportation->agency->owner_id !== $user->id && !$user->can('update_travel_transportation')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function delete(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($transportation->agency && $transportation->agency->owner_id !== $user->id && !$user->can('delete_travel_transportation')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function restore(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('restore_travel_transportation')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('force_delete_travel_transportation')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
