<?php

declare(strict_types=1);

/**
 * FreelanceProposalPolicy — CatVRF 2026 Component.
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
 * @see https://catvrf.ru/docs/freelanceproposalpolicy
 */

namespace App\Domains\Freelance\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class FreelanceProposalPolicy
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';


    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id || $user->id === $proposal->job->client_id
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function create(User $user): Response
    {
        return $user->id ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id && $proposal->status === 'pending'
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function delete(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id && in_array($proposal->status, ['pending', 'rejected'], true)
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function accept(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
            ? $this->response->allow()
            : $this->response->deny();
    }

    public function reject(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
            ? $this->response->allow()
            : $this->response->deny();
    }
}
