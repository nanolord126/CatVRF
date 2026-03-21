<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Carbon;

/**
 * Политика для записи на услугу.
 * Production 2026.
 */
final class AppointmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) filament()->getTenant();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $appointment->tenant_id === tenant('id') && (
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
        return $appointment->tenant_id === tenant('id') && $user->can('update_appointments');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        // Клиент может отменить свою запись в течение 24 часов до начала
        if ($appointment->client_id === $user->id) {
            return $appointment->datetime_start->greaterThan(now()->addDay());
        }

        return $appointment->tenant_id === tenant('id') && $user->can('cancel_appointments');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $appointment->tenant_id === tenant('id') && $user->can('delete_appointments');
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return $appointment->tenant_id === tenant('id') && $user->can('restore_appointments');
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $appointment->tenant_id === tenant('id') && $user->can('force_delete_appointments');
    }
}
