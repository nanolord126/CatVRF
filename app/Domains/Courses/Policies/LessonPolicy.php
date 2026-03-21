<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class LessonPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, Lesson $lesson): Response
    {
        if (!$lesson->is_published) {
            if ($user && ($user->id === $lesson->course->instructor_id || $user->isAdmin())) {
                return Response::allow();
            }
            return Response::deny('Lesson not published');
        }

        return Response::allow();
    }

    public function create(User $user, Lesson $lesson): Response
    {
        return $user->id === $lesson->course->instructor_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, Lesson $lesson): Response
    {
        return $user->id === $lesson->course->instructor_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, Lesson $lesson): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete lessons');
    }
}
