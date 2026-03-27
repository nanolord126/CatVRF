<?php

declare(strict_types=1);


namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalClinic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * MedicalClinicPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MedicalClinicPolicy
{
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
