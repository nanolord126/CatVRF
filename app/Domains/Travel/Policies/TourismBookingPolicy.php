<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Domains\Travel\Models\TourBooking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Tourism Booking Policy
 * 
 * Authorization policy for tourism booking operations.
 * Follows CatVRF canonical rules for policy-based access control.
 */
final class TourismBookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the booking.
     */
    public function view(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->isAdmin()
            || ($booking->business_group_id && $user->businessGroups()->where('id', $booking->business_group_id)->exists());
    }

    /**
     * Determine if the user can create bookings.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the booking.
     */
    public function update(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->isAdmin()
            || ($booking->business_group_id && $user->businessGroups()->where('id', $booking->business_group_id)->exists());
    }

    /**
     * Determine if the user can confirm the booking.
     */
    public function confirm(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id
            && $booking->status === 'held'
            && $booking->hold_expires_at
            && $booking->hold_expires_at->isFuture();
    }

    /**
     * Determine if the user can cancel the booking.
     */
    public function cancel(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->isAdmin()
            || ($booking->business_group_id && $user->businessGroups()->where('id', $booking->business_group_id)->exists());
    }

    /**
     * Determine if the user can schedule video call.
     */
    public function scheduleVideoCall(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id
            && $booking->status === 'confirmed';
    }

    /**
     * Determine if the user can mark virtual tour as viewed.
     */
    public function markVirtualTour(User $user, TourBooking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Determine if the user can delete the booking.
     */
    public function delete(User $user, TourBooking $booking): bool
    {
        return $user->isAdmin();
    }
}
