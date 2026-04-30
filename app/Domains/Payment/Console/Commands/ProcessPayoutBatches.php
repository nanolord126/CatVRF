<?php

declare(strict_types=1);

namespace App\Domains\Payment\Console\Commands;

use App\Domains\Payment\Services\PayoutBatchService;
use App\Domains\Payment\Models\PayoutBatch;
use Illuminate\Console\Command;

class ProcessPayoutBatches extends Command
{
    protected $signature = 'payment:process-payout-batches';

    protected $description = 'Process payout batches that are ready for execution';

    public function handle(): int
    {
        $service = app(PayoutBatchService::class);
        $batches = $service->getReadyBatches();

        $processed = 0;
        foreach ($batches as $batch) {
            try {
                $service->processBatch($batch);
                $processed++;
                $this->info("Processed batch {$batch->uuid}");
            } catch (\Throwable $e) {
                $this->error("Failed to process batch {$batch->uuid}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$processed} payout batches");

        return self::SUCCESS;
    }
}
