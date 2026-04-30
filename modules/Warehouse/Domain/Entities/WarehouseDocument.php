<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseDocumentId;
use Modules\Warehouse\Domain\Enums\DocumentTypeEnum;
use Modules\Warehouse\Domain\Enums\DocumentStatusEnum;

/**
 * Warehouse Document Entity
 * 
 * Представляет документ в системе документооборота склада:
 * - Акты приемки
 * - Накладные
 * - Акты списания
 * - Акты инвентаризации
 * - Прочие документы
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WarehouseDocument
{
    public function __construct(
        private WarehouseDocumentId $id,
        private string $warehouseId,
        private string $documentNumber,
        private DocumentTypeEnum $documentType,
        private DocumentStatusEnum $status,
        private \DateTimeImmutable $documentDate,
        private ?string $relatedOrderId,
        private ?string $relatedMovementId,
        private ?string $supplierId,
        private array $items,
        private float $totalAmount,
        private ?string $currency,
        private ?string $notes,
        private int $createdBy,
        private int $tenantId,
        private ?string $branchId,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $approvedAt = null,
        private ?int $approvedBy = null,
        private ?string $approvalComment = null,
        private ?\DateTimeImmutable $updatedAt = null
    ) {}

    public static function create(
        string $warehouseId,
        string $documentNumber,
        DocumentTypeEnum $documentType,
        \DateTimeImmutable $documentDate,
        int $createdBy,
        int $tenantId,
        ?string $relatedOrderId = null,
        ?string $relatedMovementId = null,
        ?string $supplierId = null,
        array $items = [],
        float $totalAmount = 0.0,
        ?string $currency = null,
        ?string $notes = null,
        ?string $branchId = null
    ): self {
        return new self(
            id: WarehouseDocumentId::generate(),
            warehouseId: $warehouseId,
            documentNumber: $documentNumber,
            documentType: $documentType,
            status: DocumentStatusEnum::DRAFT,
            documentDate: $documentDate,
            relatedOrderId: $relatedOrderId,
            relatedMovementId: $relatedMovementId,
            supplierId: $supplierId,
            items: $items,
            totalAmount: $totalAmount,
            currency: $currency,
            notes: $notes,
            createdBy: $createdBy,
            tenantId: $tenantId,
            branchId: $branchId,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function approve(int $approvedBy, string $comment = null): self
    {
        if ($this->status !== DocumentStatusEnum::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Document must be pending approval');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            documentNumber: $this->documentNumber,
            documentType: $this->documentType,
            status: DocumentStatusEnum::APPROVED,
            documentDate: $this->documentDate,
            relatedOrderId: $this->relatedOrderId,
            relatedMovementId: $this->relatedMovementId,
            supplierId: $this->supplierId,
            items: $this->items,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            notes: $this->notes,
            createdBy: $this->createdBy,
            tenantId: $this->tenantId,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            approvedAt: new \DateTimeImmutable(),
            approvedBy: $approvedBy,
            approvalComment: $comment,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function reject(int $rejectedBy, string $reason): self
    {
        if ($this->status !== DocumentStatusEnum::PENDING_APPROVAL) {
            throw new \InvalidArgumentException('Document must be pending approval');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            documentNumber: $this->documentNumber,
            documentType: $this->documentType,
            status: DocumentStatusEnum::REJECTED,
            documentDate: $this->documentDate,
            relatedOrderId: $this->relatedOrderId,
            relatedMovementId: $this->relatedMovementId,
            supplierId: $this->supplierId,
            items: $this->items,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            notes: $this->notes,
            createdBy: $this->createdBy,
            tenantId: $this->tenantId,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            approvedAt: new \DateTimeImmutable(),
            approvedBy: $rejectedBy,
            approvalComment: $reason,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function submitForApproval(): self
    {
        if ($this->status !== DocumentStatusEnum::DRAFT) {
            throw new \InvalidArgumentException('Document must be in draft status');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            documentNumber: $this->documentNumber,
            documentType: $this->documentType,
            status: DocumentStatusEnum::PENDING_APPROVAL,
            documentDate: $this->documentDate,
            relatedOrderId: $this->relatedOrderId,
            relatedMovementId: $this->relatedMovementId,
            supplierId: $this->supplierId,
            items: $this->items,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            notes: $this->notes,
            createdBy: $this->createdBy,
            tenantId: $this->tenantId,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            approvalComment: $this->approvalComment,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function archive(): self
    {
        if ($this->status !== DocumentStatusEnum::APPROVED) {
            throw new \InvalidArgumentException('Only approved documents can be archived');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            documentNumber: $this->documentNumber,
            documentType: $this->documentType,
            status: DocumentStatusEnum::ARCHIVED,
            documentDate: $this->documentDate,
            relatedOrderId: $this->relatedOrderId,
            relatedMovementId: $this->relatedMovementId,
            supplierId: $this->supplierId,
            items: $this->items,
            totalAmount: $this->totalAmount,
            currency: $this->currency,
            notes: $this->notes,
            createdBy: $this->createdBy,
            tenantId: $this->tenantId,
            branchId: $this->branchId,
            createdAt: $this->createdAt,
            approvedAt: $this->approvedAt,
            approvedBy: $this->approvedBy,
            approvalComment: $this->approvalComment,
            updatedAt: new \DateTimeImmutable()
        );
    }

    // Getters
    public function getId(): WarehouseDocumentId
    {
        return $this->id;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouseId;
    }

    public function getDocumentNumber(): string
    {
        return $this->documentNumber;
    }

    public function getDocumentType(): DocumentTypeEnum
    {
        return $this->documentType;
    }

    public function getStatus(): DocumentStatusEnum
    {
        return $this->status;
    }

    public function getDocumentDate(): \DateTimeImmutable
    {
        return $this->documentDate;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function canBeApproved(): bool
    {
        return $this->status === DocumentStatusEnum::PENDING_APPROVAL;
    }

    public function canBeRejected(): bool
    {
        return $this->status === DocumentStatusEnum::PENDING_APPROVAL;
    }

    public function canBeArchived(): bool
    {
        return $this->status === DocumentStatusEnum::APPROVED;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'warehouse_id' => $this->warehouseId,
            'document_number' => $this->documentNumber,
            'document_type' => $this->documentType->value,
            'status' => $this->status->value,
            'document_date' => $this->documentDate->format('Y-m-d H:i:s'),
            'related_order_id' => $this->relatedOrderId,
            'related_movement_id' => $this->relatedMovementId,
            'supplier_id' => $this->supplierId,
            'items' => $this->items,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'tenant_id' => $this->tenantId,
            'branch_id' => $this->branchId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'approved_by' => $this->approvedBy,
            'approval_comment' => $this->approvalComment,
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
