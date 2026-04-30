<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

use App\Domains\Hotels\Models\PricingRule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * PricingRulePolicy — Политика авторизации для тарифных правил.
 *
 * Управление ценовыми правилами (сезонные, акционные, B2B-тарифы)
 * доступно только владельцу отеля и администратору.
 */
final class PricingRulePolicy
{
    /**
     * Может ли пользователь просматривать список тарифных правил.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id !== null
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Требуется авторизация');
    }

    /**
     * Может ли пользователь просматривать конкретное правило.
     */
    public function $this->viewFactory->make(User $user, PricingRule $rule): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $rule->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этому тарифному правилу');
    }

    /**
     * Может ли пользователь создавать тарифные правила.
     */
    public function create(User $user): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id !== null
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Недостаточно прав');
    }

    /**
     * Может ли пользователь обновлять тарифное правило.
     */
    public function update(User $user, PricingRule $rule): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $rule->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этому тарифному правилу');
    }

    /**
     * Может ли пользователь удалять тарифное правило.
     */
    public function delete(User $user, PricingRule $rule): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может удалить тарифное правило');
    }

    /**
     * Отладочный массив.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'     => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
