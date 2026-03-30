<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
