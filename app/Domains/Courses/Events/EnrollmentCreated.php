<?php declare(strict_types=1);

namespace App\Domains\Courses\Events;

use App\Domains\Courses\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EnrollmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly string $correlationId = '',
    ) {}
}
