<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;
use Modules\Warehouse\Domain\ValueObjects\InventoryCountId;

final readonly class InventoryCount
{
    public function __construct(
        private InventoryCountId $id,
        private WarehouseId $warehouseId,
        private ?ZoneId $zoneId,
        private string $countNumber,
        private string $countType,
        private string $status,
        private \DateTimeImmutable $scheduledDate,
        private ?\DateTimeImmutable $startedAt,
        private ?\DateTimeImmutable $completedAt,
        private ?int $totalItemsExpected,
        private ?int $totalItemsCounted,
        private ?int $discrepanciesFound,
        private ?string $performedBy,
        private ?string $approvedBy,
        private ?\DateTimeImmutable $approvedAt,
        private ?string $notes,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null
    ) {}

    public static function create(
        WarehouseId $warehouseId,
        ?ZoneId $zoneId,
        string $countType,
        \DateTimeImmutable $scheduledDate,
        ?string $notes = null
    ): self {
        $countNumber = 'IC-' . date('Ymd-His') . '-' . rand(1000, 9999);

        return new self(
            id: InventoryCountId::generate(),
            warehouseId: $warehouseId,
            zoneId: $zoneId,
            countNumber: $countNumber,
            countType: $countType,
            status: 'scheduled',
            scheduledDate: $scheduledDate,
            startedAt: null,
            completedAt: null,
            totalItemsExpected: null,
            totalItemsCounted: null,
            discrepanciesFound: null,
            performedBy: null,
            approvedBy: null,
            approvedAt: null,
            notes: $notes,
            createdAt: new \DateTimeImmutable()
        );
    }

    public function getId(): InventoryCountId
    {
        return $this->id;
    }

    public function getWarehouseId(): WarehouseId
    {
        return $this->warehouseId;
    }

    public function getZoneId(): ?ZoneId
    {
        return $this->zoneId;
    }

    public function getCountNumber(): string
    {
        return $this->countNumber;
    }

    public function getCountType(): string
    {
        return $this->countType;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getScheduledDate(): \DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getTotalItemsExpected(): ?int
    {
        return $this->totalItemsExpected;
    }

    public function getTotalItemsCounted(): ?int
    {
        return $this->totalItemsCounted;
    }

    public function getDiscrepanciesFound(): ?int
    {
        return $this->discrepanciesFound;
    }

    public function getPerformedBy(): ?string
    {
        return $this->performedBy;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function start(string $performedBy): self
    {
        if (!$this->isScheduled()) {
            throw new \InvalidArgumentException('Can only start scheduled inventory counts');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            countNumber: $this->countNumber,
            countType: $this->countType,
            status: 'in_progress',
            scheduledDate: $this->scheduledDate,
            startedAt: new \DateTimeImmutable(),
            completedAt: null,
            totalItemsExpected: $this->totalItemsExpected,
            totalItemsCounted: $this->totalItemsCounted,
            discrepanciesFound: $this->discrepanciesFound,
            performedBy: $performedBy,
            approvedBy: $this->approvedBy,
            approvedAt: $this->approvedAt,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function complete(int $totalItemsCounted, int $discrepanciesFound): self
    {
        if (!$this->isInProgress()) {
            throw new \InvalidArgumentException('Can only complete inventory counts in progress');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            countNumber: $this->countNumber,
            countType: $this->countType,
            status: 'completed',
            scheduledDate: $this->scheduledDate,
            startedAt: $this->startedAt,
            completedAt: new \DateTimeImmutable(),
            totalItemsExpected: $this->totalItemsExpected,
            totalItemsCounted: $totalItemsCounted,
            discrepanciesFound: $discrepanciesFound,
            performedBy: $this->performedBy,
            approvedBy: $this->approvedBy,
            approvedAt: $this->approvedAt,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function approve(string $approvedBy): self
    {
        if (!$this->isCompleted()) {
            throw new \InvalidArgumentException('Can only approve completed inventory counts');
        }

        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            countNumber: $this->countNumber,
            countType: $this->countType,
            status: 'approved',
            scheduledDate: $this->scheduledDate,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            totalItemsExpected: $this->totalItemsExpected,
            totalItemsCounted: $this->totalItemsCounted,
            discrepanciesFound: $this->discrepanciesFound,
            performedBy: $this->performedBy,
            approvedBy: $approvedBy,
            approvedAt: new \DateTimeImmutable(),
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function setExpectedItems(int $totalItemsExpected): self
    {
        return new self(
            id: $this->id,
            warehouseId: $this->warehouseId,
            zoneId: $this->zoneId,
            countNumber: $this->countNumber,
            countType: $this->countType,
            status: $this->status,
            scheduledDate: $this->scheduledDate,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            totalItemsExpected: $totalItemsExpected,
            totalItemsCounted: $this->totalItemsCounted,
            discrepanciesFound: $this->discrepanciesFound,
            performedBy: $this->performedBy,
            approvedBy: $this->approvedBy,
            approvedAt: $this->approvedAt,
            notes: $this->notes,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'warehouse_id' => $this->warehouseId->toString(),
            'zone_id' => $this->zoneId?->toString(),
            'count_number' => $this->countNumber,
            'count_type' => $this->countType,
            'status' => $this->status,
            'scheduled_date' => $this->scheduledDate->format('Y-m-d H:i:s'),
            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
            'total_items_expected' => $this->totalItemsExpected,
            'total_items_counted' => $this->totalItemsCounted,
            'discrepancies_found' => $this->discrepanciesFound,
            'performed_by' => $this->performedBy,
            'approved_by' => $this->approvedBy,
            'approved_at' => $this->approvedAt?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'is_scheduled' => $this->isScheduled(),
            'is_in_progress' => $this->isInProgress(),
            'is_completed' => $this->isCompleted(),
            'is_approved' => $this->isApproved(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
