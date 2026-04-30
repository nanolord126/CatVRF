<?php

declare(strict_types=1);

namespace App\Octane\Listeners;

use Laravel\Octane\Events\WorkerErrorOccurred;
use Illuminate\Log\LogManager;

final readonly class LogWorkerError
{
    public function handle(WorkerErrorOccurred $event): void
    {
        $this->log->error('Octane worker error occurred', [
            'exception' => $event->exception->getMessage(),
            'trace' => $event->exception->getTraceAsString(),
        ]);
    }
}
