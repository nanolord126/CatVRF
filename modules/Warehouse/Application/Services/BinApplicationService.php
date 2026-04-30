<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Warehouse\Domain\Entities\Bin;
use Modules\Warehouse\Domain\Repositories\BinRepositoryInterface;
use Modules\Warehouse\Domain\Repositories\ZoneRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\BinId;
use Psr\Log\LoggerInterface;
use App\Services\Security\AuditService;
use Illuminate\Support\Str;

/**
 * Bin Application Service - Production Layer
 *
 * Orchestrates bin operations (storage bins within zones)
 * Separate from Inventory (stock counting process)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BinApplicationService
{
    public function __construct(
        private readonly BinRepositoryInterface $binRepository,
        private readonly ZoneRepositoryInterface $zoneRepository,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService
    ) {}

    /**
     * Create a new bin in zone
     */
    public function createBin(
        string $zoneId,
        string $code,
        string $name,
        int $capacity,
        ?string $coordinates = null,
        int $tenantId,
        ?int $userId = null
    ): Bin {
        $zone = $this->zoneRepository->findById(ZoneId::fromString($zoneId));

        if (!$zone) {
            throw new \InvalidArgumentException('Zone not found');
        }

        $bin = Bin::create(
            zoneId: $zone->getId(),
            warehouseId: $zone->getWarehouseId(),
            code: $code,
            name: $name,
            capacity: $capacity,
            coordinates: $coordinates
        );

        $correlationId = Str::uuid()->toString();

        $this->binRepository->save($bin);

        $this->auditService->logEvent(
            eventType: 'warehouse_bin_created',
            context: [
                'entity_type' => 'warehouse_bin',
                'entity_id' => $bin->getId()->toString(),
                'zone_id' => $zoneId,
                'warehouse_id' => $zone->getWarehouseId()->toString(),
                'code' => $bin->getCode(),
                'name' => $bin->getName(),
                'capacity' => $bin->getCapacity(),
                'coordinates' => $coordinates,
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Warehouse bin created', [
            'bin_id' => $bin->getId()->toString(),
            'zone_id' => $zoneId,
            'code' => $bin->getCode(),
            'name' => $bin->getName(),
            'capacity' => $bin->getCapacity(),
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return $bin;
    }

    /**
     * Update bin stock level
     */
    public function updateBinStock(
        string $binId,
        int $quantity,
        int $tenantId,
        ?int $userId = null
    ): Bin {
        $id = BinId::fromString($binId);
        $bin = $this->binRepository->findById($id);

        if (!$bin) {
            throw new \InvalidArgumentException('Bin not found');
        }

        $updatedBin = $bin->updateStock($quantity);
        $correlationId = Str::uuid()->toString();

        $this->binRepository->save($updatedBin);

        $this->auditService->logEvent(
            eventType: 'warehouse_bin_stock_updated',
            context: [
                'entity_type' => 'warehouse_bin',
                'entity_id' => $binId,
                'zone_id' => $updatedBin->getZoneId()->toString(),
                'warehouse_id' => $updatedBin->getWarehouseId()->toString(),
                'quantity_change' => $quantity,
                'new_stock' => $updatedBin->getCurrentStock(),
                'utilization' => $updatedBin->getUtilizationPercentage(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Bin stock updated', [
            'bin_id' => $binId,
            'quantity_change' => $quantity,
            'new_stock' => $updatedBin->getCurrentStock(),
            'correlation_id' => $correlationId,
        ]);

        return $updatedBin;
    }

    /**
     * Get bin by ID
     */
    public function getBin(string $binId): ?Bin
    {
        $id = BinId::fromString($binId);
        return $this->binRepository->findById($id);
    }

    /**
     * Get bin by code
     */
    public function getBinByCode(string $code): ?Bin
    {
        return $this->binRepository->findByCode($code);
    }

    /**
     * Get bins by zone
     */
    public function getBinsByZone(string $zoneId): array
    {
        $id = ZoneId::fromString($zoneId);
        return $this->binRepository->findByZoneId($id);
    }

    /**
     * Get active bins by zone
     */
    public function getActiveBinsByZone(string $zoneId): array
    {
        $id = ZoneId::fromString($zoneId);
        return $this->binRepository->findActiveByZoneId($id);
    }

    /**
     * Delete bin
     */
    public function deleteBin(
        string $binId,
        int $tenantId,
        ?int $userId = null
    ): void {
        $id = BinId::fromString($binId);
        $bin = $this->binRepository->findById($id);

        if (!$bin) {
            throw new \InvalidArgumentException('Bin not found');
        }

        $correlationId = Str::uuid()->toString();

        $this->binRepository->delete($id);

        $this->auditService->logEvent(
            eventType: 'warehouse_bin_deleted',
            context: [
                'entity_type' => 'warehouse_bin',
                'entity_id' => $binId,
                'zone_id' => $bin->getZoneId()->toString(),
                'warehouse_id' => $bin->getWarehouseId()->toString(),
                'code' => $bin->getCode(),
                'name' => $bin->getName(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ],
            correlationId: $correlationId
        );

        $this->logger->info('Bin deleted', [
            'bin_id' => $binId,
            'code' => $bin->getCode(),
            'correlation_id' => $correlationId,
        ]);
    }
}
