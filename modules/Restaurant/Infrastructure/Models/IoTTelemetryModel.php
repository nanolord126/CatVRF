<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Restaurant\Domain\Entities\IoTTelemetry;

final class IoTTelemetryModel extends Model
{
    protected $table = 'iot_telemetry';

    protected $fillable = [
        'iot_device_id',
        'metric_type',
        'value',
        'value_string',
        'value_json',
        'unit',
        'is_alert',
        'alert_message',
        'alert_level',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'is_alert' => 'boolean',
        'value_json' => 'array',
        'recorded_at' => 'immutable_datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IoTDeviceModel::class, 'iot_device_id');
    }

    public function toDomain(): IoTTelemetry
    {
        return new IoTTelemetry(
            id: $this->id,
            iotDeviceId: $this->iot_device_id,
            metricType: $this->metric_type,
            value: $this->value ? (float) $this->value : null,
            valueString: $this->value_string,
            valueJson: $this->value_json,
            unit: $this->unit,
            isAlert: $this->is_alert,
            alertMessage: $this->alert_message,
            recordedAt: $this->recorded_at,
            createdAt: $this->created_at->toImmutable(),
        );
    }

    public static function fromDomain(IoTTelemetry $telemetry): self
    {
        return new self([
            'id' => $telemetry->id > 0 ? $telemetry->id : null,
            'iot_device_id' => $telemetry->iotDeviceId,
            'metric_type' => $telemetry->metricType,
            'value' => $telemetry->value,
            'value_string' => $telemetry->valueString,
            'value_json' => $telemetry->valueJson,
            'unit' => $telemetry->unit,
            'is_alert' => $telemetry->isAlert,
            'alert_message' => $telemetry->alertMessage,
            'recorded_at' => $telemetry->recordedAt,
        ]);
    }

    public function scopeAlerts($query)
    {
        return $query->where('is_alert', true);
    }

    public function scopeByMetric($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeByDevice($query, int $deviceId)
    {
        return $query->where('iot_device_id', $deviceId);
    }

    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }
}
