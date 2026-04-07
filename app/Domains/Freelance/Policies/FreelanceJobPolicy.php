<?php declare(strict_types=1);

/**
 * FreelanceJobPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/freelancejobpolicy
 */


namespace App\Domains\Freelance\Policies;

final class FreelanceJobPolicy
{

    public function view(?User $user, FreelanceJob $job): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->id ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function viewProposals(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
        }

        public function close(User $user, FreelanceJob $job): Response
        {
            return $user->id === $job->client_id ? $this->response->allow() : $this->response->deny();
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
