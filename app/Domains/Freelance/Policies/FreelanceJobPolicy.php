<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceJob;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FreelanceJobPolicy
{
    public function view(?User $user, FreelanceJob $job): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->id ? Response::allow() : Response::deny();
    }

    public function update(User $user, FreelanceJob $job): Response
    {
        return $user->id === $job->client_id ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FreelanceJob $job): Response
    {
        return $user->id === $job->client_id ? Response::allow() : Response::deny();
    }

    public function viewProposals(User $user, FreelanceJob $job): Response
    {
        return $user->id === $job->client_id ? Response::allow() : Response::deny();
    }

    public function close(User $user, FreelanceJob $job): Response
    {
        return $user->id === $job->client_id ? Response::allow() : Response::deny();
    }
}
