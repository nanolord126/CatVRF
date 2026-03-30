<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalDoctorPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, MedicalDoctor $doctor): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_medical_doctor') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, MedicalDoctor $doctor): Response
        {
            return $user->id === $doctor->user_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function delete(User $user, MedicalDoctor $doctor): Response
        {
            return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
