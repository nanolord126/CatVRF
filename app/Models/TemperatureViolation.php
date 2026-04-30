<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Temperature Violation Model
 *
 * Logs temperature violations for audit trail and compliance.
 * Tracks products affected and resolution details.
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $warehouse_id
 * @property float $current_temperature
 * @property float $min_allowed
 * @property float $max_allowed
 * @property string $severity
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class TemperatureViolation extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'temperature_violations';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'current_temperature',
        'min_allowed',
        'max_allowed',
        'severity',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'current_temperature' => 'decimal:2',
        'min_allowed' => 'decimal:2',
        'max_allowed' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    /**
     * Product affected by violation
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Warehouse where violation occurred
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * User who resolved the violation
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope for unresolved violations
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope for resolved violations
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope for critical severity
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for high severity
     */
    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope for specific warehouse
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope for specific product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if violation is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Resolve the violation
     */
    public function resolve(int $userId): bool
    {
        return $this->update([
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    /**
     * Get duration of violation in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if (!$this->resolved_at) {
            return now()->diffInMinutes($this->created_at);
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    /**
     * Get temperature deviation from allowed range
     */
    public function getDeviation(): float
    {
        if ($this->current_temperature < $this->min_allowed) {
            return $this->min_allowed - $this->current_temperature;
        }

        return $this->current_temperature - $this->max_allowed;
    }

    /**
     * Check if temperature is below minimum
     */
    public function isBelowMin(): bool
    {
        return $this->current_temperature < $this->min_allowed;
    }

    /**
     * Check if temperature is above maximum
     */
    public function isAboveMax(): bool
    {
        return $this->current_temperature > $this->max_allowed;
    }
}
