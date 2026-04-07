<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\Hotel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * HotelPolicy — Политика авторизации для отелей.
 *
 * Определяет права доступа к CRUD-операциям с отелями.
 * Публичные методы (viewAny, view) доступны всем, мутации — владельцу и админу.
 *
 * @package App\Domains\Hotels\Policies
 */
final class HotelPolicy
{
    /**
     * Может ли пользователь просматривать список отелей.
     * Доступно всем авторизованным пользователям.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный отель.
     * Доступно всем авторизованным пользователям.
     */
    public function view(User $user, Hotel $hotel): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь создавать отели.
     * Только пользователи с правом create_hotels.
     */
    public function create(User $user): Response
    {
        return $user->can('create_hotels')
            ? Response::allow()
            : Response::deny('Недостаточно прав для создания отеля');
    }

    /**
     * Может ли пользователь обновлять данные отеля.
     * Доступно администратору или владельцу отеля (tenant match).
     */
    public function update(User $user, Hotel $hotel): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->tenant_id === $hotel->tenant_id
            ? Response::allow()
            : Response::deny('Нет доступа к редактированию отеля');
    }

    /**
     * Может ли пользователь удалять отель.
     * Только для администратора.
     */
    public function delete(User $user, Hotel $hotel): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('Только администратор может удалить отель');
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
