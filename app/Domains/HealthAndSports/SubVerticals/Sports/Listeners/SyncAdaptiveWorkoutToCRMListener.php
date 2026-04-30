<?php

declare(strict_types=1);

namespace App\Domains\Sports\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\Sports\Events\AdaptiveWorkoutGeneratedEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Log\LogManager;

final class SyncAdaptiveWorkoutToCRMListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly LogManager $log,) {}

    public function handle(AdaptiveWorkoutGeneratedEvent $event): void
    {
        $this->log->channel('crm')->$this->logger->info('Syncing adaptive workout to CRM', [
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
        $this->log->channel('crm')->error('Failed to sync adaptive workout to CRM', [
            'user_id' => $event->userId,
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}
