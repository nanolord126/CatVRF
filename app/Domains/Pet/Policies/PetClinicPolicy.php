<?php

declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use App\Models\User;
use App\Domains\Pet\Models\PetClinic;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * КАНЬОН 2026 — ПОЛИТИКА ДОСТУПА ДЛЯ КЛИНИК
 * 
 * Изоляция данных на уровне tenant_id и проверка прав через FraudControlService.
 */
final class PetClinicPolicy
{
    use HandlesAuthorization;

    /**
     * Проверка доступа к просмотру списка (только текущий тенант)
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_pet_clinics');
    }

    /**
     * Просмотр конкретной клиники
     */
    public function view(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('view_pet_clinics');
    }

    /**
     * Создание клиники — требует Fraud Check
     */
    public function create(User $user): bool
    {
        if (!app(\App\Services\FraudControlService::class)->shouldBlock(0.1, 'create_clinic')) {
             return $user->can('manage_pet_clinics');
        }

        return false;
    }

    /**
     * Обновление — строгая проверка владельца тенанта
     */
    public function update(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('manage_pet_clinics');
    }

    /**
     * Удаление — SoftDelete рекомендуется
     */
    public function delete(User $user, PetClinic $clinic): bool
    {
        return $clinic->tenant_id === $user->tenant_id && $user->can('delete_pet_clinics');
    }
}
            : $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $user->hasPermissionTo('pet_clinic_create')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function update(User $user, PetClinic $clinic): Response
    {
        return $clinic->owner_id === $user->id && $clinic->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function delete(User $user, PetClinic $clinic): Response
    {
        return $clinic->owner_id === $user->id && $clinic->tenant_id === $user->current_tenant_id
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }

    public function verify(User $user, PetClinic $clinic): Response
    {
        return $user->hasPermissionTo('pet_clinic_verify')
            ? $this->response->allow()
            : $this->response->deny('Unauthorized');
    }
}
