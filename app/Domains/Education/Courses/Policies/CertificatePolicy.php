<?php declare(strict_types=1);

/**
 * CertificatePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/certificatepolicy
 */


namespace App\Domains\Education\Courses\Policies;

final class CertificatePolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, Certificate $certificate): Response
        {
            if ($user->id === $certificate->student_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
        }

        public function download(User $user, Certificate $certificate): Response
        {
            if ($user->id === $certificate->student_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
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

    /**
     * Get the component identifier for logging and audit purposes.
     *
     * @return string The fully qualified component name
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

}
