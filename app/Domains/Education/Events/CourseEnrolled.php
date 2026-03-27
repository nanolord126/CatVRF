<?php

declare(strict_types=1);

namespace App\Domains\Education\Events;

use App\Domains\Education\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: CourseEnrolled (Education).
 * Событие зачисления студента на курс.
 */
final class CourseEnrolled
{
    use Dispatchable, SerializesModels;

    public string $correlation_id;

    public function __construct(
        public readonly Enrollment $enrollment,
        ?string $correlationId = null
    ) {
        $this->correlation_id = $correlationId ?? (string) Str::uuid();
    }
}
