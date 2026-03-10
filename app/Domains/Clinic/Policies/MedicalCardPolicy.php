<?php

declare(strict_types=1);

namespace App\Domains\Clinic\Policies;

use App\Models\User;
use App\Domains\Clinic\Models\MedicalCard;
use Illuminate\Auth\Access\HandlesAuthorization;

final class MedicalCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor', 'nurse']) &&
               $user->tenant_id !== null;
    }

    public function view(User $user, MedicalCard $card): bool
    {
        if ($user->tenant_id !== $card->tenant_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor', 'nurse'])) {
            return true;
        }

        return $card->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null &&
               $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor']);
    }

    public function update(User $user, MedicalCard $card): bool
    {
        if ($user->tenant_id !== $card->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor']);
    }

    public function delete(User $user, MedicalCard $card): bool
    {
        if ($user->tenant_id !== $card->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function restore(User $user, MedicalCard $card): bool
    {
        if ($user->tenant_id !== $card->tenant_id) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager']);
    }

    public function forceDelete(User $user, MedicalCard $card): bool
    {
        if ($user->tenant_id !== $card->tenant_id) {
            return false;
        }

        return $user->hasRole('admin');
    }
}
