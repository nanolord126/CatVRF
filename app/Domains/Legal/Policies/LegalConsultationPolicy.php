<?php declare(strict_types=1);

namespace App\Domains\Legal\Policies;

use App\Models\User;
use App\Domains\Legal\Models\LegalConsultation;
final class LegalConsultationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LegalConsultation $legalConsultation): bool
    {
        return $user->tenant_id === $legalConsultation->tenant_id;
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
    public function update(User $user, LegalConsultation $legalConsultation): bool
    {
        return $user->tenant_id === $legalConsultation->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LegalConsultation $legalConsultation): bool
    {
        return $user->tenant_id === $legalConsultation->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, LegalConsultation $legalConsultation): bool
    {
        return $user->tenant_id === $legalConsultation->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, LegalConsultation $legalConsultation): bool
    {
        return false;
    }
}
