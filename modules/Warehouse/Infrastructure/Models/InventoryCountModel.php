<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Warehouse\Domain\Entities\InventoryCount as InventoryCountEntity;
use Modules\Warehouse\Domain\ValueObjects\InventoryCountId;
use Modules\Warehouse\Domain\ValueObjects\WarehouseId;
use Modules\Warehouse\Domain\ValueObjects\ZoneId;

final class InventoryCountModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_inventory_counts';

    protected $fillable = [
        'id',
        'warehouse_id',
        'zone_id',
        'count_number',
        'count_type',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'total_items_expected',
        'total_items_counted',
        'discrepancies_found',
        'performed_by',
        'approved_by',
        'approved_at',
        'notes',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_items_expected' => 'integer',
        'total_items_counted' => 'integer',
        'discrepancies_found' => 'integer',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZoneModel::class, 'zone_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItemModel::class, 'inventory_count_id');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByCountNumber($query, string $countNumber)
    {
        return $query->where('count_number', $countNumber);
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

    public function toDomain(): InventoryCountEntity
    {
        return new InventoryCountEntity(
            id: InventoryCountId::fromString($this->id),
            warehouseId: WarehouseId::fromString($this->warehouse_id),
            zoneId: $this->zone_id ? ZoneId::fromString($this->zone_id) : null,
            countNumber: $this->count_number,
            countType: $this->count_type,
            status: $this->status,
            scheduledDate: new \DateTimeImmutable($this->scheduled_date),
            startedAt: $this->started_at ? new \DateTimeImmutable($this->started_at) : null,
            completedAt: $this->completed_at ? new \DateTimeImmutable($this->completed_at) : null,
            totalItemsExpected: $this->total_items_expected,
            totalItemsCounted: $this->total_items_counted,
            discrepanciesFound: $this->discrepancies_found,
            performedBy: $this->performed_by,
            approvedBy: $this->approved_by,
            approvedAt: $this->approved_at ? new \DateTimeImmutable($this->approved_at) : null,
            notes: $this->notes,
            createdAt: new \DateTimeImmutable($this->created_at),
            updatedAt: $this->updated_at ? new \DateTimeImmutable($this->updated_at) : null
        );
    }

    public static function fromDomain(InventoryCountEntity $entity): array
    {
        return [
            'id' => $entity->getId()->toString(),
            'warehouse_id' => $entity->getWarehouseId()->toString(),
            'zone_id' => $entity->getZoneId()?->toString(),
            'count_number' => $entity->getCountNumber(),
            'count_type' => $entity->getCountType(),
            'status' => $entity->getStatus(),
            'scheduled_date' => $entity->getScheduledDate()->format('Y-m-d H:i:s'),
            'started_at' => $entity->getStartedAt()?->format('Y-m-d H:i:s'),
            'completed_at' => $entity->getCompletedAt()?->format('Y-m-d H:i:s'),
            'total_items_expected' => $entity->getTotalItemsExpected(),
            'total_items_counted' => $entity->getTotalItemsCounted(),
            'discrepancies_found' => $entity->getDiscrepanciesFound(),
            'performed_by' => $entity->getPerformedBy(),
            'approved_by' => $entity->getApprovedBy(),
            'approved_at' => $entity->getApprovedAt()?->format('Y-m-d H:i:s'),
            'notes' => $entity->getNotes(),
        ];
    }
}
