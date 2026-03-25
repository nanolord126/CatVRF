declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Policies;

use App\Domains\Sports\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * ReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReviewPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, Review $review): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, Review $review): Response
    {
        return ($user->id === $review->reviewer_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }

    public function delete(User $user, Review $review): Response
    {
        return ($user->id === $review->reviewer_id || $user->hasRole('admin')) ? $this->response->allow() : $this->response->deny();
    }
}
