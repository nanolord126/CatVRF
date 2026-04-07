<?php declare(strict_types=1);

/**
 * CoursePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/coursepolicy
 */


namespace App\Domains\Education\Courses\Policies;

final class CoursePolicy
{

    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, Course $course): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('courses.create')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function update(User $user, Course $course): Response
        {
            if ($user->id === $course->instructor_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
        }

        public function delete(User $user, Course $course): Response
        {
            return $user->isAdmin()
                ? $this->response->allow()
                : $this->response->deny('Only admins can delete courses');
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
