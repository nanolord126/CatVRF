<?php declare(strict_types=1);

namespace Modules\RealEstate\Policies;

use App\Models\User;
use Modules\RealEstate\Models\PropertyBooking;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PropertyBookingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            || $user->id === $booking->user_id
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_real_estate_bookings')
            || $user->hasRole('admin');
    }

    public function update(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            && ($user->hasPermissionTo('update_real_estate_bookings') || $user->hasRole('admin'));
    }

    public function delete(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            && ($user->hasPermissionTo('delete_real_estate_bookings') || $user->hasRole('admin'));
    }

    public function confirm(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            && ($user->hasPermissionTo('confirm_real_estate_bookings') || $user->hasRole('admin'));
    }

    public function complete(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            && ($user->hasPermissionTo('complete_real_estate_bookings') || $user->hasRole('admin'));
    }

    public function cancel(User $user, PropertyBooking $booking): bool
    {
        return $user->tenant_id === $booking->tenant_id
            && ($user->id === $booking->user_id || $user->hasPermissionTo('cancel_real_estate_bookings') || $user->hasRole('admin'));
    }

    public function initiateVideoCall(User $user, PropertyBooking $booking): bool
    {
        return $user->id === $booking->user_id
            || $user->hasPermissionTo('initiate_video_calls') || $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_real_estate_bookings')
            || $user->hasRole('admin');
    }
}
