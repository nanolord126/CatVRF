<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

use App\Domains\Hotels\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * BookingPolicy — Политика авторизации для бронирований.
 *
 * Определяет права доступа к CRUD-операциям с бронированиями.
 * Проверяет tenant-scoping, роли и владение бронированием.
 */
final class BookingPolicy
{
    /**
     * Может ли пользователь просматривать список бронирований.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return $user->tenant_id !== null
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Требуется авторизация');
    }

    /**
     * Может ли пользователь просматривать конкретное бронирование.
     * Доступ только владельцу бронирования или администратору.
     */
    public function $this->viewFactory->make(User $user, Booking $booking): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->id === $booking->user_id && $user->tenant_id === $booking->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этому бронированию');
    }

    /**
     * Может ли пользователь создавать бронирования.
     */
    public function create(User $user): Response
    {
        return $user->tenant_id !== null
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Требуется авторизация');
    }

    /**
     * Может ли пользователь отменять бронирование.
     * Отмена доступна владельцу или администратору.
     */
    public function cancel(User $user, Booking $booking): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->id === $booking->user_id && $user->tenant_id === $booking->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только владелец может отменить бронирование');
    }

    /**
     * Может ли пользователь удалять бронирование (soft delete).
     * Только для администратора.
     */
    public function delete(User $user, Booking $booking): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может удалить бронирование');
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
