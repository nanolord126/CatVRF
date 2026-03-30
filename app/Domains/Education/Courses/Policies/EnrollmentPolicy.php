<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Policies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EnrollmentPolicy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
