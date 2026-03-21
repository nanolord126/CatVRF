<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetAppointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class PetAppointmentPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, PetAppointment $appointment): Response
    {
        return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
            && $appointment->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, PetAppointment $appointment): Response
    {
        return $appointment->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function cancel(User $user, PetAppointment $appointment): Response
    {
        return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
            && $appointment->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function complete(User $user, PetAppointment $appointment): Response
    {
        return $appointment->clinic->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
