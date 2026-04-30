<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use App\Models\WarehouseProduct;

/**
 * Warehouse Bin Model
 *
 * Represents storage bins within warehouse zones.
 * Tracks capacity and current stock for space management.
 *
 * @property int $id
 * @property string $warehouse_id
 * @property string $zone_id
 * @property string $code
 * @property string $name
 * @property string|null $coordinates
 * @property int $capacity
 * @property int $current_stock
 * @property bool $is_active
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseBin extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_bins';

    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'code',
        'name',
        'coordinates',
        'capacity',
        'current_stock',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Warehouse this bin belongs to
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Zone this bin belongs to
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * Batches stored in this bin
     */
    public function batches(): HasMany
    {
        return $this->hasMany(WarehouseBatch::class, 'bin_id');
    }

    /**
     * Products stored in this bin (through batches)
     */
    public function products(): HasMany
    {
        return $this->hasMany(WarehouseProduct::class, 'bin_id');
    }

    /**
     * Scope for active bins
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for bins with available capacity
     */
    public function scopeWithCapacity($query)
    {
        return $query->whereRaw('current_stock < capacity');
    }

    /**
     * Scope for bins at capacity
     */
    public function scopeFull($query)
    {
        return $query->whereRaw('current_stock >= capacity');
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentage(): float
    {
        if ($this->capacity === 0) {
            return 0;
        }

        return ($this->current_stock / $this->capacity) * 100;
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacity(): int
    {
        return max(0, $this->capacity - $this->current_stock);
    }
}
