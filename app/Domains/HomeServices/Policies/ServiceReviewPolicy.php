<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Policies;

use App\Models\User;
use App\Domains\HomeServices\Models\ServiceReview;
use Illuminate\Auth\Access\Response;

final class ServiceReviewPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, ServiceReview $review): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->auth() ? Response::allow() : Response::deny('Unauthorized');
    }

    public function update(User $user, ServiceReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->hasPermissionTo('update_reviews') ? Response::allow() : Response::deny('Unauthorized');
    }

    public function delete(User $user, ServiceReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->hasPermissionTo('delete_reviews') ? Response::allow() : Response::deny('Unauthorized');
    }
}
