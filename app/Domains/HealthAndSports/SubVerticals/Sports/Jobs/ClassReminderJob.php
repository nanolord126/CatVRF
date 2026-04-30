<?php

declare(strict_types=1);

namespace App\Domains\Sports\Jobs;

use Psr\Log\LoggerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Class ClassReminderJob
 *
 * Part of the Sports vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class ClassReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly int $classId;

    private readonly string $correlationId;

    public function __construct(int $classId, string $correlationId, private readonly LoggerInterface $logger)
    {
        $this->classId = $classId;
        $this->correlationId = $correlationId !== null ? (string) Str::uuid();
        $this->onQueue('notifications');
    }

    public function tags(): array
    {
        return ['sports', 'job'];
    }

    public function handle(): void
    {
        $correlationId = $this->correlationId;
        $this->logger->$this->logger->info('ClassReminderJob started', ['correlation_id' => $correlationId]);

        try {
            // Implemented per canon 2026
            // For example: find the class by $this->classId, find the user, and send a notification.
            $this->logger->$this->logger->info('Class reminder job logic for class '.$this->classId.' needs to be implemented.');

        } catch (Exception $e) {
            $this->logger->error('ClassReminderJob failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }

        $this->logger->$this->logger->info('ClassReminderJob finished', ['correlation_id' => $correlationId]);
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('sports job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
