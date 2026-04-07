<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Policies;


use Carbon\Carbon;
final class AppointmentPolicy
{

    use HandlesAuthorization;

        public function viewAny(User $user): bool
        {
            return (bool) filament()->getTenant();
        }

        public function view(User $user, Appointment $appointment): bool
        {
            return $appointment->tenant_id === tenant()->id && (
                $appointment->client_id === $user->id ||
                $user->can('view_all_appointments')
            );
        }

        public function create(User $user): bool
        {
            return (bool) filament()->getTenant() && $user->can('create_appointments');
        }

        public function update(User $user, Appointment $appointment): bool
        {
            return $appointment->tenant_id === tenant()->id && $user->can('update_appointments');
        }

        public function cancel(User $user, Appointment $appointment): bool
        {
            // Клиент может отменить свою запись в течение 24 часов до начала
            if ($appointment->client_id === $user->id) {
                return $appointment->datetime_start->greaterThan(Carbon::now()->addDay());
            }

            return $appointment->tenant_id === tenant()->id && $user->can('cancel_appointments');
        }

        public function delete(User $user, Appointment $appointment): bool
        {
            return $appointment->tenant_id === tenant()->id && $user->can('delete_appointments');
        }

        public function restore(User $user, Appointment $appointment): bool
        {
            return $appointment->tenant_id === tenant()->id && $user->can('restore_appointments');
        }

        public function forceDelete(User $user, Appointment $appointment): bool
        {
            return $appointment->tenant_id === tenant()->id && $user->can('force_delete_appointments');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
