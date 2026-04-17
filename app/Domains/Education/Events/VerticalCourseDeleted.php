<?php declare(strict_types=1);

namespace App\Domains\Education\Events;

use App\Domains\Education\Models\VerticalCourse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VerticalCourseDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly VerticalCourse $verticalCourse,
    ) {}

    public function getVertical(): string
    {
        return $this->verticalCourse->vertical;
    }
}
