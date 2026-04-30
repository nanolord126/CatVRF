<?php

declare(strict_types=1);

namespace App\Domains\Education\Policies;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Models\User;
use App\Domains\Education\Models\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Domains\Education\Models\Course;

final readonly class LearningPathPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function $this->viewFactory->make(User $user, int $enrollmentId): bool
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        return $enrollment->user_id === $user->id;
    }

    public function generate(User $user, int $courseId): bool
    {
        return $user->can('enroll', Course::class);
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
