<?php declare(strict_types=1);

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Salon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SalonPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Salon $salon): bool
    {
        return $salon->is_active
            || $user->hasRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && !$user->is_blocked
            && $user->hasRole('business_owner');
    }

    public function update(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id
            || $user->hasRole('admin');
    }

    public function delete(User $user, Salon $salon): bool
    {
        if ($salon->appointments()->where('status', '!=', 'completed')->count() > 0) {
            return false;
        }

        return $user->id === $salon->owner_id
            || $user->hasRole('admin');
    }

    public function manageMasters(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id
            || $user->hasRole('admin')
            || $user->hasRole('salon_manager');
    }

    public function manageServices(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id
            || $user->hasRole('admin')
            || $user->hasRole('salon_manager');
    }

    public function manageSlots(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id
            || $user->hasRole('admin')
            || $user->hasRole('salon_manager');
    }
}
