<?php declare(strict_types=1);

namespace App\Domains\Education\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Education\DTOs\LearningPathRecommendationDto;

final class LearningPathGeneratedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $courseId,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly LearningPathRecommendationDto $recommendation,
        public readonly string $correlationId,
    ) {}
}
