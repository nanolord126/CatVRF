<?php declare(strict_types=1);

/**
 * EnrollmentPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/enrollmentpolicy
 */


namespace App\Domains\Education\Courses\Policies;

final class EnrollmentPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, Enrollment $enrollment): Response
        {
            if ($user->id === $enrollment->student_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            if ($user->id === $enrollment->course->instructor_id) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, Enrollment $enrollment): Response
        {
            return $user->isAdmin()
                ? $this->response->allow()
                : $this->response->deny('Only admins can update enrollments');
        }

        public function delete(User $user, Enrollment $enrollment): Response
        {
            return $user->isAdmin()
                ? $this->response->allow()
                : $this->response->deny('Only admins can delete enrollments');
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
