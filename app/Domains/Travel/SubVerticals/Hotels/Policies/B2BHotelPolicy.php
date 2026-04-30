<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * B2BHotelPolicy — Политика авторизации для B2B-операций Hotels.
 *
 * Управление витринами (storefront), заказами и верификацией B2B-клиентов.
 * B2B-операции доступны только верифицированным юрлицам (ИНН + business_card_id)
 * или администраторам платформы.
 */
final class B2BHotelPolicy
{
    /**
     * Может ли пользователь просматривать список B2B-витрин.
     */
    public function viewAny(User $user): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь просматривать конкретную B2B-витрину.
     * Доступно владельцу по tenant_id или администратору.
     *
     * @param  User  $user  Авторизованный пользователь
     * @param  object  $storefront  B2B-витрина (tenant_id scoped)
     */
    public function viewStorefront(User $user, object $storefront): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $storefront->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этой витрине');
    }

    /**
     * Может ли пользователь создавать B2B-витрину.
     * Требуется верификация компании (ИНН + проверка).
     */
    public function createStorefront(User $user): Response
    {
        if ($user->tenant_id === null) {
            return PolicyResponse::deny('Требуется авторизация');
        }

        return $user->has_verified_company
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Требуется верификация компании');
    }

    /**
     * Может ли пользователь обновлять B2B-витрину.
     *
     * @param  User  $user  Авторизованный пользователь
     * @param  object  $storefront  B2B-витрина
     */
    public function updateStorefront(User $user, object $storefront): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $storefront->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этой витрине');
    }

    /**
     * Может ли пользователь просматривать B2B-заказ.
     *
     * @param  User  $user  Авторизованный пользователь
     * @param  object  $order  B2B-заказ
     */
    public function viewOrder(User $user, object $order): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $order->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этому заказу');
    }

    /**
     * Может ли пользователь одобрять B2B-заказ.
     * Только pending-заказы, доступ по tenant_id или администратору.
     *
     * @param  User  $user  Авторизованный пользователь
     * @param  object  $order  B2B-заказ
     */
    public function approveOrder(User $user, object $order): Response
    {
        $hasAccess = $user->is_admin || $user->tenant_id === $order->tenant_id;
        $isPending = ($order->status ?? '') === 'pending';

        if (! $hasAccess) {
            return PolicyResponse::deny('Нет доступа');
        }

        return $isPending
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Одобрение невозможно: заказ не в статусе pending');
    }

    /**
     * Может ли пользователь отклонять B2B-заказ.
     *
     * @param  User  $user  Авторизованный пользователь
     * @param  object  $order  B2B-заказ
     */
    public function rejectOrder(User $user, object $order): Response
    {
        $hasAccess = $user->is_admin || $user->tenant_id === $order->tenant_id;
        $isPending = ($order->status ?? '') === 'pending';

        if (! $hasAccess) {
            return PolicyResponse::deny('Нет доступа');
        }

        return $isPending
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Отклонение невозможно: заказ не в статусе pending');
    }

    /**
     * Может ли пользователь верифицировать ИНН.
     * Только для администратора.
     */
    public function verifyInn(User $user): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может верифицировать ИНН');
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
