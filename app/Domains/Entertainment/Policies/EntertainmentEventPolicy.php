<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Policies;

use App\Models\User;
use App\Domains\Entertainment\Models\EntertainmentEvent;
use Illuminate\Auth\Access\Response;

final class EntertainmentEventPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, EntertainmentEvent $event): Response
    {
        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('create_entertainment_events')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function update(User $user, EntertainmentEvent $event): Response
    {
        return $user->hasPermissionTo('update_entertainment_events')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }

    public function delete(User $user, EntertainmentEvent $event): Response
    {
        return $user->hasPermissionTo('delete_entertainment_events')
            ? Response::allow()
            : Response::deny('Unauthorized');
    }
}
