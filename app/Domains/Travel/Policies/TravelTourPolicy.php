<?php declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use App\Models\User;
use App\Domains\Travel\Models\TravelTour;
use Illuminate\Auth\Access\Response;

final class TravelTourPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, TravelTour $tour): Response
    {
        if ($tour->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_tour')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TravelTour $tour): Response
    {
        if ($tour->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($tour->agency->owner_id !== $user->id && !$user->can('update_travel_tour')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function delete(User $user, TravelTour $tour): Response
    {
        if ($tour->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($tour->agency->owner_id !== $user->id && !$user->can('delete_travel_tour')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function restore(User $user, TravelTour $tour): Response
    {
        if ($tour->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('restore_travel_tour')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelTour $tour): Response
    {
        if ($tour->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('force_delete_travel_tour')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
