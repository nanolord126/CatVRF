declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Courses\Policies;

use App\Domains\Courses\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * EnrollmentPolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EnrollmentPolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Enrollment $enrollment): Response
    {
        if ($user->id === $enrollment->student_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        if ($user->id === $enrollment->course->instructor_id) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function create(User $user): Response
    {
        return $this->response->allow();
    }

    public function update(User $user, Enrollment $enrollment): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can update enrollments');
    }

    public function delete(User $user, Enrollment $enrollment): Response
    {
        return $user->isAdmin()
            ? $this->response->allow()
            : $this->response->deny('Only admins can delete enrollments');
    }
}
