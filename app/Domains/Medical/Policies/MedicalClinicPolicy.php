<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalClinic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class MedicalClinicPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, MedicalClinic $clinic): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_medical_clinic') ? Response::allow() : Response::deny();
    }

    public function update(User $user, MedicalClinic $clinic): Response
    {
        return $user->id === $clinic->owner_id || $user->hasRole('admin') 
            ? Response::allow() 
            : Response::deny();
    }

    public function delete(User $user, MedicalClinic $clinic): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
