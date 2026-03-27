<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Events;

use App\Domains\Education\Courses\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * EnrollmentCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class EnrollmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly string $correlationId = '',
    ) {}
}
