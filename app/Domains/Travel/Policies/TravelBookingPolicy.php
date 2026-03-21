<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Auth\Access\Response;

final class TravelBookingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('view_travel_booking')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('update_travel_booking')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function delete(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('delete_travel_booking')) {
            return Response::deny('Unauthorized');
        }

        return Response::allow();
    }

    public function restore(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('restore_travel_booking')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return Response::deny('Unauthorized');
        }

        return $user->can('force_delete_travel_booking')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
