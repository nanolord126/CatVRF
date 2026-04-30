<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Warehouse\Domain\Entities\WarehouseDocument;
use Modules\Warehouse\Domain\Repositories\WarehouseDocumentRepositoryInterface;
use Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId;
use Modules\Warehouse\Domain\Enums\DocumentTypeEnum;
use Modules\Warehouse\Domain\Enums\DocumentStatusEnum;
use Modules\Warehouse\Infrastructure\Models\WarehouseDocumentModel;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse Document Repository Implementation
 */
final readonly class WarehouseDocumentRepository implements WarehouseDocumentRepositoryInterface
{
    public function save(WarehouseDocument $document): void
    {
        $data = $document->toArray();
        
        WarehouseDocumentModel::updateOrCreate(
            ['id' => $document->getId()->toString()],
            $data
        );
    }

    public function findById(WarehouseDocumentId $id): ?WarehouseDocument
    {
        $model = WarehouseDocumentModel::find($id->toString());
        
        if (!$model) {
            return null;
        }
        
        return $this->modelToEntity($model);
    }

    public function findByWarehouseId(string $warehouseId): array
    {
        $models = WarehouseDocumentModel::where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return array_map(fn($model) => $this->modelToEntity($model), $models->toArray());
    }

    public function findByDocumentNumber(string $documentNumber): ?WarehouseDocument
    {
        $model = WarehouseDocumentModel::where('document_number', $documentNumber)->first();
        
        if (!$model) {
            return null;
        }
        
        return $this->modelToEntity($model);
    }

    public function findByStatusAndTenant(DocumentStatusEnum $status, int $tenantId): array
    {
        $models = WarehouseDocumentModel::where('status', $status->value)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return array_map(fn($model) => $this->modelToEntity($model), $models->toArray());
    }

    public function findByTenant(int $tenantId): array
    {
        $models = WarehouseDocumentModel::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return array_map(fn($model) => $this->modelToEntity($model), $models->toArray());
    }

    public function delete(WarehouseDocumentId $id): void
    {
        WarehouseDocumentModel::where('id', $id->toString())->delete();
    }

    public function exists(WarehouseDocumentId $id): bool
    {
        return WarehouseDocumentModel::where('id', $id->toString())->exists();
    }

    private function modelToEntity(WarehouseDocumentModel $model): WarehouseDocument
    {
        return new WarehouseDocument(
            id: WarehouseDocumentId::fromString($model->id),
            warehouseId: $model->warehouse_id,
            documentNumber: $model->document_number,
            documentType: DocumentTypeEnum::from($model->document_type),
            status: DocumentStatusEnum::from($model->status),
            documentDate: new \DateTimeImmutable($model->document_date),
            relatedOrderId: $model->related_order_id,
            relatedMovementId: $model->related_movement_id,
            supplierId: $model->supplier_id,
            items: $model->items ?? [],
            totalAmount: (float) $model->total_amount,
            currency: $model->currency,
            notes: $model->notes,
            createdBy: (int) $model->created_by,
            tenantId: (int) $model->tenant_id,
            branchId: $model->branch_id,
            createdAt: new \DateTimeImmutable($model->created_at),
            approvedAt: $model->approved_at ? new \DateTimeImmutable($model->approved_at) : null,
            approvedBy: $model->approved_by ? (int) $model->approved_by : null,
            approvalComment: $model->approval_comment,
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at) : null
        );
    }
}
