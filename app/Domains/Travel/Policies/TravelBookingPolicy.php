<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Auth\Access\Response;

final class TravelBookingPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('view_travel_booking')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('update_travel_booking')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function delete(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($booking->user_id !== $user->id && !$user->can('delete_travel_booking')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function restore(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('restore_travel_booking')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelBooking $booking): Response
    {
        if ($booking->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('force_delete_travel_booking')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
