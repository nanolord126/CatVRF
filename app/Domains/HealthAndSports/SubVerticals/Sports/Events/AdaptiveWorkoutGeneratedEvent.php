<?php

declare(strict_types=1);

namespace App\Domains\Sports\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Sports\DTOs\AdaptiveWorkoutPlanDto;

final class AdaptiveWorkoutGeneratedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly AdaptiveWorkoutPlanDto $dto,
        public readonly array $workoutPlan,
        public readonly string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [];
    }
}
