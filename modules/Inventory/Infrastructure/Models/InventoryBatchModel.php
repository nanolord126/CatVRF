<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Domain\Entities\InventoryBatch as InventoryBatchEntity;
use Modules\Inventory\Domain\Enums\BatchStatus;

final class InventoryBatchModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inventory_batches';

    protected $fillable = [
        'inventory_item_id',
        'tenant_id',
        'batch_number',
        'manufacture_date',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'purchase_price',
        'storage_location',
        'status',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItemModel::class, 'inventory_item_id');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUsable($query)
    {
        return $query->where('status', '!=', 'expired')
            ->where('expiry_date', '>=', now());
    }

    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByExpiryDate($query)
    {
        return $query->orderBy('expiry_date', 'asc')
            ->orderBy('manufacture_date', 'asc');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->lte(now()->addDays($days));
    }

    public function isUsable(): bool
    {
        return $this->status !== 'expired' && $this->status !== 'quarantine' && !$this->isExpired();
    }

    public function hasStock(): bool
    {
        return $this->current_quantity > 0;
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function toDomain(): InventoryBatchEntity
    {
        return new InventoryBatchEntity(
            id: $this->id,
            inventoryItemId: $this->inventory_item_id,
            batchNumber: $this->batch_number,
            manufactureDate: $this->manufacture_date,
            expiryDate: $this->expiry_date,
            initialQuantity: $this->initial_quantity,
            currentQuantity: $this->current_quantity,
            purchasePrice: (float) $this->purchase_price,
            storageLocation: $this->storage_location,
            status: BatchStatus::from($this->status),
        );
    }
}
