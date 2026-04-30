<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Repositories;

use Modules\Warehouse\Domain\Entities\WarehouseDocument;
use Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId;
use Modules\Warehouse\Domain\Enums\DocumentStatusEnum;

/**
 * Warehouse Document Repository Interface
 */
interface WarehouseDocumentRepositoryInterface
{
    public function save(WarehouseDocument $document): void;
    
    public function findById(WarehouseDocumentId $id): ?WarehouseDocument;
    
    public function findByWarehouseId(string $warehouseId): array;
    
    public function findByDocumentNumber(string $documentNumber): ?WarehouseDocument;
    
    public function findByStatusAndTenant(DocumentStatusEnum $status, int $tenantId): array;
    
    public function findByTenant(int $tenantId): array;
    
    public function delete(WarehouseDocumentId $id): void;
    
    public function exists(WarehouseDocumentId $id): bool;
}
