<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class PetReviewPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, PetReview $review): Response
    {
        return $review->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, PetReview $review): Response
    {
        return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, PetReview $review): Response
    {
        return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function approve(User $user, PetReview $review): Response
    {
        return $user->hasPermissionTo('pet_review_approve')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
