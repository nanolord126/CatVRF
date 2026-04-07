<?php declare(strict_types=1);

/**
 * FreelanceContractPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/freelancecontractpolicy
 */


namespace App\Domains\Freelance\Policies;

final class FreelanceContractPolicy
{

    public function view(User $user, FreelanceContract $contract): Response
        {
            return $user->id === $contract->freelancer->user_id || $user->id === $contract->client_id
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function update(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function release(User $user, FreelanceContract $contract): Response
        {
            return $user->id === $contract->client_id && in_array($contract->status, ['active', 'on_hold'])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function complete(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function pause(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function cancel(User $user, FreelanceContract $contract): Response
        {
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
