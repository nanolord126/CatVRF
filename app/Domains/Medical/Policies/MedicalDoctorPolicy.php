<?php

declare(strict_types=1);


namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalDoctor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * MedicalDoctorPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalDoctorPolicy
{
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
