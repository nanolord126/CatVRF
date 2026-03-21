<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\EventReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class EventReviewPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, EventReview $review): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, EventReview $review): Response
    {
        if ($user->id === $review->buyer_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function delete(User $user, EventReview $review): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete reviews');
    }
}
