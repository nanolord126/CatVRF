<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\Medical\Models\MedicalRecord;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Medical Record Compliance Policy
 *
 * Extends the base MedicalRecordPolicy with compliance officer support.
 * Compliance officers can view but not modify medical records.
 */
final readonly class MedicalRecordCompliancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any medical records.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('view any medical records')
            || $user->hasRole('medical_compliance')
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view a specific medical record.
     */
    public function $this->viewFactory->make(User $user, MedicalRecord $record): bool
    {
        // Compliance officers can view records within their tenant
        if ($user->hasRole('medical_compliance')) {
            return $user->tenant_id === $record->tenant_id;
        }

        // Admins can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Regular users need explicit permission and tenant match
        return $user->can('view medical records')
            && $user->tenant_id === $record->tenant_id;
    }

    /**
     * Determine if the user can create medical records.
     */
    public function create(User $user): bool
    {
        // Compliance officers CANNOT create records
        if ($user->hasRole('medical_compliance')) {
            return false;
        }

        return $user->can('create medical records') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update a medical record.
     */
    public function update(User $user, MedicalRecord $record): bool
    {
        // Compliance officers CANNOT update records
        if ($user->hasRole('medical_compliance')) {
            return false;
        }

        return ($user->can('update medical records') || $user->hasRole('admin'))
            && $user->tenant_id === $record->tenant_id;
    }

    /**
     * Determine if the user can delete a medical record.
     */
    public function delete(User $user, MedicalRecord $record): bool
    {
        // Compliance officers CANNOT delete records
        if ($user->hasRole('medical_compliance')) {
            return false;
        }

        return ($user->can('delete medical records') || $user->hasRole('admin'))
            && $user->tenant_id === $record->tenant_id;
    }

    /**
     * Determine if the user can restore a medical record.
     */
    public function restore(User $user, MedicalRecord $record): bool
    {
        // Compliance officers CANNOT restore records
        if ($user->hasRole('medical_compliance')) {
            return false;
        }

        return ($user->can('restore medical records') || $user->hasRole('admin'))
            && $user->tenant_id === $record->tenant_id;
    }

    /**
     * Determine if the user can force delete a medical record.
     */
    public function forceDelete(User $user, MedicalRecord $record): bool
    {
        // Compliance officers CANNOT force delete records
        if ($user->hasRole('medical_compliance')) {
            return false;
        }

        return ($user->can('force delete medical records') || $user->hasRole('admin'))
            && $user->tenant_id === $record->tenant_id;
    }
}
