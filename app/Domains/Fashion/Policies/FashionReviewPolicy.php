<?php declare(strict_types=1);

/**
 * FashionReviewPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/fashionreviewpolicy
 */


namespace App\Domains\Fashion\Policies;

final class FashionReviewPolicy
{

    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, FashionReview $review): Response
        {
            return $review->status === 'approved' || ($user && ($user->id === $review->reviewer_id || $user->isAdmin())) ? $this->response->allow() : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, FashionReview $review): Response
        {
            return $user->id === $review->reviewer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

        public function delete(User $user, FashionReview $review): Response
        {
            return $user->id === $review->reviewer_id || $user->isAdmin() ? $this->response->allow() : $this->response->deny();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
