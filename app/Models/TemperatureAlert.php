<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Temperature Alert Model
 *
 * Stores temperature violation alerts for cold chain monitoring.
 * Supports severity levels and resolution tracking.
 *
 * @property int $id
 * @property int $zone_id
 * @property float $temperature
 * @property float $min_allowed
 * @property float $max_allowed
 * @property string $severity
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class TemperatureAlert extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'temperature_alerts';

    protected $fillable = [
        'zone_id',
        'temperature',
        'min_allowed',
        'max_allowed',
        'severity',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'min_allowed' => 'decimal:2',
        'max_allowed' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    /**
     * Zone where alert occurred
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * User who resolved the alert
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope for unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope for resolved alerts
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
     * Scope for specific severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if alert is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Resolve the alert
     */
    public function resolve(int $userId): bool
    {
        return $this->update([
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    /**
     * Get duration of alert in minutes
     */
    public function getDurationMinutes(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    /**
     * Check if temperature is below minimum
     */
    public function isBelowMin(): bool
    {
        return $this->temperature < $this->min_allowed;
    }

    /**
     * Check if temperature is above maximum
     */
    public function isAboveMax(): bool
    {
        return $this->temperature > $this->max_allowed;
    }
}
