<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceProposal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FreelanceProposalPolicy
{
    public function view(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id || $user->id === $proposal->job->client_id
            ? Response::allow()
            : Response::deny();
    }

    public function create(User $user): Response
    {
        return $user->id ? Response::allow() : Response::deny();
    }

    public function update(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id && $proposal->status === 'pending'
            ? Response::allow()
            : Response::deny();
    }

    public function delete(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->freelancer->user_id && in_array($proposal->status, ['pending', 'rejected'])
            ? Response::allow()
            : Response::deny();
    }

    public function accept(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
            ? Response::allow()
            : Response::deny();
    }

    public function reject(User $user, FreelanceProposal $proposal): Response
    {
        return $user->id === $proposal->job->client_id && $proposal->status === 'pending'
            ? Response::allow()
            : Response::deny();
    }
}
