<?php

declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class TravelAgencyPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_agency')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($agency->owner_id !== $user->id && ! $user->can('update_travel_agency')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function delete(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($agency->owner_id !== $user->id && ! $user->can('delete_travel_agency')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function restore(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('restore_travel_agency')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelAgency $agency): Response
    {
        if ($agency->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('force_delete_travel_agency')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
