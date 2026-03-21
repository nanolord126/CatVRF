<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CertificatePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Certificate $certificate): Response
    {
        if ($user->id === $certificate->student_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function download(User $user, Certificate $certificate): Response
    {
        if ($user->id === $certificate->student_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }
}
