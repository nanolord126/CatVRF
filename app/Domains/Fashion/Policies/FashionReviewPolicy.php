declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Fashion\Policies;

use App\Domains\Fashion\Models\FashionReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * FashionReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionReviewPolicy
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
}
