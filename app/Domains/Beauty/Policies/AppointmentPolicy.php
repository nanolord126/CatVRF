<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class AppointmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id
            || $user->tenant_id === $appointment->tenant_id
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && !$user->is_blocked;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($appointment->status === 'completed' || $appointment->status === 'cancelled') {
            return false;
        }

        return $user->id === $appointment->user_id
            || $user->hasRole('admin');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        if ($appointment->status === 'completed') {
            return false;
        }

        return $user->id === $appointment->user_id
            || $user->hasRole('admin');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($appointment->status === 'completed' || $appointment->status === 'cancelled') {
            return false;
        }

        $hoursUntilAppointment = now()->diffInHours($appointment->starts_at, false);

        if ($hoursUntilAppointment < 1) {
            return false;
        }

        return $user->id === $appointment->user_id
            || $user->hasRole('admin');
    }

    public function initiateVideoCall(User $user, Appointment $appointment): bool
    {
        if ($appointment->status !== 'pending_payment') {
            return false;
        }

        if (isset($appointment->metadata['video_call_expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($appointment->metadata['video_call_expires_at']);
            if ($expiresAt->isFuture()) {
                return true;
            }
        }

        return $user->id === $appointment->user_id;
    }

    public function processPayment(User $user, Appointment $appointment): bool
    {
        if ($appointment->status !== 'pending_payment') {
            return false;
        }

        return $user->id === $appointment->user_id;
    }
}
