<?php

declare(strict_types=1);

namespace Modules\Payments\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Payments\Infrastructure\Repositories\EloquentIdempotencyRepository;

/**
 * Job: Очистка просроченных idempotency-записей.
 * Запускается ежедневно через Scheduler.
 */
final class CleanupIdempotencyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $tags = ['payments', 'maintenance'];

    public function handle(EloquentIdempotencyRepository $repo): void
    {
        $deleted = $repo->deleteExpired();

        \Illuminate\Support\Facades\Log::channel('audit')->info('idempotency.cleanup', [
            'deleted_count' => $deleted,
        ]);
    }
}
