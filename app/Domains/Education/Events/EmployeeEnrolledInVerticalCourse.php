<?php declare(strict_types=1);

namespace App\Domains\Education\Events;

use App\Domains\Education\Models\Enrollment;
use App\Domains\Education\Models\VerticalCourse;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EmployeeEnrolledInVerticalCourse
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly VerticalCourse $verticalCourse,
        public readonly Enrollment $enrollment,
        public readonly string $correlationId,
    ) {}

    public function getVertical(): string
    {
        return $this->verticalCourse->vertical;
    }

    public function getTargetRole(): ?string
    {
        return $this->verticalCourse->target_role;
    }

    public function getDifficultyLevel(): string
    {
        return $this->verticalCourse->difficulty_level;
    }
}
