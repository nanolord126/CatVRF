<?php
namespace App\Policies;
use App\Models\User;
use App\Domains\Clinic\Models\MedicalCard;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicalCardPolicy extends BaseSecurityPolicy {
    use HandlesAuthorization;

    public function viewAny(User $user): bool {
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'clinic-staff', 'doctor']);
    }

    public function view(User $user, MedicalCard $card): bool {
        if ($user->tenant_id !== $card->tenant_id) return false;
        if ($user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor'])) return true;
        if ($user->hasRole('clinic-staff') && $card->clinic_id === $user->clinic_id) return true;
        return $card->patient_id === $user->id;
    }

    public function create(User $user): bool {
        return $user->tenant_id !== null && $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor']);
    }

    public function update(User $user, MedicalCard $card): bool {
        if ($user->tenant_id !== $card->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor']);
    }

    public function addAppointment(User $user, MedicalCard $card): bool {
        if ($user->tenant_id !== $card->tenant_id) return false;
        return $user->hasAnyRole(['admin', 'tenant-owner', 'manager', 'doctor', 'clinic-staff']);
    }

    public function delete(User $user, MedicalCard $card): bool {
        return $user->hasRole('admin') && $user->tenant_id === $card->tenant_id;
    }
}
