<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

final class EventReviewPolicy
{

    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, EventReview $review): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, EventReview $review): Response
        {
            if ($user->id === $review->buyer_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
        }

        public function delete(User $user, EventReview $review): Response
        {
            return $user->isAdmin()
                ? $this->response->allow()
                : $this->response->deny('Only admins can delete reviews');
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
