<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\CourseReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CourseReviewPolicy
{
    public function viewAny(?User $user): Response
    {
        return Response::allow();
    }

    public function view(?User $user, CourseReview $review): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, CourseReview $review): Response
    {
        if ($user->id === $review->student_id || $user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Unauthorized');
    }

    public function delete(User $user, CourseReview $review): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can delete reviews');
    }
}
