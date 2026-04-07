<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Policies;

use App\Domains\Hotels\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\Response;

/**
 * ReviewPolicy — Политика авторизации для отзывов.
 *
 * Просмотр отзывов открыт для всех авторизованных пользователей.
 * Создание — только гости, прошедшие проживание.
 * Редактирование — автор или администратор.
 * Удаление — только администратор.
 *
 * @package App\Domains\Hotels\Policies
 */
final class ReviewPolicy
{
    /**
     * Может ли пользователь просматривать список отзывов.
     */
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный отзыв.
     */
    public function view(User $user, Review $review): Response
    {
        return Response::allow();
    }

    /**
     * Может ли пользователь создавать отзыв.
     * Только авторизованные пользователи с подтверждённым проживанием.
     */
    public function create(User $user): Response
    {
        return $user->tenant_id !== null
            ? Response::allow()
            : Response::deny('Требуется авторизация для создания отзыва');
    }

    /**
     * Может ли пользователь обновлять отзыв.
     * Доступно автору отзыва или администратору.
     */
    public function update(User $user, Review $review): Response
    {
        if ($user->is_admin) {
            return Response::allow();
        }

        return $user->id === $review->user_id
            ? Response::allow()
            : Response::deny('Только автор может редактировать отзыв');
    }

    /**
     * Может ли пользователь удалять отзыв.
     * Только для администратора.
     */
    public function delete(User $user, Review $review): Response
    {
        return $user->is_admin
            ? Response::allow()
            : Response::deny('Только администратор может удалить отзыв');
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
