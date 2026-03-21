<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class MedicalAppointmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->hasPermissionTo('view_appointments') ? Response::allow() : Response::deny();
    }

    public function view(User $user, MedicalAppointment $appointment): Response
    {
        return $user->id === $appointment->patient_id || $user->hasRole('admin')
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, MedicalAppointment $appointment): Response
    {
        return $user->id === $appointment->patient_id || $user->hasRole('admin')
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, MedicalAppointment $appointment): Response
    {
        return $user->id === $appointment->patient_id || $user->hasRole('admin')
            ? Response::allow()
            : Response::deny();
    }
}
