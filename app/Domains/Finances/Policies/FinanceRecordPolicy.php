<?php

declare(strict_types=1);

namespace App\Domains\Finances\Policies;

use App\Domains\Finances\Models\FinanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Политика доступа к финансовым записям.
 *
 * Tenant-scoped: пользователь имеет доступ только
 * к записям своего тенанта.
 * B2B-scoped: дополнительная проверка business_group_id
 * для B2B-пользователей.
 *
 * @package App\Domains\Finances\Policies
 */
final class FinanceRecordPolicy
{
    /**
     * Может ли пользователь просматривать список.
     */
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь просматривать конкретную запись.
     *
     * Проверяет tenant + business_group изоляцию.
     */
    public function view(User $user, FinanceRecord $financeRecord): Response
    {
        if ($user->tenant_id !== $financeRecord->tenant_id) {
            return Response::deny('Нет доступа к записям другого тенанта.');
        }

        if (! $this->canAccessBusinessGroup($user, $financeRecord)) {
            return Response::deny('Нет доступа к записям этого филиала.');
        }

        return Response::allow();
    }

    /**
     * Может ли пользователь создавать записи.
     */
    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    /**
     * Может ли пользователь обновлять запись.
     */
    public function update(User $user, FinanceRecord $financeRecord): Response
    {
        if ($user->tenant_id !== $financeRecord->tenant_id) {
            return Response::deny('Нельзя редактировать записи другого тенанта.');
        }

        if (! $this->canAccessBusinessGroup($user, $financeRecord)) {
            return Response::deny('Нельзя редактировать записи этого филиала.');
        }

        return Response::allow();
    }

    /**
     * Может ли пользователь удалять запись.
     *
     * Финансовые записи со статусом completed нельзя удалять.
     */
    public function delete(User $user, FinanceRecord $financeRecord): Response
    {
        if ($user->tenant_id !== $financeRecord->tenant_id) {
            return Response::deny('Нельзя удалять записи другого тенанта.');
        }

        if (! $this->canAccessBusinessGroup($user, $financeRecord)) {
            return Response::deny('Нельзя удалять записи этого филиала.');
        }

        if (($financeRecord->status ?? '') === 'completed') {
            return Response::deny('Нельзя удалять завершённые финансовые записи.');
        }

        return Response::allow();
    }

    /**
     * Может ли пользователь восстановить запись.
     */
    public function restore(User $user, FinanceRecord $financeRecord): Response
    {
        if ($user->tenant_id !== $financeRecord->tenant_id) {
            return Response::deny('Нельзя восстанавливать записи другого тенанта.');
        }

        if (! $this->canAccessBusinessGroup($user, $financeRecord)) {
            return Response::deny('Нельзя восстанавливать записи этого филиала.');
        }

        return Response::allow();
    }

    /**
     * Жёсткое удаление финансовых записей запрещено.
     */
    public function forceDelete(User $user, FinanceRecord $financeRecord): bool
    {
        return false;
    }

    /**
     * Проверка B2B-изоляции business_group.
     *
     * Если у пользователя есть active_business_group_id,
     * запись должна принадлежать этому же филиалу.
     * Если у записи нет business_group_id — доступна всем.
     */
    private function canAccessBusinessGroup(User $user, FinanceRecord $financeRecord): bool
    {
        $userGroup = $user->active_business_group_id ?? null;
        $recordGroup = $financeRecord->business_group_id ?? null;

        if ($recordGroup === null) {
            return true;
        }

        if ($userGroup === null) {
            return true;
        }

        return $userGroup === $recordGroup;
    }
}
