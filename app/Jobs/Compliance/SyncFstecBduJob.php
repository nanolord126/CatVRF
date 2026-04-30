<?php

declare(strict_types=1);

namespace App\Jobs\Compliance;

use App\Services\Compliance\FstecBduService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Sync FSTEC BDU Job
 * 
 * Periodically syncs threats and vulnerabilities from FSTEC Threat Database.
 * Should be scheduled to run weekly or monthly.
 * 
 * Schedule: php artisan schedule:run
 * Command: php artisan sync:fstec-bdu
 */
final readonly class SyncFstecBduJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        private readonly ?bool $syncThreats = null,
        private readonly ?bool $syncVulnerabilities = null,
    ) {
        $this->onQueue('compliance');
    }

    public function handle(
        FstecBduService $bduService,
        LoggerInterface $logger
    ): void {
        $syncThreats = $this->syncThreats ?? true;
        $syncVulnerabilities = $this->syncVulnerabilities ?? true;

        $logger->info('Starting FSTEC BDU sync job', [
            'sync_threats' => $syncThreats,
            'sync_vulnerabilities' => $syncVulnerabilities,
        ]);

        try {
            if ($syncThreats && $syncVulnerabilities) {
                $result = $bduService->syncAll();
            } elseif ($syncThreats) {
                $result = ['threats' => $bduService->syncThreats()];
            } elseif ($syncVulnerabilities) {
                $result = ['vulnerabilities' => $bduService->syncVulnerabilities()];
            } else {
                $logger->warning('FSTEC BDU sync job called with nothing to sync');
                return;
            }

            $logger->info('FSTEC BDU sync job completed', [
                'result' => $result,
            ]);

            // Log statistics
            $stats = $bduService->getStatistics();
            $logger->info('FSTEC BDU statistics', [
                'statistics' => $stats,
            ]);
        } catch (\Throwable $e) {
            $logger->error('FSTEC BDU sync job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FSTEC BDU sync job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
