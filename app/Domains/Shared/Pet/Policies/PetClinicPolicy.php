<?php

declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use FraudControlService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Domains\Pet\Models\PetClinic;
use App\Models\User;
use App\Services\FraudControlService;

/**
 * Class PetClinicPolicy
 *
 * Part of the Pet vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Authorization policy for resource access control.
 * Enforces tenant-scoped permissions.
 * Integrates with B2C/B2B role system.
 */
final class PetClinicPolicy
{
    /**
     * Handle viewAny operation.
     *
     * @throws \DomainException
     */
    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly ViewFactory $viewFactory,) {}

    public function viewAny(User $user): bool
    {
        return $user->can('view_pet_clinics');
    }

    /**
     * Handle view operation.
     *
     * @throws \DomainException
     */
    public function $this->viewFactory->make(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('view_pet_clinics');
    }

    public function create(User $user): bool
    {
        if (! $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */->shouldBlock(0.1, 'create_clinic')) {
            return $user->can('manage_pet_clinics');
        }

        return false;
    }

    public function update(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('manage_pet_clinics');
    }

    public function delete(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('delete_pet_clinics');
    }
}
