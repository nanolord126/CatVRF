<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
