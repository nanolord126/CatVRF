<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

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
 *
 * @package App\Domains\Hotels\Policies
 */
final class RoomTypePolicy
{
    /**
     * Может ли пользователь просматривать список типов номеров.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный тип номера.
     */
    public function view(User $user, RoomType $roomType): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь создавать типы номеров.
     */
    public function create(User $user): Response
    {
        return $user->can('create_room_types') || $user->is_admin
            ? Response::allow()
            : Response::deny('Недостаточно прав для создания типа номера');
    }

    /**
     * Может ли пользователь обновлять тип номера.
     */
    public function update(User $user, RoomType $roomType): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id === $roomType->tenant_id
            ? Response::allow()
            : Response::deny('Нет доступа к этому типу номера');
    }

    /**
     * Может ли пользователь удалять тип номера.
     */
    public function delete(User $user, RoomType $roomType): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('Только администратор может удалить тип номера');
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
