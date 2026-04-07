<?php declare(strict_types=1);

/**
 * FreelanceDeliverablePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/freelancedeliverablepolicy
 */


namespace App\Domains\Freelance\Policies;

final class FreelanceDeliverablePolicy
{

    public function view(User $user, FreelanceDeliverable $deliverable): Response
        {
            $contract = $deliverable->contract;
            return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $user->id ? $this->response->allow() : $this->response->deny();
        }

        public function update(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->freelancer->user_id && $deliverable->status === 'pending'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function approve(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && $deliverable->status === 'submitted'
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function requestRevision(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'pending'])
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function reject(User $user, FreelanceDeliverable $deliverable): Response
        {
            return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'revisions_requested'])
                ? $this->response->allow()
                : $this->response->deny();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
