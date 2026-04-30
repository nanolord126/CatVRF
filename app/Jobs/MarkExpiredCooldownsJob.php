<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Security\CooldownService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Mark Expired Cooldowns Job
 * 
 * Scheduled job to mark expired cooldowns as expired.
 * Should run every hour to clean up expired cooldowns.
 */
final class MarkExpiredCooldownsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        // CooldownService will be resolved from container in handle()
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cooldownService = app(CooldownService::class);
        $count = $cooldownService->markExpiredCooldowns();

        if ($count > 0) {
            \Log::info('Expired cooldowns marked', [
                'count' => $count,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }
}
