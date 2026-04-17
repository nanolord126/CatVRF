<?php

declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class SportsBookingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id 
            || $user->hasRole('admin')
            || $user->hasRole('trainer') && $booking->trainer_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') 
            || $user->hasRole('trainer');
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && !$user->isBanned();
    }

    public function update(User $user, Booking $booking): bool
    {
        if ($booking->status === 'completed' || $booking->status === 'cancelled') {
            return false;
        }

        return $booking->user_id === $user->id 
            || $user->hasRole('admin')
            || $user->hasRole('trainer') && $booking->trainer_id === $user->id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        if (!$booking->canBeCancelled()) {
            return false;
        }

        return $booking->user_id === $user->id 
            || $user->hasRole('admin');
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            return false;
        }

        return $user->hasRole('admin') 
            || $user->hasRole('trainer') && $booking->trainer_id === $user->id;
    }

    public function verifyBiometric(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id 
            && $booking->status === 'confirmed';
    }

    public function extendHold(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id 
            && $booking->status === 'pending';
    }

    public function viewForVenue(User $user, int $venueId): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('trainer') && $user->trainerProfile?->studio_id === $venueId;
    }

    public function viewForTrainer(User $user, int $trainerId): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('trainer') && $user->id === $trainerId;
    }

    public function bulkCreate(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('business_group_admin');
    }

    public function export(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('business_group_admin');
    }
}
