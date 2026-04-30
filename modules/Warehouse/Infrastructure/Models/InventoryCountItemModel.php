<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InventoryCountItemModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'warehouse_inventory_count_items';

    protected $fillable = [
        'id',
        'inventory_count_id',
        'product_sku',
        'product_name',
        'expected_quantity',
        'counted_quantity',
        'discrepancy',
        'bin_code',
        'batch_number',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'discrepancy' => 'integer',
        'metadata' => 'array',
    ];

    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCountModel::class, 'inventory_count_id');
    }

    /**
     * Scope for items in a specific inventory count
     */
    public function scopeForInventoryCount($query, string $inventoryCountId)
    {
        return $query->where('inventory_count_id', $inventoryCountId);
    }

    /**
     * Scope for items with discrepancies
     */
    public function scopeWithDiscrepancy($query)
    {
        return $query->where('discrepancy', '!=', 0);
    }

    /**
     * Scope for items without discrepancies
     */
    public function scopeWithoutDiscrepancy($query)
    {
        return $query->where('discrepancy', 0);
    }

    /**
     * Scope for items with positive discrepancies (overstock)
     */
    public function scopeWithPositiveDiscrepancy($query)
    {
        return $query->where('discrepancy', '>', 0);
    }

    /**
     * Scope for items with negative discrepancies (understock)
     */
    public function scopeWithNegativeDiscrepancy($query)
    {
        return $query->where('discrepancy', '<', 0);
    }

    /**
     * Scope for items in a specific bin
     */
    public function scopeInBin($query, string $binCode)
    {
        return $query->where('bin_code', $binCode);
    }

    /**
     * Scope for items with a specific batch
     */
    public function scopeWithBatch($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    /**
     * Scope for items matching product SKU
     */
    public function scopeForProduct($query, string $productSku)
    {
        return $query->where('product_sku', $productSku);
    }

    /**
     * Calculate discrepancy percentage
     */
    public function getDiscrepancyPercentageAttribute(): float
    {
        if ($this->expected_quantity === 0) {
            return 0.0;
        }

        return round(($this->discrepancy / $this->expected_quantity) * 100, 2);
    }

    /**
     * Check if item has significant discrepancy (>5%)
     */
    public function hasSignificantDiscrepancy(float $threshold = 5.0): bool
    {
        return abs($this->discrepancy_percentage) > $threshold;
    }

    /**
     * Mark item as counted
     */
    public function markAsCounted(int $countedQuantity, ?string $binCode = null, ?string $notes = null): bool
    {
        $this->counted_quantity = $countedQuantity;
        $this->discrepancy = $countedQuantity - $this->expected_quantity;
        
        if ($binCode !== null) {
            $this->bin_code = $binCode;
        }
        
        if ($notes !== null) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Reset counted quantity
     */
    public function resetCounted(): bool
    {
        $this->counted_quantity = 0;
        $this->discrepancy = -$this->expected_quantity;
        $this->bin_code = null;
        $this->notes = null;

        return $this->save();
    }
}
