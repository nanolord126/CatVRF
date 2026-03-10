<?php

namespace App\Policies;

use App\Models\User;
use App\Domains\Hotel\Models\HotelBooking;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * HotelBookingPolicy - Правила доступа к бронированиям (Production 2026).
 *
 * @package App\Policies
 */
class HotelBookingPolicy extends BaseSecurityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff']) &&
               $user->tenant_id !== null;
    }

    public function view(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        // Персонал отеля может видеть все бронирования
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff'])) {
            return true;
        }

        // Гость может видеть только свои бронирования
        return $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        // Персонал может изменять бронирования в статусах pending/confirmed
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff'])) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        // Гость может изменять только свои неподтвержденные бронирования
        return $booking->user_id === $user->id && $booking->status === 'pending';
    }

    public function cancel(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        // Персонал может отменить до check-in
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff'])) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        // Гость может отменить свое бронирование до check-in
        return $booking->user_id === $user->id && in_array($booking->status, ['pending', 'confirmed']);
    }

    public function delete(User $user, HotelBooking $booking): bool
    {
        return $user->hasRole('admin') && $user->tenant_id === $booking->tenant_id;
    }

    public function confirm(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff']);
    }

    public function checkIn(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff']);
    }

    public function checkOut(User $user, HotelBooking $booking): bool
    {
        if ($user->tenant_id !== $booking->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'hotel-staff']);
    }
}
