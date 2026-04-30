<?php

declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class TravelTransportationPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_transportation')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($transportation->agency && $transportation->agency->owner_id !== $user->id && ! $user->can('update_travel_transportation')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function delete(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($transportation->agency && $transportation->agency->owner_id !== $user->id && ! $user->can('delete_travel_transportation')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function restore(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('restore_travel_transportation')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelTransportation $transportation): Response
    {
        if ($transportation->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('force_delete_travel_transportation')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
