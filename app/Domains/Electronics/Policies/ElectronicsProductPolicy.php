<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\Electronics\Models\ElectronicsProduct;

final class ElectronicsProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function $this->viewFactory->make(User $user, ElectronicsProduct $electronicsProduct): bool
    {
        return $user->tenant_id === $electronicsProduct->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ElectronicsProduct $electronicsProduct): bool
    {
        return $user->tenant_id === $electronicsProduct->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ElectronicsProduct $electronicsProduct): bool
    {
        return $user->tenant_id === $electronicsProduct->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ElectronicsProduct $electronicsProduct): bool
    {
        return $user->tenant_id === $electronicsProduct->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ElectronicsProduct $electronicsProduct): bool
    {
        return false;
    }
}
