declare(strict_types=1);

<?php

namespace App\Domains\Beauty\Policies;

use App\Models\User;
use App\Domains\Beauty\Models\Salon;

/**
 * SalonPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class SalonPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Salon $salon): bool
    {
        return $user->tenant_id === $salon->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id && $user->tenant_id === $salon->tenant_id;
    }

    public function delete(User $user, Salon $salon): bool
    {
        return $user->id === $salon->owner_id && $user->tenant_id === $salon->tenant_id;
    }
}
