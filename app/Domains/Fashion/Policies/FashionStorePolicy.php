<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Models\User;
use App\Domains\Fashion\Models\FashionStore;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * КАНЬОН 2026 — FASHION STORE POLICY
 * 
 * Изоляция данных на уровне tenant_id. 
 * Проверка ИНН для B2B действий.
 */
final class FashionStorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_fashion');
    }

    public function view(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        // Проверка через Fraud ML перед созданием магазина
        if (!app(\App\Services\FraudControlService::class)->shouldBlock(0.1, 'create_fashion_store')) {
             return $user->can('manage_fashion');
        }

        return false;
    }

    public function update(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id && $user->can('manage_fashion');
    }

    public function delete(User $user, FashionStore $store): bool
    {
        return $store->tenant_id === $user->tenant_id && $user->isAdmin();
    }
}

    public function create(User $user): Response
    {
        return $user->hasPermission('create_fashion_store') ? $this->response->allow() : $this->response->deny();
    }

    public function update(User $user, FashionStore $store): Response
    {
        return $user->id === $store->owner_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, FashionStore $store): Response
    {
        return $user->isAdmin() ? $this->response->allow() : $this->response->deny();
    }
}
