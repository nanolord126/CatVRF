<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAppointmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $user->hasPermissionTo('view_appointments') ? $this->response->allow() : $this->response->deny();
        }

        public function view(User $user, MedicalAppointment $appointment): Response
        {
            return $user->id === $appointment->patient_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, MedicalAppointment $appointment): Response
        {
            return $user->id === $appointment->patient_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function delete(User $user, MedicalAppointment $appointment): Response
        {
            return $user->id === $appointment->patient_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }
}
