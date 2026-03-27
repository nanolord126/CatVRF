<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Events;

use App\Domains\Education\Courses\Models\LessonProgress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final /**
 * LessonCompleted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class LessonCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly LessonProgress $lessonProgress,
        public readonly string $correlationId = '',
    ) {}
}
