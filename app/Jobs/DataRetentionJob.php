<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Warehouse\Domain\Services\DataRetentionService;

/**
 * Data Retention Job
 * 
 * Scheduled job for automatic data cleanup per 152-ФZ compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class DataRetentionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly DataRetentionService $dataRetentionService
    ) {}

    public function handle(): void
    {
        try {
            $results = $this->dataRetentionService->runAllRetentionPolicies();

            Log::info('Data retention policies executed', [
                'logs_anonymized' => $results['logs_anonymized'],
                'movements_anonymized' => $results['movements_anonymized'],
                'inventory_counts_anonymized' => $results['inventory_counts_anonymized'],
                'expired_batches_deleted' => $results['expired_batches_deleted'],
            ]);
        } catch (\Exception $e) {
            Log::error('Data retention job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
