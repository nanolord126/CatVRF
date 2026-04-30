<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\PublishOutboxMessagesJob;
use Illuminate\Console\Command;

/**
 * Manual command to publish pending outbox messages
 * Useful for recovery and manual intervention
 */
final class PublishOutboxCommand extends Command
{
    protected $signature = 'outbox:publish
                            {--queue= : Specific queue to process}
                            {--priority= : Minimum priority to process}
                            {--force : Force processing without waiting}';

    protected $description = 'Publish pending outbox messages to event bus';

    public function handle(): int
    {
        $queue = $this->option('queue');
        $priority = $this->option('priority') ? (int) $this->option('priority') : null;

        $this->info('Publishing outbox messages...');

        $job = new PublishOutboxMessagesJob($queue, $priority);

        if ($this->option('force')) {
            $job->dispatchSync();
        } else {
            $job->dispatch();
        }

        $this->info('Job dispatched successfully');

        return self::SUCCESS;
    }
}
