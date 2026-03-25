declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalAppointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * MedicalAppointmentPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalAppointmentPolicy
{
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
