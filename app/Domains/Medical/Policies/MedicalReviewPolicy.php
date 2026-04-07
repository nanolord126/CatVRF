<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

final class MedicalReviewPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, MedicalReview $review): Response
        {
            return $review->status === 'approved' || $user->id === $review->reviewer_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, MedicalReview $review): Response
        {
            return $user->id === $review->reviewer_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
        }

        public function delete(User $user, MedicalReview $review): Response
        {
            return $user->id === $review->reviewer_id || $user->hasRole('admin')
                ? $this->response->allow()
                : $this->response->deny();
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
