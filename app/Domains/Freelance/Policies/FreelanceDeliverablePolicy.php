<?php

declare(strict_types=1);

/**
 * FreelanceDeliverablePolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/freelancedeliverablepolicy
 */

namespace App\Domains\Freelance\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class FreelanceDeliverablePolicy
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';


    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, FreelanceDeliverable $deliverable): Response
    {
        $contract = $deliverable->contract;

        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id], true)
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
        return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'pending'], true)
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function reject(User $user, FreelanceDeliverable $deliverable): Response
    {
        return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'revisions_requested'], true)
            ? $this->response->allow()
            : $this->response->deny();
    }
}
