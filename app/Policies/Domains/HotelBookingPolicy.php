<?php declare(strict_types=1);

namespace App\Policies\Domains;

use App\Domains\Hotels\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class HotelBookingPolicy
{
    use HandlesAuthorization;

    /**
     * Admins can do anything
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * View booking (guest, hotel staff, manager, admin)
     */
    public function view(User $user, Booking $booking): bool
    {
        // Tenant scoping
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Guest can view their own bookings
        if ($user->id === $booking->guest_id) {
            return true;
        }

        // Hotel staff and managers can view
        if ($booking->hotel_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']);
        }

        // Manager/accountant can view all
        return $user->hasRole(['manager', 'accountant']);
    }

    /**
     * Create booking (guest, business_owner, manager)
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['guest', 'business_owner', 'manager']);
    }

    /**
     * Update booking (guest before checkin, hotel staff, manager)
     */
    public function update(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Guest can update before checkin
        if ($user->id === $booking->guest_id && now() < $booking->check_in_at) {
            return true;
        }

        // Hotel staff can update
        if ($booking->hotel_id === $user->current_business_group_id) {
            return $user->hasRole(['employee', 'manager']);
        }

        return $user->hasRole(['manager', 'business_owner']);
    }

    /**
     * Check in guest (hotel staff)
     */
    public function checkIn(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $booking->hotel_id === $user->current_business_group_id &&
               $user->hasRole(['employee', 'manager']) &&
               $booking->status === 'confirmed';
    }

    /**
     * Check out guest (hotel staff)
     */
    public function checkOut(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $booking->hotel_id === $user->current_business_group_id &&
               $user->hasRole(['employee', 'manager']) &&
               $booking->status === 'checked_in';
    }

    /**
     * Cancel booking
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Guest can cancel with conditions (cancellation policy)
        if ($user->id === $booking->guest_id && $booking->status !== 'completed') {
            return true;
        }

        // Hotel manager can cancel
        if ($booking->hotel_id === $user->current_business_group_id) {
            return $user->hasRole(['manager']);
        }

        return $user->hasRole(['manager', 'admin']);
    }

    /**
     * Rate booking (guest after checkout)
     */
    public function rate(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->id === $booking->guest_id && $booking->status === 'completed';
    }

    /**
     * Delete booking (admin only)
     */
    public function delete(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * View booking invoice
     */
    public function viewInvoice(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Guest can view their own invoice
        if ($user->id === $booking->guest_id) {
            return true;
        }

        // Hotel accounting staff
        return $booking->hotel_id === $user->current_business_group_id &&
               $user->hasRole(['accountant', 'manager']);
    }

    /**
     * Modify pricing (hotel manager/business owner only)
     */
    public function modifyPrice(User $user, Booking $booking): bool
    {
        if ($booking->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only before payment
        if ($booking->payment_status !== 'pending') {
            return false;
        }

        return $booking->hotel_id === $user->current_business_group_id &&
               $user->hasRole(['manager', 'business_owner']);
    }
}
