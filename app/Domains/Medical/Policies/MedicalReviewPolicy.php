<?php declare(strict_types=1);

namespace App\Domains\Medical\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalReviewPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
}
