<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use App\Domains\Sports\Events\AdaptiveWorkoutGeneratedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class SyncAdaptiveWorkoutToCRMListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private AuditService $audit,
    ) {}

    public function handle(AdaptiveWorkoutGeneratedEvent $event): void
    {
        Log::channel('crm')->info('Syncing adaptive workout to CRM', [
            'user_id' => $event->userId,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            'adaptive_workout_synced_to_crm',
            'sports_adaptive_workout',
            $event->userId,
            [],
            [
                'workout_plan_keys' => array_keys($event->workoutPlan),
                'correlation_id' => $event->correlationId,
            ],
            $event->correlationId
        );
    }

    public function failed(AdaptiveWorkoutGeneratedEvent $event, \Throwable $exception): void
    {
        Log::channel('crm')->error('Failed to sync adaptive workout to CRM', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
