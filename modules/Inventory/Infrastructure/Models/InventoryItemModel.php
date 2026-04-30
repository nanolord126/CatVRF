<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Domain\Entities\InventoryItem as InventoryItemEntity;
use Modules\Inventory\Domain\Enums\InventoryCategory;
use Modules\Inventory\Domain\Enums\ItemStatus;

final class InventoryItemModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'business_group_id',
        'name',
        'sku',
        'barcode',
        'category',
        'batch_number',
        'manufacture_date',
        'expiry_date',
        'shelf_life_days',
        'quantity',
        'reserved',
        'unit',
        'purchase_price',
        'selling_price',
        'min_stock_level',
        'storage_conditions',
        'storage_location',
        'is_controlled',
        'status',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'is_controlled' => 'boolean',
        'metadata' => 'array',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatchModel::class, 'inventory_item_id');
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

    public function scopeControlled($query)
    {
        return $query->where('is_controlled', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
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
        return $this->status !== 'expired' && $this->status !== 'quarantine';
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function toDomain(): InventoryItemEntity
    {
        return new InventoryItemEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            name: $this->name,
            sku: $this->sku,
            barcode: $this->barcode,
            category: InventoryCategory::from($this->category),
            batchNumber: $this->batch_number,
            manufactureDate: $this->manufacture_date,
            expiryDate: $this->expiry_date,
            shelfLifeDays: $this->shelf_life_days,
            quantity: $this->quantity,
            unit: $this->unit,
            purchasePrice: (float) $this->purchase_price,
            sellingPrice: (float) $this->selling_price,
            minStockLevel: $this->min_stock_level,
            storageConditions: $this->storage_conditions,
            isControlled: $this->is_controlled,
            status: ItemStatus::from($this->status),
        );
    }
}
