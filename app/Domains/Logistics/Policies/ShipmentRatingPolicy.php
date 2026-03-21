<?php declare(strict_types=1);

namespace App\Domains\Logistics\Policies;

use App\Domains\Logistics\Models\ShipmentRating;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class ShipmentRatingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, ShipmentRating $rating): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, ShipmentRating $rating): Response
    {
        return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }

    public function delete(User $user, ShipmentRating $rating): Response
    {
        return $user->id === $rating->reviewer_id || $user->hasRole('admin') ? Response::allow() : Response::deny();
    }
}
