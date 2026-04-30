<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\Batch;
use Modules\Warehouse\Domain\Repositories\BatchRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\BatchId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ProductId;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Batch Tracking Application Service
 *
 * Orchestrates batch tracking operations
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BatchTrackingApplicationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly BatchRepositoryInterface $batchRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create batch
     */
    public function createBatch(
        WarehouseId $warehouseId,
        string $productSku,
        string $batchNumber,
        \DateTimeImmutable $expiryDate,
        int $quantity,
        ?int $userId = null,
        ?string $tenantId = null
    ): Batch {
        $batch = Batch::create(
            warehouseId: $warehouseId,
            productSku: $productSku,
            batchNumber: $batchNumber,
            expiryDate: $expiryDate,
            quantity: $quantity
        );

        $this->batchRepository->save($batch);

        $this->auditService->logAction(
            action: 'batch_created',
            entityType: 'Batch',
            entityId: $batch->getId()->toString(),
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'batch_number' => $batchNumber,
                'product_sku' => $productSku,
                'quantity' => $quantity,
                'expiry_date' => $expiryDate->format('Y-m-d'),
            ]
        );

        $this->logger->info('Batch created', [
            'batch_id' => $batch->getId()->toString(),
            'batch_number' => $batchNumber,
            'product_sku' => $productSku,
        ]);

        return $batch;
    }

    /**
     * Quarantine batch
     */
    public function quarantineBatch(
        string $batchId,
        string $reason,
        ?int $userId = null,
        ?string $tenantId = null
    ): void {
        $batch = $this->batchRepository->findById(
            BatchId::fromString($batchId)
        );

        if (!$batch) {
            throw new \InvalidArgumentException('Batch not found');
        }

        $this->auditService->logAction(
            action: 'batch_quarantined',
            entityType: 'Batch',
            entityId: $batchId,
            userId: $userId,
            tenantId: $tenantId,
            context: [
                'reason' => $reason,
            ]
        );

        $this->logger->info('Batch quarantined', [
            'batch_id' => $batchId,
            'reason' => $reason,
        ]);
    }

    /**
     * Get expiring batches
     */
    public function getExpiringBatches(int $daysThreshold = 30): array
    {
        return $this->batchRepository->findExpiringBatches($daysThreshold);
    }

    /**
     * Get expired batches
     */
    public function getExpiredBatches(): array
    {
        return $this->batchRepository->findExpiredBatches();
    }

    /**
     * Check if product requires FEFO
     */
    public function requiresFEFO(string $productSku): bool
    {
        // Medical products typically require FEFO
        $medicalPrefixes = ['MED-', 'PHARMA-', 'VET-'];
        
        foreach ($medicalPrefixes as $prefix) {
            if (str_starts_with($productSku, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
