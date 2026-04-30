<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProductTemperatureRequirement — Temperature storage requirements for products
 * 
 * Defines temperature ranges, storage types, and monitoring requirements
 * for perishable products in compliance with food safety regulations (152-ФЗ, ФЗ-323)
 */
final class ProductTemperatureRequirement extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_product_temperature_requirements';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'min_temperature_celsius',
        'max_temperature_celsius',
        'optimal_temperature_celsius',
        'storage_type',
        'critical_min_temperature_celsius',
        'critical_max_temperature_celsius',
        'max_duration_outside_range_minutes',
        'warning_threshold_minutes',
        'requires_continuous_monitoring',
        'is_hazardous',
        'notes',
        'regulation_reference',
    ];

    protected $casts = [
        'min_temperature_celsius' => 'decimal:2',
        'max_temperature_celsius' => 'decimal:2',
        'optimal_temperature_celsius' => 'decimal:2',
        'critical_min_temperature_celsius' => 'decimal:2',
        'critical_max_temperature_celsius' => 'decimal:2',
        'max_duration_outside_range_minutes' => 'integer',
        'warning_threshold_minutes' => 'integer',
        'requires_continuous_monitoring' => 'boolean',
        'is_hazardous' => 'boolean',
        'coordinates' => 'array',
    ];

    // Storage types
    public const STORAGE_FROZEN = 'frozen';
    public const STORAGE_REFRIGERATED = 'refrigerated';
    public const STORAGE_AMBIENT = 'ambient';
    public const STORAGE_HEATED = 'heated';

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    /**
     * Check if temperature is within acceptable range
     */
    public function isTemperatureInRange(float $temperature): bool
    {
        if ($this->min_temperature_celsius === null && $this->max_temperature_celsius === null) {
            return true;
        }

        $aboveMin = $this->min_temperature_celsius === null || $temperature >= $this->min_temperature_celsius;
        $belowMax = $this->max_temperature_celsius === null || $temperature <= $this->max_temperature_celsius;

        return $aboveMin && $belowMax;
    }

    /**
     * Check if temperature is at critical level
     */
    public function isTemperatureCritical(float $temperature): bool
    {
        $belowCriticalMin = $this->critical_min_temperature_celsius !== null && $temperature < $this->critical_min_temperature_celsius;
        $aboveCriticalMax = $this->critical_max_temperature_celsius !== null && $temperature > $this->critical_max_temperature_celsius;

        return $belowCriticalMin || $aboveCriticalMax;
    }

    /**
     * Get violation severity for a temperature reading
     */
    public function getViolationSeverity(float $temperature): string
    {
        if ($this->isTemperatureCritical($temperature)) {
            return 'critical';
        }

        if (!$this->isTemperatureInRange($temperature)) {
            return 'warning';
        }

        return 'none';
    }

    /**
     * Get storage type label
     */
    public function getStorageTypeLabel(): string
    {
        return match($this->storage_type) {
            self::STORAGE_FROZEN => 'Замороженное',
            self::STORAGE_REFRIGERATED => 'Охлажденное',
            self::STORAGE_AMBIENT => 'Комнатное',
            self::STORAGE_HEATED => 'Подогрев',
            default => 'Неизвестно',
        };
    }

    /**
     * Get temperature range as string
     */
    public function getTemperatureRange(): string
    {
        $min = $this->min_temperature_celsius !== null ? $this->min_temperature_celsius . '°C' : '-∞';
        $max = $this->max_temperature_celsius !== null ? $this->max_temperature_celsius . '°C' : '+∞';
        
        return "{$min} ... {$max}";
    }

    /**
     * Get critical temperature range as string
     */
    public function getCriticalRange(): string
    {
        if ($this->critical_min_temperature_celsius === null && $this->critical_max_temperature_celsius === null) {
            return 'Не установлено';
        }

        $min = $this->critical_min_temperature_celsius !== null ? $this->critical_min_temperature_celsius . '°C' : '-∞';
        $max = $this->critical_max_temperature_celsius !== null ? $this->critical_max_temperature_celsius . '°C' : '+∞';
        
        return "{$min} ... {$max}";
    }

    /**
     * Scope for products requiring continuous monitoring
     */
    public function scopeRequiresMonitoring($query)
    {
        return $query->where('requires_continuous_monitoring', true);
    }

    /**
     * Scope for hazardous products
     */
    public function scopeHazardous($query)
    {
        return $query->where('is_hazardous', true);
    }

    /**
     * Scope by storage type
     */
    public function scopeByStorageType($query, string $storageType)
    {
        return $query->where('storage_type', $storageType);
    }
}
