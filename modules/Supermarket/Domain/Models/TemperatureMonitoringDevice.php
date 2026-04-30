<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * TemperatureMonitoringDevice — IoT device for temperature monitoring
 * 
 * Represents physical temperature sensors installed in storage areas
 * with MQTT/HTTP integration for real-time monitoring
 */
final class TemperatureMonitoringDevice extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_temperature_monitoring_devices';

    protected $fillable = [
        'tenant_id',
        'device_id',
        'name',
        'serial_number',
        'manufacturer',
        'model',
        'access_key',
        'access_secret',
        'mqtt_topic',
        'location',
        'zone',
        'coordinates',
        'reporting_interval_seconds',
        'accuracy_celsius',
        'status',
        'last_seen_at',
        'last_calibration_at',
        'next_calibration_due_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'metadata' => 'array',
        'reporting_interval_seconds' => 'integer',
        'accuracy_celsius' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'last_calibration_at' => 'datetime',
        'next_calibration_due_at' => 'datetime',
    ];

    protected $hidden = [
        'access_secret',
    ];

    // Device statuses
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_DECOMMISSIONED = 'decommissioned';

    /**
     * Boot method to generate access key
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->access_key)) {
                $device->access_key = self::generateAccessKey();
            }
        });
    }

    /**
     * Relationships
     */
    public function readings(): HasMany
    {
        return $this->hasMany(TemperatureReading::class, 'device_id')
            ->orderBy('recorded_at', 'desc');
    }

    public function latestReading(): HasMany
    {
        return $this->hasMany(TemperatureReading::class, 'device_id')
            ->orderBy('recorded_at', 'desc')
            ->limit(1);
    }

    /**
     * Generate unique access key for device
     */
    public static function generateAccessKey(): string
    {
        return 'temp_' . Str::random(32);
    }

    /**
     * Generate and set access secret
     */
    public function generateAccessSecret(): string
    {
        $this->access_secret = encrypt(Str::random(64));
        $this->save();
        
        return $this->access_secret;
    }

    /**
     * Verify access secret
     */
    public function verifyAccessSecret(string $secret): bool
    {
        if (!$this->access_secret) {
            return false;
        }

        try {
            $decrypted = decrypt($this->access_secret);
            return hash_equals($decrypted, $secret);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): bool
    {
        $this->last_seen_at = now();
        
        // Auto-update status from offline to active
        if ($this->status === self::STATUS_OFFLINE) {
            $this->status = self::STATUS_ACTIVE;
        }

        return $this->save();
    }

    /**
     * Check if device is online
     */
    public function isOnline(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }

        // Consider device offline if not seen for 3x reporting interval
        $offlineThreshold = $this->reporting_interval_seconds * 3;
        return $this->last_seen_at->diffInSeconds(now()) < $offlineThreshold;
    }

    /**
     * Check if calibration is due
     */
    public function isCalibrationDue(): bool
    {
        if (!$this->next_calibration_due_at) {
            return false;
        }

        return $this->next_calibration_due_at->isPast();
    }

    /**
     * Get latest temperature reading
     */
    public function getLatestReading(): ?TemperatureReading
    {
        return $this->readings()->first();
    }

    /**
     * Get average temperature for a time period
     */
    public function getAverageTemperature(\Carbon\Carbon $from, \Carbon\Carbon $to): ?float
    {
        return $this->readings()
            ->whereBetween('recorded_at', [$from, $to])
            ->avg('temperature_celsius');
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_INACTIVE => 'Неактивен',
            self::STATUS_MAINTENANCE => 'Обслуживание',
            self::STATUS_OFFLINE => 'Офлайн',
            self::STATUS_DECOMMISSIONED => 'Списан',
            default => 'Неизвестно',
        };
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for online devices
     */
    public function scopeOnline($query)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes(15));
    }

    /**
     * Scope by location
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    /**
     * Scope by zone
     */
    public function scopeByZone($query, string $zone)
    {
        return $query->where('zone', $zone);
    }

    /**
     * Scope for devices requiring calibration
     */
    public function scopeCalibrationDue($query)
    {
        return $query->where('next_calibration_due_at', '<=', now());
    }
}
