<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CoursePolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function viewAny(?User $user): Response
        {
            return $this->response->allow();
        }

        public function view(?User $user, Course $course): Response
        {
            return $this->response->allow();
        }

        public function create(User $user): Response
        {
            return $user->hasPermission('courses.create')
                ? $this->response->allow()
                : $this->response->deny('Unauthorized');
        }

        public function update(User $user, Course $course): Response
        {
            if ($user->id === $course->instructor_id || $user->isAdmin()) {
                return $this->response->allow();
            }

            return $this->response->deny('Unauthorized');
        }

        public function delete(User $user, Course $course): Response
        {
            return $user->isAdmin()
                ? $this->response->allow()
                : $this->response->deny('Only admins can delete courses');
        }
}
