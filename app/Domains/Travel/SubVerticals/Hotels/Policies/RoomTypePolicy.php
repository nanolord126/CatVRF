<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

use App\Domains\Hotels\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * RoomTypePolicy — Политика авторизации для типов номеров.
 *
 * Управление типами номеров (создание, обновление, удаление)
 * доступно владельцу отеля (по tenant_id) и администратору.
 * Просмотр открыт для всех авторизованных.
 */
final class RoomTypePolicy
{
    /**
     * Может ли пользователь просматривать список типов номеров.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный тип номера.
     */
    public function $this->viewFactory->make(User $user, RoomType $roomType): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь создавать типы номеров.
     */
    public function create(User $user): Response
    {
        return $user->can('create_room_types') || $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Недостаточно прав для создания типа номера');
    }

    /**
     * Может ли пользователь обновлять тип номера.
     */
    public function update(User $user, RoomType $roomType): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->tenant_id === $roomType->tenant_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Нет доступа к этому типу номера');
    }

    /**
     * Может ли пользователь удалять тип номера.
     */
    public function delete(User $user, RoomType $roomType): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может удалить тип номера');
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
