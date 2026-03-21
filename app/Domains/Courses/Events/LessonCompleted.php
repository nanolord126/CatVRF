<?php declare(strict_types=1);

namespace App\Domains\Courses\Events;

use App\Domains\Courses\Models\LessonProgress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LessonCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LessonProgress $lessonProgress,
        public readonly string $correlationId = '',
    ) {}
}
