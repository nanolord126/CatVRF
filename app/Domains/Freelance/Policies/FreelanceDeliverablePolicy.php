<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceDeliverable;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FreelanceDeliverablePolicy
{
    public function view(User $user, FreelanceDeliverable $deliverable): Response
    {
        $contract = $deliverable->contract;
        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->id ? Response::allow() : Response::deny();
    }

    public function update(User $user, FreelanceDeliverable $deliverable): Response
    {
        return $user->id === $deliverable->freelancer->user_id && $deliverable->status === 'pending'
            ? Response::allow()
            : Response::deny();
    }

    public function approve(User $user, FreelanceDeliverable $deliverable): Response
    {
        return $user->id === $deliverable->contract->client_id && $deliverable->status === 'submitted'
            ? Response::allow()
            : Response::deny();
    }

    public function requestRevision(User $user, FreelanceDeliverable $deliverable): Response
    {
        return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'pending'])
            ? Response::allow()
            : Response::deny();
    }

    public function reject(User $user, FreelanceDeliverable $deliverable): Response
    {
        return $user->id === $deliverable->contract->client_id && in_array($deliverable->status, ['submitted', 'revisions_requested'])
            ? Response::allow()
            : Response::deny();
    }
}
