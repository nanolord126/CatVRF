<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Warehouse Zone Model
 *
 * Represents functional zones within a warehouse (receiving, storage, picking, etc.).
 * Supports branch-specific zoning for multi-tenant operations.
 *
 * @property int $id
 * @property string $warehouse_id
 * @property string $name
 * @property string $type
 * @property int $capacity
 * @property int $current_stock
 * @property string|null $branch_id
 * @property bool $is_active
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class WarehouseZone extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id',
        'name',
        'type',
        'capacity',
        'current_stock',
        'branch_id',
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
     * Warehouse this zone belongs to
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Bins in this zone
     */
    public function bins(): HasMany
    {
        return $this->hasMany(WarehouseBin::class, 'zone_id');
    }

    /**
     * Batches in this zone
     */
    public function batches(): HasMany
    {
        return $this->hasMany(WarehouseBatch::class, 'zone_id');
    }

    /**
     * Scope for active zones
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific zone types
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for storage zones
     */
    public function scopeStorage($query)
    {
        return $query->where('type', 'storage');
    }

    /**
     * Scope for picking zones
     */
    public function scopePicking($query)
    {
        return $query->where('type', 'picking');
    }

    /**
     * Scope for receiving zones
     */
    public function scopeReceiving($query)
    {
        return $query->where('type', 'receiving');
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
