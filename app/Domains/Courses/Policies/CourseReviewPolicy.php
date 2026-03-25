declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\CourseReview;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * CourseReviewPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourseReviewPolicy
{
    public function viewAny(?User $user): Response
    {
        return $this->response->allow();
    }

    public function view(?User $user, CourseReview $review): Response
    {
        return $this->response->allow();
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, CourseReview $review): Response
    {
        if ($user->id === $review->student_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function delete(User $user, CourseReview $review): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete reviews');
    }
}
