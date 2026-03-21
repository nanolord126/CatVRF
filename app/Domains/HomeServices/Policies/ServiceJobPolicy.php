<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceJob;
use Illuminate\Auth\Access\Response;

final class ServiceJobPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function view(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->client_id || $user->id === $job->contractor->user_id ? Response::allow() : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function accept(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->contractor->user_id ? Response::allow() : Response::deny('Unauthorized');
    }

    public function cancel(User $user, ServiceJob $job): Response
    {
        return $user->id === $job->client_id || $user->id === $job->contractor->user_id ? Response::allow() : Response::deny('Unauthorized');
    }
}
