<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;
use Modules\Restaurant\Domain\Enums\IoTSecurityStatus;

final class IoTDeviceModel extends Model
{
    protected $table = 'iot_devices';

    protected $fillable = [
        'tenant_id',
        'kitchen_station_id',
        'device_identifier',
        'name',
        'type',
        'protocol',
        'connection_config',
        'broker_url',
        'topic_prefix',
        'is_online',
        'last_seen_at',
        'is_active',
        'metadata',
        'description',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'immutable_datetime',
        'metadata' => 'array',
        'type' => IoTDeviceType::class,
        'protocol' => IoTProtocol::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant() !== null) {
                $query->where('iot_devices.tenant_id', tenant()->id);
            }
        });
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStationModel::class, 'kitchen_station_id');
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(IoTTelemetryModel::class, 'iot_device_id');
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(IoTAlertRuleModel::class, 'iot_device_id');
    }

    public function toDomain(): IoTDevice
    {
        return new IoTDevice(
            id: $this->id,
            tenantId: $this->tenant_id,
            kitchenStationId: $this->kitchen_station_id,
            deviceIdentifier: $this->device_identifier,
            name: $this->name,
            type: $this->type,
            protocol: $this->protocol,
            connectionConfig: $this->connection_config,
            brokerUrl: $this->broker_url,
            topicPrefix: $this->topic_prefix,
            isOnline: $this->is_online,
            lastSeenAt: $this->last_seen_at,
            isActive: $this->is_active,
            securityStatus: $this->security_status ?? IoTSecurityStatus::ACTIVE,
            quarantinedAt: $this->quarantined_at,
            quarantineReason: $this->quarantine_reason,
            quarantinedBy: $this->quarantined_by,
            securityLevel: $this->security_level ?? 'standard',
            securityMetadata: $this->security_metadata,
            rateLimitPerMinute: $this->rate_limit_per_minute ?? 60,
            rateLimitResetAt: $this->rate_limit_reset_at,
            lastSecurityCheckAt: $this->last_security_check_at,
            lastSecurityCheckResult: $this->last_security_check_result,
            metadata: $this->metadata,
            description: $this->description,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }

    public static function fromDomain(IoTDevice $device): self
    {
        return new self([
            'id' => $device->id > 0 ? $device->id : null,
            'tenant_id' => $device->tenantId,
            'kitchen_station_id' => $device->kitchenStationId,
            'device_identifier' => $device->deviceIdentifier,
            'name' => $device->name,
            'type' => $device->type,
            'protocol' => $device->protocol,
            'connection_config' => $device->connectionConfig,
            'broker_url' => $device->brokerUrl,
            'topic_prefix' => $device->topicPrefix,
            'is_online' => $device->isOnline,
            'last_seen_at' => $device->lastSeenAt,
            'is_active' => $device->isActive,
            'metadata' => $device->metadata,
            'description' => $device->description,
        ]);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, IoTDeviceType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProtocol($query, IoTProtocol $protocol)
    {
        return $query->where('protocol', $protocol);
    }
}
    public function scopeByType($query, IoTDeviceType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProtocol($query, IoTProtocol $protocol)
    {
        return $query->where('protocol', $protocol);
    }
}
    public function scopeByType($query, IoTDeviceType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProtocol($query, IoTProtocol $protocol)
    {
        return $query->where('protocol', $protocol);
    }
}
    public function scopeByProtocol($query, IoTProtocol $protocol)
    {
        return $query->where('protocol', $protocol);
    }
}
