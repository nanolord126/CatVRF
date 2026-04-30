<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Analytics\Application\DTOs\BehavioralEventDto;
use Modules\Analytics\Application\UseCases\CaptureBehavioralEventUseCase;

/**
 * Process Behavioral Events Job
 *
 * Async job for processing behavioral events in batch.
 * Runs asynchronously to avoid blocking main request flow.
 * Follows production pattern: batch processing, error handling.
 */
final class ProcessBehavioralEventsJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;
    use WithAuditLogging;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param BehavioralEventDto[] $events
     */
    public function __construct(
        public readonly array $events,
    ) {}

    public function handle(
        CaptureBehavioralEventUseCase $captureEventUseCase,
        AuditService $auditService,
    ): void {
        $processedCount = 0;
        $failedCount = 0;

        foreach ($this->events as $eventDto) {
            try {
                $captureEventUseCase->execute($eventDto);
                $processedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                // Continue processing other events
                continue;
            }
        }

        $this->logAction(
            action: 'behavioral_events_processed_batch',
            entityType: 'BehavioralEvent',
            entityId: null,
            context: [
                'total_events' => count($this->events),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
            ],
            userId: null,
            tenantId: null
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->logError(
            operation: 'behavioral_events_processing_failed',
            exception: $exception instanceof \Exception ? $exception : new \Exception($exception->getMessage()),
            context: [
                'total_events' => count($this->events),
            ]
        );
    }
}
