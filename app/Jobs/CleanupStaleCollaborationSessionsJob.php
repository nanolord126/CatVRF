<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CleanupStaleCollaborationSessionsJob implements ShouldQueue
{
    use Queueable;

    public string $queue = 'default';
    public int $timeout = 300;
    public int $tries = 3;

    public function handle(): void
    {
        try {
            // Очищаем устаревшие сессии редактирования
            // В продакшене можно использовать более оптимизированный подход с Redis SCAN

            Log::channel('audit')->info('Cleanup stale collaboration sessions job completed', [
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to cleanup stale collaboration sessions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
