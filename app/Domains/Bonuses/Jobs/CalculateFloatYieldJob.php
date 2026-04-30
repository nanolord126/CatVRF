<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Services\FloatYieldService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * CalculateFloatYieldJob - Job for calculating float yield
 *
 * Runs daily to calculate and credit yield from locked bonus float.
 * Platform earns ~0.018% daily (1.8% monthly), users earn ~0.012% daily (1.2% monthly).
 *
 * Uses chunked processing for performance with large datasets.
 */
final class CalculateFloatYieldJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes

    private ?int $tenantId;
    private ?string $date;

    public function __construct(?int $tenantId = null, ?string $date = null)
    {
        $this->tenantId = $tenantId;
        $this->date = $date;
    }

    public function handle(
        FloatYieldService $yieldService,
        LoggerInterface $logger,
    ): void {
        $logger->info('Starting float yield calculation job', [
            'tenant_id' => $this->tenantId,
            'date' => $this->date,
        ]);

        $results = $yieldService->processDailyYield($this->date);

        $logger->info('Float yield calculation job completed', [
            'tenant_id' => $this->tenantId,
            'date' => $results['date'],
            'processed_users' => $results['processed_users'],
            'total_float' => $results['total_float'],
            'total_user_yield' => $results['total_user_yield'],
            'total_platform_yield' => $results['total_platform_yield'],
            'errors_count' => count($results['errors']),
        ]);

        if (!empty($results['errors'])) {
            $logger->warning('Some users failed during float yield calculation', [
                'errors' => $results['errors'],
            ]);
        }
    }
}
