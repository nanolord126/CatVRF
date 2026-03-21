<?php declare(strict_types=1);

namespace App\Domains\Freelance\Policies;

use App\Domains\Freelance\Models\Freelancer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FreelancerPolicy
{
    public function view(?User $user, Freelancer $freelancer): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->id ? Response::allow() : Response::deny();
    }

    public function update(User $user, Freelancer $freelancer): Response
    {
        return $user->id === $freelancer->user_id ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Freelancer $freelancer): Response
    {
        return $user->id === $freelancer->user_id ? Response::allow() : Response::deny();
    }

    public function viewDetails(User $user, Freelancer $freelancer): Response
    {
        return Response::allow();
    }
}
