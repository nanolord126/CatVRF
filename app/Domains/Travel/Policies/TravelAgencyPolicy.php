<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelAgency;
use Illuminate\Auth\Access\Response;

final class TravelAgencyPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_agency')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($agency->owner_id !== $user->id && !$user->can('update_travel_agency')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function delete(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($agency->owner_id !== $user->id && !$user->can('delete_travel_agency')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function restore(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('restore_travel_agency')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('force_delete_travel_agency')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
