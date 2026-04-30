<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Restaurant\Domain\Entities\IoTSecurityEvent as IoTSecurityEventEntity;
use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;

final class IoTSecurityEventModel extends Model
{
    protected $table = 'iot_security_events';

    protected $fillable = [
        'iot_device_id',
        'tenant_id',
        'event_type',
        'severity',
        'description',
        'event_data',
        'source_ip',
        'user_agent',
        'fingerprint',
        'action_taken',
        'action_details',
        'resolved_at',
        'resolution_notes',
        'correlation_id',
        'parent_event_id',
    ];

    protected $casts = [
        'event_data' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IoTDeviceModel::class, 'iot_device_id');
    }

    public function toDomain(): IoTSecurityEventEntity
    {
        return new IoTSecurityEventEntity(
            id: $this->id,
            iotDeviceId: $this->iot_device_id,
            tenantId: $this->tenant_id,
            eventType: IoTSecurityEventType::from($this->event_type),
            severity: $this->severity,
            description: $this->description,
            eventData: $this->event_data,
            sourceIp: $this->source_ip,
            userAgent: $this->user_agent,
            fingerprint: $this->fingerprint,
            actionTaken: $this->action_taken,
            actionDetails: $this->action_details,
            resolvedAt: $this->resolved_at ? \Carbon\CarbonImmutable::parse($this->resolved_at) : null,
            resolutionNotes: $this->resolution_notes,
            correlationId: $this->correlation_id,
            parentEventId: $this->parent_event_id,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(IoTSecurityEventEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'iot_device_id' => $entity->iotDeviceId,
            'tenant_id' => $entity->tenantId,
            'event_type' => $entity->eventType->value,
            'severity' => $entity->severity,
            'description' => $entity->description,
            'event_data' => $entity->eventData,
            'source_ip' => $entity->sourceIp,
            'user_agent' => $entity->userAgent,
            'fingerprint' => $entity->fingerprint,
            'action_taken' => $entity->actionTaken,
            'action_details' => $entity->actionDetails,
            'resolved_at' => $entity->resolvedAt,
            'resolution_notes' => $entity->resolutionNotes,
            'correlation_id' => $entity->correlationId,
            'parent_event_id' => $entity->parentEventId,
        ]);
    }
}
