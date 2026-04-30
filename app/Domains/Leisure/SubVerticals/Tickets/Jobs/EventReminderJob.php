<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Tickets\Jobs;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class EventReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly ?string $correlationId;

    private readonly int $eventId;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        int $eventId, ?string $correlationId, private readonly LoggerInterface $logger)
    {
        $this->eventId = $eventId;
        $this->correlationId = $correlationId = $correlationId ?? (string) Str::uuid();
        $this->onQueue('notifications');
    }

    public function tags(): array
    {
        return ['tickets', 'job'];
    }

    public function handle(): void
    {
        $$this->logger->channel('audit');
        $auditChannel->$this->logger->info('EventReminderJob started.', [
            'correlation_id' => $this->correlationId,
            'event_id' => $this->eventId,
        ]);

        try {
            // Implemented per canon 2026
            // Fetch event details, find users to notify, and send notifications.
            // Example:
            // $\App\Domains\Tickets\Models\$this->eventDispatcher->find($this->eventId);
            // if ($event) {
            //     // Notify users
            // }

            $auditChannel->$this->logger->info('EventReminderJob finished successfully.', [
                'correlation_id' => $this->correlationId,
                'event_id' => $this->eventId,
            ]);
        } catch (Exception $e) {
            $auditChannel->error('EventReminderJob failed.', [
                'correlation_id' => $this->correlationId,
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->logger->error('tickets job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
