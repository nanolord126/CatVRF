<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelFlight;
use Illuminate\Auth\Access\Response;

final class TravelFlightPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_flight')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($flight->agency && $flight->agency->owner_id !== $user->id && !$user->can('update_travel_flight')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function delete(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($flight->agency && $flight->agency->owner_id !== $user->id && !$user->can('delete_travel_flight')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function restore(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('restore_travel_flight')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('force_delete_travel_flight')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
