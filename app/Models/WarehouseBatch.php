<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Carbon\Carbon;

/**
 * Warehouse Batch Model
 *
 * Represents product batches with expiry tracking for FIFO operations.
 * Critical for pharmaceutical compliance (ФЗ-323) and inventory management.
 *
 * @property int $id
 * @property string $product_id
 * @property string $product_sku
 * @property string $batch_number
 * @property string $lot_number
 * @property \Illuminate\Support\Carbon $manufacture_date
 * @property \Illuminate\Support\Carbon $expiry_date
 * @property int $initial_quantity
 * @property int $current_quantity
 * @property float $purchase_price
 * @property string $warehouse_id
 * @property string|null $zone_id
 * @property string|null $bin_id
 * @property string|null $supplier_id
 * @property string|null $supplier_name
 * @property string|null $certificate_number
 * @property string $status
 * @property string|null $notes
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseBatch extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_batches';

    protected $fillable = [
        'product_id',
        'product_sku',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'purchase_price',
        'warehouse_id',
        'zone_id',
        'bin_id',
        'supplier_id',
        'supplier_name',
        'certificate_number',
        'status',
        'notes',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'status' => 'string',
        'metadata' => 'json',
        'supplier_name' => \App\Casts\EncryptedPIICast::class,
    ];

    /**
     * Product this batch belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(WarehouseProduct::class, 'product_id');
    }

    /**
     * Warehouse where this batch is stored
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Zone where this batch is stored
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * Bin where this batch is stored
     */
    public function bin(): BelongsTo
    {
        return $this->belongsTo(WarehouseBin::class, 'bin_id');
    }

    /**
     * Stock movements for this batch
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(WarehouseStockMovement::class, 'batch_id');
    }

    /**
     * Scope for active batches
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for batches expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope for expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now()->startOfDay());
    }

    /**
     * Scope for usable batches (not expired, not depleted)
     */
    public function scopeUsable($query)
    {
        return $query->where('status', '!=', 'expired')
            ->where('status', '!=', 'depleted')
            ->where('current_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->startOfDay());
            });
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get remaining quantity percentage
     */
    public function getRemainingPercentage(): float
    {
        if ($this->initial_quantity === 0) {
            return 0;
        }

        return ($this->current_quantity / $this->initial_quantity) * 100;
    }
}
