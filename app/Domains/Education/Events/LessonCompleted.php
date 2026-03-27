<?php

declare(strict_types=1);

namespace App\Domains\Education\Events;

use App\Domains\Education\Models\Lesson;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: LessonCompleted (Education).
 * Событие завершения урока студентом.
 */
final class LessonCompleted
{
    use Dispatchable, SerializesModels;

    public string $correlation_id;

    public function __construct(
        public readonly int $userId,
        public readonly Lesson $lesson,
        ?string $correlationId = null
    ) {
        $this->correlation_id = $correlationId ?? (string) Str::uuid();
    }
}
