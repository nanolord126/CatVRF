<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика для отзывов.
 * Production 2026.
 */
final class ReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Review $review): bool
    {
        return true; // Отзывы видимы всем
    }

    public function create(User $user): bool
    {
        return $user->id !== null; // Только авторизованные пользователи
    }

    public function update(User $user, Review $review): bool
    {
        return $review->author_id === $user->id && $user->can('update_reviews');
    }

    public function delete(User $user, Review $review): bool
    {
        return (
            $review->author_id === $user->id || // Автор может удалить
            $user->can('delete_reviews')
        );
    }

    public function restore(User $user, Review $review): bool
    {
        return $user->can('restore_reviews');
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->can('force_delete_reviews');
    }
}
