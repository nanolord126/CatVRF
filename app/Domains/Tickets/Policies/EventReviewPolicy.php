declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Tickets\Policies;

use App\Domains\Tickets\Models\EventReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * EventReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EventReviewPolicy
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
}
