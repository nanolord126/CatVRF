<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use App\Domains\Medical\Models\MedicalDoctor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class MedicalDoctorPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, MedicalDoctor $doctor): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_medical_doctor') ? Response::allow() : Response::deny();
    }

    public function update(User $user, MedicalDoctor $doctor): Response
    {
        return $user->id === $doctor->user_id || $user->hasRole('admin')
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, MedicalDoctor $doctor): Response
    {
        return $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
