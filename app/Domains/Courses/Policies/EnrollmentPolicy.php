<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class EnrollmentPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, Enrollment $enrollment): Response
    {
        if ($user->id === $enrollment->student_id || $user->isAdmin()) {
            return Response::allow();
        }

        if ($user->id === $enrollment->course->instructor_id) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, Enrollment $enrollment): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can update enrollments');
    }

    public function delete(User $user, Enrollment $enrollment): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete enrollments');
    }
}
