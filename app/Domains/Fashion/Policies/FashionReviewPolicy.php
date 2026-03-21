<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class FashionReviewPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, FashionReview $review): Response
    {
        return $review->status === 'approved' || ($user && ($user->id === $review->reviewer_id || $user->isAdmin())) ? Response::allow() : Response::deny();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, FashionReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }

    public function delete(User $user, FashionReview $review): Response
    {
        return $user->id === $review->reviewer_id || $user->isAdmin() ? Response::allow() : Response::deny();
    }
}
