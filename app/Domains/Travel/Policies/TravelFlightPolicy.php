<?php

declare(strict_types=1);

namespace App\Domains\Travel\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

final class TravelFlightPolicy
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function $this->viewFactory->make(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $user->can('create_travel_flight')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($flight->agency && $flight->agency->owner_id !== $user->id && ! $user->can('update_travel_flight')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function delete(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        if ($flight->agency && $flight->agency->owner_id !== $user->id && ! $user->can('delete_travel_flight')) {
            return $this->response->deny('Unauthorized');
        }

        return $this->response->allow();
    }

    public function restore(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('restore_travel_flight')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function forceDelete(User $user, TravelFlight $flight): Response
    {
        if ($flight->tenant_id !== tenant()->id) {
            return $this->response->deny('Unauthorized');
        }

        return $user->can('force_delete_travel_flight')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
