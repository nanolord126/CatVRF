<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Application\Services\DeadLetterQueueService;
use App\Shared\Infrastructure\Persistence\DeadLetterQueue;
use Illuminate\Console\Command;

/**
 * Command to retry events from Dead Letter Queue
 */
final class RetryDeadLetterEventsCommand extends Command
{
    public function __construct(
        private readonly DeadLetterQueueService $dlqService,
    ) {
        parent::__construct();
    }

    protected $signature = 'events:retry-dlq
                            {--id= : Specific DLQ event ID to retry}
                            {--all : Retry all unprocessed events}
                            {--reason= : Filter by failure reason}
                            {--limit=50 : Limit number of events to retry}
                            {--dry-run : Show what would be retried without executing}';

    protected $description = 'Retry events from Dead Letter Queue';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = DeadLetterQueue::unprocessed();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($reason = $this->option('reason')) {
            $query->where('failure_reason', $reason);
        }

        if (! $this->option('id')) {
            $query->limit($limit);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('No events to retry');

            return self::SUCCESS;
        }

        $this->info("Found {$events->count()} events to retry");

        if ($dryRun) {
            foreach ($events as $event) {
                $this->line("  Would retry: {$event->event_type} ({$event->id}) - {$event->failure_reason}");
            }

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($events->count());
        $progressBar->start();

        $retried = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                if ($dlqService->retryEvent($event->id)) {
                    $retried++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error("Failed to retry {$event->id}: {$e->getMessage()}");
                $failed++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Retried', $retried],
                ['Failed', $failed],
                ['Total', $events->count()],
            ]
        );

        return self::SUCCESS;
    }
}
