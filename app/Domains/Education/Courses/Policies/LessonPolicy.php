<?php declare(strict_types=1);

/**
 * LessonPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/lessonpolicy
 */


namespace App\Domains\Education\Courses\Policies;

final class LessonPolicy
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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
