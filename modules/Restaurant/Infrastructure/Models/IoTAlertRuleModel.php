<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Restaurant\Domain\Enums\IoTAlertSeverity;

final class IoTAlertRuleModel extends Model
{
    protected $table = 'iot_alert_rules';

    protected $fillable = [
        'tenant_id',
        'iot_device_id',
        'name',
        'metric_type',
        'condition',
        'threshold_min',
        'threshold_max',
        'threshold_string',
        'severity',
        'is_active',
        'notification_config',
        'description',
    ];

    protected $casts = [
        'threshold_min' => 'decimal:6',
        'threshold_max' => 'decimal:6',
        'severity' => IoTAlertSeverity::class,
        'is_active' => 'boolean',
        'notification_config' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('iot_alert_rules.tenant_id', tenant()->id);
            }
        });
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IoTDeviceModel::class, 'iot_device_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDevice($query, int $deviceId)
    {
        return $query->where('iot_device_id', $deviceId);
    }

    public function scopeByMetric($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeBySeverity($query, IoTAlertSeverity $severity)
    {
        return $query->where('severity', $severity);
    }
}
