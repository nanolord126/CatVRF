<?php declare(strict_types=1);

namespace App\Domains\Pet\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
