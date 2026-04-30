<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shared\Application\Services\DeadLetterQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Command to show Dead Letter Queue statistics
 */
final class DeadLetterQueueStatsCommand extends Command
{
    public function __construct(
        private readonly DeadLetterQueueService $dlqService,
    ) {
        parent::__construct();
    }

    protected $signature = 'events:dlq-stats';

    protected $description = 'Show Dead Letter Queue statistics';

    public function handle(): int
    {
        $stats = $dlqService->getStats();

        $this->info('Dead Letter Queue Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Events', $stats['total']],
                ['Unprocessed', $stats['unprocessed']],
                ['Processed', $stats['processed']],
                ['Last 24h', $stats['recent_24h']],
                ['Last 7d', $stats['recent_7d']],
            ]
        );

        $this->newLine();
        $this->info('Top Failure Reasons:');
        $this->table(
            ['Reason', 'Count'],
            (new Collection($stats['by_failure_reason']))->map(fn ($count, $reason) => [$reason, $count])->toArray()
        );

        $this->newLine();
        $this->info('Top Event Types:');
        $this->table(
            ['Event Type', 'Count'],
            (new Collection($stats['by_event_type']))->map(fn ($count, $type) => [$type, $count])->toArray()
        );

        return self::SUCCESS;
    }
}
