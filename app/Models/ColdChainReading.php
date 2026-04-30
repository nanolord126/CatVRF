<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

/**
 * Cold Chain Reading Model
 *
 * Stores temperature and humidity readings for cold chain monitoring.
 * Compliant with ФЗ-323 for pharmaceutical storage requirements.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $zone_id
 * @property float $temperature
 * @property float|null $humidity
 * @property string|null $sensor_id
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
final class ColdChainReading extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'cold_chain_readings';

    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'temperature',
        'humidity',
        'sensor_id',
        'recorded_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Warehouse where reading was taken
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Zone where reading was taken
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * Scope for specific sensor
     */
    public function scopeForSensor($query, string $sensorId)
    {
        return $query->where('sensor_id', $sensorId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('recorded_at', [$startDate, $endDate]);
    }

    /**
     * Scope for temperature violations
     */
    public function scopeViolations($query, float $minTemp, float $maxTemp)
    {
        return $query->where(function ($q) use ($minTemp, $maxTemp) {
            $q->where('temperature', '<', $minTemp)
              ->orWhere('temperature', '>', $maxTemp);
        });
    }

    /**
     * Check if reading is within acceptable range
     */
    public function isWithinRange(float $minTemp, float $maxTemp): bool
    {
        return $this->temperature >= $minTemp && $this->temperature <= $maxTemp;
    }

    /**
     * Get temperature deviation from target
     */
    public function getDeviation(float $targetTemp): float
    {
        return abs($this->temperature - $targetTemp);
    }
}
