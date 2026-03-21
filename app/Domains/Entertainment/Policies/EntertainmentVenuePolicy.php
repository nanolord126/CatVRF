<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\EntertainmentVenue;
use Illuminate\Auth\Access\Response;

final class EntertainmentVenuePolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, EntertainmentVenue $venue): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainment_venues')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, EntertainmentVenue $venue): Response
    {
        return $user->hasPermissionTo('update_entertainment_venues')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, EntertainmentVenue $venue): Response
    {
        return $user->hasPermissionTo('delete_entertainment_venues')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
