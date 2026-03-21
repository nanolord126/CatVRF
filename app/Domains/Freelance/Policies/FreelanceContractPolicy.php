<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\FreelanceContract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FreelanceContractPolicy
{
    public function view(User $user, FreelanceContract $contract): Response
    {
        return $user->id === $contract->freelancer->user_id || $user->id === $contract->client_id
            ? Response::allow()
            : Response::deny();
    }

    public function update(User $user, FreelanceContract $contract): Response
    {
        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
            ? Response::allow()
            : Response::deny();
    }

    public function release(User $user, FreelanceContract $contract): Response
    {
        return $user->id === $contract->client_id && in_array($contract->status, ['active', 'on_hold'])
            ? Response::allow()
            : Response::deny();
    }

    public function complete(User $user, FreelanceContract $contract): Response
    {
        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
            ? Response::allow()
            : Response::deny();
    }

    public function pause(User $user, FreelanceContract $contract): Response
    {
        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
            ? Response::allow()
            : Response::deny();
    }

    public function cancel(User $user, FreelanceContract $contract): Response
    {
        return in_array($user->id, [$contract->freelancer->user_id, $contract->client_id])
            ? Response::allow()
            : Response::deny();
    }
}
