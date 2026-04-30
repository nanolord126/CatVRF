<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\OfficeCatering\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\OfficeCatering\Models\CateringCompany;

final class CateringCompanyPolicy
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
    public function $this->viewFactory->make(User $user, CateringCompany $cateringCompany): bool
    {
        return $user->tenant_id === $cateringCompany->tenant_id;
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
    public function update(User $user, CateringCompany $cateringCompany): bool
    {
        return $user->tenant_id === $cateringCompany->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CateringCompany $cateringCompany): bool
    {
        return $user->tenant_id === $cateringCompany->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CateringCompany $cateringCompany): bool
    {
        return $user->tenant_id === $cateringCompany->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CateringCompany $cateringCompany): bool
    {
        return false;
    }
}
