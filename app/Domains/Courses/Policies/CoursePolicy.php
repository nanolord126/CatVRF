<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CoursePolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Course $course): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermission('courses.create')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, Course $course): Response
    {
        if ($user->id === $course->instructor_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function delete(User $user, Course $course): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete courses');
    }
}
