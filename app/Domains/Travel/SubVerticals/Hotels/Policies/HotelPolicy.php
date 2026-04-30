<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

use App\Domains\Hotels\Models\Hotel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * HotelPolicy — Политика авторизации для отелей.
 *
 * Определяет права доступа к CRUD-операциям с отелями.
 * Публичные методы (viewAny, view) доступны всем, мутации — владельцу и админу.
 */
final class HotelPolicy
{
    /**
     * Может ли пользователь просматривать список отелей.
     * Доступно всем авторизованным пользователям.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный отель.
     * Доступно всем авторизованным пользователям.
     */
    public function $this->viewFactory->make(User $user, Hotel $hotel): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь создавать отели.
     * Только пользователи с правом create_hotels.
     */
    public function create(User $user): Response
    {
        return $user->can('create_hotels')
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Недостаточно прав для создания отеля');
    }

    /**
     * Может ли пользователь обновлять данные отеля.
     * Доступно администратору или владельцу отеля (tenant match).
     */
    public function update(User $user, Hotel $hotel): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $hotel->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к редактированию отеля');
    }

    /**
     * Может ли пользователь удалять отель.
     * Только для администратора.
     */
    public function delete(User $user, Hotel $hotel): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может удалить отель');
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
