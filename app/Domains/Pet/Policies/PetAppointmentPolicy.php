<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetAppointmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
