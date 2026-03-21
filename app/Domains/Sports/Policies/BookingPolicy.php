<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class BookingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Booking $booking): Response
    {
        return ($user->id === $booking->member_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, Booking $booking): Response
    {
        return ($user->id === $booking->member_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }

    public function cancel(User $user, Booking $booking): Response
    {
        return ($user->id === $booking->member_id || $user->hasRole('admin')) ? Response::allow() : Response::deny();
    }
}
