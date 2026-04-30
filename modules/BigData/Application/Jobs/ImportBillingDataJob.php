<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Domain\Enums\CloudProvider;
use Modules\BigData\Domain\Interfaces\CloudBillingAdapterInterface;
use Modules\BigData\Domain\Interfaces\CostRepositoryInterface;

/**
 * Import Billing Data Job
 *
 * Imports billing data from cloud provider APIs into ClickHouse.
 * Runs hourly to keep cost data fresh.
 *
 * Queue: bigdata-cost
 */
final class ImportBillingDataJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct(
        private readonly string $cloudProvider = 'self_hosted',
        private readonly int $daysBack = 3,
    ) {
        $this->onQueue('bigdata-cost');
    }

    public function handle(
        CostRepositoryInterface $repository,
    ): void {
        $endDate = CarbonImmutable::now();
        $startDate = $endDate->subDays($this->daysBack);

        Log::info('ImportBillingDataJob: starting', [
            'provider' => $this->cloudProvider,
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
        ]);

        try {
            $adapter = $this->resolveAdapter();
            $records = $adapter->getDailyBilling($startDate, $endDate);

            if (empty($records)) {
                Log::info('ImportBillingDataJob: no records returned');

                return;
            }

            $inserted = $repository->insertBillingRaw($records);

            Log::info('ImportBillingDataJob: completed', [
                'provider' => $this->cloudProvider,
                'records_inserted' => $inserted,
            ]);
        } catch (\Throwable $e) {
            Log::error('ImportBillingDataJob: failed', [
                'provider' => $this->cloudProvider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveAdapter(): CloudBillingAdapterInterface
    {
        return app("bigdata.billing.{$this->cloudProvider}");
    }
}
