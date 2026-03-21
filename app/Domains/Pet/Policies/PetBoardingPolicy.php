<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Domains\Pet\Models\PetBoardingReservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class PetBoardingPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, PetBoardingReservation $reservation): Response
    {
        return ($reservation->owner_id === $user->id || $reservation->clinic->owner_id === $user->id)
            && $reservation->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return Response::allow();
    }

    public function update(User $user, PetBoardingReservation $reservation): Response
    {
        return $reservation->owner_id === $user->id && $reservation->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function cancel(User $user, PetBoardingReservation $reservation): Response
    {
        return ($reservation->owner_id === $user->id || $reservation->clinic->owner_id === $user->id)
            && $reservation->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function complete(User $user, PetBoardingReservation $reservation): Response
    {
        return $reservation->clinic->owner_id === $user->id && $reservation->tenant_id === $user->current_tenant_id
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
