<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Policies;

use App\Domains\Education\Courses\Models\Lesson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * LessonPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LessonPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, Lesson $lesson): Response
    {
        if (!$lesson->is_published) {
            if ($user && ($user->id === $lesson->course->instructor_id || $user->isAdmin())) {
                return $this->response->allow();
            }
            return $this->response->deny('Lesson not published');
        }

        return $this->response->allow();
    }

    public function create(User $user, Lesson $lesson): Response
    {
        return $user->id === $lesson->course->instructor_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, Lesson $lesson): Response
    {
        return $user->id === $lesson->course->instructor_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, Lesson $lesson): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete lessons');
    }
}
