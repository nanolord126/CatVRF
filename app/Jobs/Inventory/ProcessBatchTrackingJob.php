<?php

declare(strict_types=1);

namespace App\Jobs\Inventory;

use App\Services\Inventory\BatchTrackingService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProcessBatchTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly string $action,
        private readonly array $data,
        private readonly string $correlationId,
    ) {
        $this->onQueue('inventory');
    }

    public function handle(BatchTrackingService $batchService): void
    {
        Log::info('Processing batch tracking job', [
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
        ]);

        match ($this->action) {
            'create_batch' => $batchService->createBatch(
                productId: $this->data['product_id'],
                warehouseId: $this->data['warehouse_id'],
                tenantId: $this->data['tenant_id'],
                batchNumber: $this->data['batch_number'],
                expiryDate: $this->data['expiry_date'],
                quantity: $this->data['quantity'],
                serialNumber: $this->data['serial_number'] ?? null,
                userId: $this->data['user_id'],
            ),
            'release_quarantine' => $batchService->releaseFromQuarantine(
                batchId: $this->data['batch_id'],
                userId: $this->data['user_id'],
            ),
            'place_hold' => $batchService->placeOnHold(
                batchId: $this->data['batch_id'],
                reason: $this->data['reason'],
                userId: $this->data['user_id'],
            ),
            'create_recall' => $batchService->createRecall(
                batchId: $this->data['batch_id'],
                recallType: $this->data['recall_type'],
                reason: $this->data['reason'],
                tenantId: $this->data['tenant_id'],
                userId: $this->data['user_id'],
            ),
            default => throw new \InvalidArgumentException("Unknown action: {$this->action}"),
        };
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Batch tracking job failed', [
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
