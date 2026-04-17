<?php declare(strict_types=1);

namespace App\Domains\Education\Policies;

use App\Models\User;
use App\Domains\Education\Models\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;

final readonly class LearningPathPolicy
{
    use HandlesAuthorization;

    public function view(User $user, int $enrollmentId): bool
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        return $enrollment->user_id === $user->id;
    }

    public function generate(User $user, int $courseId): bool
    {
        return $user->can('enroll', \App\Domains\Education\Models\Course::class);
    }

    public function adapt(User $user, int $enrollmentId): bool
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        return $enrollment->user_id === $user->id && $enrollment->progress_percent > 0;
    }

    public function delete(User $user, int $enrollmentId): bool
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);
        return $enrollment->user_id === $user->id && $enrollment->completed_at === null;
    }
}
