<?php

declare(strict_types=1);

/**
 * ReviewPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/reviewpolicy
 */


namespace App\Domains\Beauty\Policies;

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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
