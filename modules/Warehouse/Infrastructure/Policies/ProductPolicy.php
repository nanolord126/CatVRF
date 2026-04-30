<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Просмотр списка товаров
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view products');
    }

    /**
     * Просмотр конкретного товара
     */
    public function view(User $user, string $productId): bool
    {
        if (!$user->can('view products')) {
            return false;
        }

        return true; // Товары могут быть общими для всех tenants
    }

    /**
     * Создание товара
     */
    public function create(User $user): bool
    {
        return $user->can('create products');
    }

    /**
     * Обновление товара
     */
    public function update(User $user, string $productId): bool
    {
        if (!$user->can('edit products')) {
            return false;
        }

        return true;
    }

    /**
     * Удаление товара
     */
    public function delete(User $user, string $productId): bool
    {
        if (!$user->can('delete products')) {
            return false;
        }

        // Запрет удаления товара с остатками
        // TODO: Проверить наличие запасов

        return true;
    }

    /**
     * Создание медицинских препаратов
     */
    public function createPharmaceutical(User $user): bool
    {
        if (!$user->can('create products')) {
            return false;
        }

        return $user->can('create pharmaceutical products');
    }

    /**
     * Создание контролируемых веществ
     */
    public function createControlledSubstance(User $user): bool
    {
        if (!$user->can('create products')) {
            return false;
        }

        return $user->can('create controlled substances');
    }
}
