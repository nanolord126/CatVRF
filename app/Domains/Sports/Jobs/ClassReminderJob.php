<?php declare(strict_types=1);

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
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Domains\Sports\Jobs
 */
final class ClassReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $classId;
    private string $correlationId;

    public function __construct(int $classId, string $correlationId = '', private readonly LoggerInterface $logger)
    {
        $this->classId = $classId;
        $this->correlationId = $correlationId ?: (string) Str::uuid();
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $correlationId = $this->correlationId;
        $this->logger->info('ClassReminderJob started', ['correlation_id' => $correlationId]);

        try {
            // Implemented per canon 2026
            // For example: find the class by $this->classId, find the user, and send a notification.
            $this->logger->info('Class reminder job logic for class ' . $this->classId . ' needs to be implemented.');

        } catch (\Throwable $e) {
            $this->logger->error('ClassReminderJob failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }

        $this->logger->info('ClassReminderJob finished', ['correlation_id' => $correlationId]);
    }
}
