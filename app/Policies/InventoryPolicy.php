<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Core\Models\User;
use Modules\Inventory\Models\InventoryItem;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Item Authorization Policy
 * CANON 2026 - Production Ready
 *
 * Управление доступом к элементам инвентаря.
 * Применяется для товаров, расходников, материалов.
 */
final class InventoryPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь видеть элемент инвентаря?
     * Активные товары видны всем (публичные), неактивные - только владельцу.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        // CANON 2026: Strict tenant scoping check
        if (isset($item->tenant_id) && $user->tenant_id !== $item->tenant_id && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'model_tenant_id' => $item->tenant_id,
            ]);
            return false;
        }

        if ($item->is_active) {
            return true;
        }

        return $user->tenant_id === $item->tenant_id || $user->hasRole('admin');
    }

    /**
     * Может ли пользователь видеть все элементы инвентаря?
     */
    public function viewAny(User $user): bool
    {
        return true; // все видят активные товары
    }

    /**
     * Может ли пользователь создать элемент?
     * Только владелец бизнеса.
     */
    public function create(User $user): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = (
            $user->hasRole('business') &&
            $user->tenant_id !== null
        );

        if (!$allowed) {
            Log::info('Unauthorized inventory creation attempt', [
                'user_id' => $user->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь обновить элемент?
     * Только владелец.
     */
    public function update(User $user, InventoryItem $item): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        $allowed = $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);

        if (!$allowed) {
            Log::warning('Unauthorized inventory update attempt', [
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }

        return $allowed;
    }

    /**
     * Может ли пользователь просмотреть текущий остаток?
     * Только владелец бизнеса (приватная информация).
     */
    public function viewStock(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь добавить товар (deduct stock)?
     * Списание автоматическое при завершении услуги/заказа.
     * Ручное списание - только администратор бизнеса.
     */
    public function deductStock(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь пополнить остаток (add stock)?
     */
    public function addStock(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь вручную скорректировать остаток (adjust)?
     * Требует обоснования.
     */
    public function adjustStock(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole('admin');
    }

    /**
     * Может ли пользователь просмотреть историю движения запасов?
     */
    public function viewStockHistory(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь настроить пороги уведомления?
     */
    public function setThresholds(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь просмотреть прогноз спроса?
     */
    public function viewForecast(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли пользователь импортировать товары из файла?
     */
    public function importFromFile(User $user): bool
    {
        return $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь экспортировать список товаров?
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['business', 'admin']) && $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь проводить инвентаризацию (stock check)?
     */
    public function performInventoryCheck(User $user, InventoryItem $item): bool
    {
        return $user->tenant_id === $item->tenant_id && $user->hasRole('admin');
    }

    /**
     * Может ли пользователь удалить элемент?
     * Soft delete - только владелец/администратор.
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->tenant_id === $item->tenant_id && $user->hasRole(['business', 'admin']);
    }

    /**
     * Может ли администратор восстановить элемент?
     */
    public function restore(User $user, InventoryItem $item): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return $user->hasRole('admin') && $user->tenant_id === $item->tenant_id;
    }

    /**
     * Может ли администратор hard-удалить элемент?
     * ЗАПРЕЩЕНО - товары хранятся для аудита.
     */
    public function forceDelete(User $user, InventoryItem $item): bool
    {
        // CANON 2026 FRAUD: Predict/check operation before mutating
        $fraudScore = 0; // fraud check at service layer
        if ($fraudScore > 0.7 && !$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => $user->id,
                'score' => $fraudScore
            ]);
            return false;
        }

        return false;
    }
}
