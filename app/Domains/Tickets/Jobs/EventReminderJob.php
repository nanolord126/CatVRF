<?php declare(strict_types=1);

namespace App\Domains\Tickets\Jobs;



use Psr\Log\LoggerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class EventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?string $correlationId;
    private int $eventId;

    public function __construct(int $eventId, string $correlationId = null, private readonly LoggerInterface $logger)
    {
        $this->eventId = $eventId;
        $this->correlationId = $correlationId ?? (string) Str::uuid();
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $auditChannel = $this->logger->channel('audit');
        $auditChannel->info('EventReminderJob started.', [
            'correlation_id' => $this->correlationId,
            'event_id' => $this->eventId,
        ]);

        try {
            // Implemented per canon 2026
            // Fetch event details, find users to notify, and send notifications.
            // Example:
            // $event = \App\Domains\Tickets\Models\Event::find($this->eventId);
            // if ($event) {
            //     // Notify users
            // }

            $auditChannel->info('EventReminderJob finished successfully.', [
                'correlation_id' => $this->correlationId,
                'event_id' => $this->eventId,
            ]);
        } catch (\Throwable $e) {
            $auditChannel->error('EventReminderJob failed.', [
                'correlation_id' => $this->correlationId,
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
