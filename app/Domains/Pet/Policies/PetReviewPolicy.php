<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

final class PetReviewPolicy
{

    public function viewAny(User $user): Response
        {
            return $this->response->allow();
        }

        public function view(User $user, PetReview $review): Response
        {
            return $review->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function create(User $user): Response
        {
            return $this->response->allow();
        }

        public function update(User $user, PetReview $review): Response
        {
            return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function delete(User $user, PetReview $review): Response
        {
            return $review->reviewer_id === $user->id && $review->tenant_id === $user->current_tenant_id
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function approve(User $user, PetReview $review): Response
        {
            return $user->hasPermissionTo('pet_review_approve')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
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
