<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\CourierRating;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class CourierRatingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, CourierRating $rating): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, CourierRating $rating): Response
    {
        return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, CourierRating $rating): Response
    {
        return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
