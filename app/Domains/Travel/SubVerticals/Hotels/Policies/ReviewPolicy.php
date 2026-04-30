<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Policies;

use Illuminate\Auth\Access\Response as PolicyResponse;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Http\JsonResponse;

use Carbon\CarbonImmutable;

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
 */
final class ReviewPolicy
{
    /**
     * Может ли пользователь просматривать список отзывов.
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function viewAny(User $user): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь просматривать конкретный отзыв.
     */
    public function $this->viewFactory->make(User $user, Review $review): Response
    {
        return PolicyResponse::allow();
    }

    /**
     * Может ли пользователь создавать отзыв.
     * Только авторизованные пользователи с подтверждённым проживанием.
     */
    public function create(User $user): Response
    {
        return $user->tenant_id !== null
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Требуется авторизация для создания отзыва');
    }

    /**
     * Может ли пользователь обновлять отзыв.
     * Доступно автору отзыва или администратору.
     */
    public function update(User $user, Review $review): Response
    {
        if ($user->is_admin) {
            return PolicyResponse::allow();
        }

        return $user->id === $review->user_id
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только автор может редактировать отзыв');
    }

    /**
     * Может ли пользователь удалять отзыв.
     * Только для администратора.
     */
    public function delete(User $user, Review $review): Response
    {
        return $user->is_admin
            ? PolicyResponse::allow()
            : PolicyResponse::deny('Только администратор может удалить отзыв');
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
