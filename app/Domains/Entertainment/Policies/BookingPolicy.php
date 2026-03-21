<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\Booking;
use Illuminate\Auth\Access\Response;

final class BookingPolicy
{
    public function viewAny(User $user): Response
    {
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function view(User $user, Booking $booking): Response
    {
        return $user->id === $booking->customer_id || $user->hasPermissionTo('view_bookings')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return auth()->check() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function cancel(User $user, Booking $booking): Response
    {
        return $user->id === $booking->customer_id || $user->hasPermissionTo('cancel_bookings')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
