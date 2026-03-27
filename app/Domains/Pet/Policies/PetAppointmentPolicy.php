<?php

declare(strict_types=1);


namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetAppointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * PetAppointmentPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class PetAppointmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, PetAppointment $appointment): Response
    {
        return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
            && $appointment->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, PetAppointment $appointment): Response
    {
        return $appointment->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function cancel(User $user, PetAppointment $appointment): Response
    {
        return ($appointment->owner_id === $user->id || $appointment->clinic->owner_id === $user->id)
            && $appointment->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function complete(User $user, PetAppointment $appointment): Response
    {
        return $appointment->clinic->owner_id === $user->id && $appointment->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
