<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\Food\Models\FoodItem;

final class FoodItemPolicy
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
    public function $this->viewFactory->make(User $user, FoodItem $foodItem): bool
    {
        return $user->tenant_id === $foodItem->tenant_id;
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
    public function update(User $user, FoodItem $foodItem): bool
    {
        return $user->tenant_id === $foodItem->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FoodItem $foodItem): bool
    {
        return $user->tenant_id === $foodItem->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FoodItem $foodItem): bool
    {
        return $user->tenant_id === $foodItem->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FoodItem $foodItem): bool
    {
        return false;
    }
}
