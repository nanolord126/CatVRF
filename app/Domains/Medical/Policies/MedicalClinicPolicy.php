<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalClinicPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, MedicalClinic $clinic): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermissionTo('create_medical_clinic') ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, MedicalClinic $clinic): Response
        {
            return $user->id === $clinic->owner_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function delete(User $user, MedicalClinic $clinic): Response
        {
            return $user->hasRole('admin') ? $this->response->allow() : $this->response->deny();
        }
}
