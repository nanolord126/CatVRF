<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TemperatureReading — Individual temperature reading from monitoring device
 * 
 * Stores temperature and humidity data with violation detection
 * for compliance tracking and alerting
 */
final class TemperatureReading extends Model
{
    protected $table = 'supermarket_temperature_readings';

    protected $fillable = [
        'device_id',
        'tenant_id',
        'temperature_celsius',
        'humidity_percent',
        'recorded_at',
        'received_at',
        'correlation_id',
        'is_violation',
        'violation_severity',
        'product_requirement_id',
        'alert_sent',
        'alert_sent_at',
        'raw_data',
    ];

    protected $casts = [
        'temperature_celsius' => 'decimal:2',
        'humidity_percent' => 'decimal:2',
        'recorded_at' => 'datetime',
        'received_at' => 'datetime',
        'is_violation' => 'boolean',
        'alert_sent' => 'boolean',
        'alert_sent_at' => 'datetime',
        'raw_data' => 'array',
    ];

    // Violation severities
    public const SEVERITY_NONE = 'none';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Relationships
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(TemperatureMonitoringDevice::class, 'device_id');
    }

    public function productRequirement(): BelongsTo
    {
        return $this->belongsTo(ProductTemperatureRequirement::class, 'product_requirement_id');
    }

    /**
     * Check if reading is a violation
     */
    public function checkViolation(?ProductTemperatureRequirement $requirement = null): void
    {
        $requirement = $requirement ?? $this->productRequirement;

        if (!$requirement) {
            $this->is_violation = false;
            $this->violation_severity = self::SEVERITY_NONE;
            return;
        }

        $this->violation_severity = $requirement->getViolationSeverity($this->temperature_celsius);
        $this->is_violation = $this->violation_severity !== self::SEVERITY_NONE;
    }

    /**
     * Mark alert as sent
     */
    public function markAlertSent(): bool
    {
        $this->alert_sent = true;
        $this->alert_sent_at = now();
        return $this->save();
    }

    /**
     * Get temperature in Fahrenheit
     */
    public function getTemperatureFahrenheit(): float
    {
        return ($this->temperature_celsius * 9/5) + 32;
    }

    /**
     * Get temperature formatted string
     */
    public function getTemperatureFormatted(): string
    {
        return number_format($this->temperature_celsius, 2) . '°C';
    }

    /**
     * Get violation severity label
     */
    public function getViolationSeverityLabel(): string
    {
        return match($this->violation_severity) {
            self::SEVERITY_CRITICAL => 'Критическое',
            self::SEVERITY_WARNING => 'Предупреждение',
            self::SEVERITY_NONE => 'Нет',
            default => 'Неизвестно',
        };
    }

    /**
     * Get violation severity color class
     */
    public function getViolationSeverityColor(): string
    {
        return match($this->violation_severity) {
            self::SEVERITY_CRITICAL => 'text-red-600',
            self::SEVERITY_WARNING => 'text-yellow-600',
            self::SEVERITY_NONE => 'text-green-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Scope for violations
     */
    public function scopeViolations($query)
    {
        return $query->where('is_violation', true);
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('violation_severity', $severity);
    }

    /**
     * Scope for critical violations
     */
    public function scopeCritical($query)
    {
        return $query->where('violation_severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope for warnings
     */
    public function scopeWarnings($query)
    {
        return $query->where('violation_severity', self::SEVERITY_WARNING);
    }

    /**
     * Scope for readings without alerts
     */
    public function scopeWithoutAlerts($query)
    {
        return $query->where('alert_sent', false);
    }

    /**
     * Scope by time range
     */
    public function scopeBetweenDates($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    /**
     * Scope by device
     */
    public function scopeByDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }
}
