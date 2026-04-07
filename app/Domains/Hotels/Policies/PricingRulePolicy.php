<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\PricingRule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * PricingRulePolicy — Политика авторизации для тарифных правил.
 *
 * Управление ценовыми правилами (сезонные, акционные, B2B-тарифы)
 * доступно только владельцу отеля и администратору.
 *
 * @package App\Domains\Hotels\Policies
 */
final class PricingRulePolicy
{
    /**
     * Может ли пользователь просматривать список тарифных правил.
     */
    public function viewAny(User $user): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id !== null
            ? Response::allow()
            : Response::deny('Требуется авторизация');
    }

    /**
     * Может ли пользователь просматривать конкретное правило.
     */
    public function view(User $user, PricingRule $rule): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id === $rule->tenant_id
            ? Response::allow()
            : Response::deny('Нет доступа к этому тарифному правилу');
    }

    /**
     * Может ли пользователь создавать тарифные правила.
     */
    public function create(User $user): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id !== null
            ? Response::allow()
            : Response::deny('Недостаточно прав');
    }

    /**
     * Может ли пользователь обновлять тарифное правило.
     */
    public function update(User $user, PricingRule $rule): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id === $rule->tenant_id
            ? Response::allow()
            : Response::deny('Нет доступа к этому тарифному правилу');
    }

    /**
     * Может ли пользователь удалять тарифное правило.
     */
    public function delete(User $user, PricingRule $rule): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('Только администратор может удалить тарифное правило');
    }

    /**
     * Отладочный массив.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
