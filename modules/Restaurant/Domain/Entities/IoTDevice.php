<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\Enums\IoTDeviceType;
use Modules\Restaurant\Domain\Enums\IoTProtocol;
use Modules\Restaurant\Domain\Enums\IoTSecurityStatus;
use Carbon\CarbonImmutable;

final readonly class IoTDevice
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $kitchenStationId,
        public string $deviceIdentifier,
        public string $name,
        public IoTDeviceType $type,
        public IoTProtocol $protocol,
        public ?string $connectionConfig,
        public ?string $brokerUrl,
        public ?string $topicPrefix,
        public bool $isOnline,
        public ?CarbonImmutable $lastSeenAt,
        public bool $isActive,
        public IoTSecurityStatus $securityStatus,
        public ?CarbonImmutable $quarantinedAt,
        public ?string $quarantineReason,
        public ?int $quarantinedBy,
        public string $securityLevel,
        public ?array $securityMetadata,
        public int $rateLimitPerMinute,
        public ?CarbonImmutable $rateLimitResetAt,
        public ?CarbonImmutable $lastSecurityCheckAt,
        public ?string $lastSecurityCheckResult,
        public ?array $metadata,
        public ?string $description,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $deviceIdentifier,
        string $name,
        IoTDeviceType $type,
        IoTProtocol $protocol,
        ?int $kitchenStationId = null,
        ?string $connectionConfig = null,
        ?string $brokerUrl = null,
        ?string $topicPrefix = null,
        ?array $metadata = null,
        ?string $description = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            kitchenStationId: $kitchenStationId,
            deviceIdentifier: $deviceIdentifier,
            name: $name,
            type: $type,
            protocol: $protocol,
            connectionConfig: $connectionConfig,
            brokerUrl: $brokerUrl,
            topicPrefix: $topicPrefix,
            isOnline: false,
            lastSeenAt: null,
            isActive: true,
            securityStatus: IoTSecurityStatus::ACTIVE,
            quarantinedAt: null,
            quarantineReason: null,
            quarantinedBy: null,
            securityLevel: 'standard',
            securityMetadata: null,
            rateLimitPerMinute: 60,
            rateLimitResetAt: null,
            lastSecurityCheckAt: null,
            lastSecurityCheckResult: null,
            metadata: $metadata,
            description: $description,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withOnlineStatus(bool $isOnline, ?CarbonImmutable $lastSeenAt = null): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            kitchenStationId: $this->kitchenStationId,
            deviceIdentifier: $this->deviceIdentifier,
            name: $this->name,
            type: $this->type,
            protocol: $this->protocol,
            connectionConfig: $this->connectionConfig,
            brokerUrl: $this->brokerUrl,
            topicPrefix: $this->topicPrefix,
            isOnline: $isOnline,
            lastSeenAt: $lastSeenAt ?? CarbonImmutable::now(),
            isActive: $this->isActive,
            metadata: $this->metadata,
            description: $this->description,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withActivation(bool $isActive): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            kitchenStationId: $this->kitchenStationId,
            deviceIdentifier: $this->deviceIdentifier,
            name: $this->name,
            type: $this->type,
            protocol: $this->protocol,
            connectionConfig: $this->connectionConfig,
            brokerUrl: $this->brokerUrl,
            topicPrefix: $this->topicPrefix,
            isOnline: $this->isOnline,
            lastSeenAt: $this->lastSeenAt,
            isActive: $isActive,
            metadata: $this->metadata,
            description: $this->description,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withStation(?int $kitchenStationId): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            kitchenStationId: $kitchenStationId,
            deviceIdentifier: $this->deviceIdentifier,
            name: $this->name,
            type: $this->type,
            protocol: $this->protocol,
            connectionConfig: $this->connectionConfig,
            brokerUrl: $this->brokerUrl,
            topicPrefix: $this->topicPrefix,
            isOnline: $this->isOnline,
            lastSeenAt: $this->lastSeenAt,
            isActive: $this->isActive,
            metadata: $this->metadata,
            description: $this->description,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isOfflineFor(int $minutes): bool
    {
        if ($this->isOnline || $this->lastSeenAt === null) {
            return false;
        }

        return $this->lastSeenAt->diffInMinutes(CarbonImmutable::now()) >= $minutes;
    }

    public function withSecurityStatus(IoTSecurityStatus $securityStatus): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            kitchenStationId: $this->kitchenStationId,
            deviceIdentifier: $this->deviceIdentifier,
            name: $this->name,
            type: $this->type,
            protocol: $this->protocol,
            connectionConfig: $this->connectionConfig,
            brokerUrl: $this->brokerUrl,
            topicPrefix: $this->topicPrefix,
            isOnline: $this->isOnline,
            lastSeenAt: $this->lastSeenAt,
            isActive: $this->isActive,
            securityStatus: $securityStatus,
            quarantinedAt: $securityStatus === IoTSecurityStatus::QUARANTINED 
                ? CarbonImmutable::now() 
                : $this->quarantinedAt,
            quarantineReason: $this->quarantineReason,
            quarantinedBy: $this->quarantinedBy,
            securityLevel: $this->securityLevel,
            securityMetadata: $this->securityMetadata,
            rateLimitPerMinute: $this->rateLimitPerMinute,
            rateLimitResetAt: $this->rateLimitResetAt,
            lastSecurityCheckAt: CarbonImmutable::now(),
            lastSecurityCheckResult: $securityStatus->value,
            metadata: $this->metadata,
            description: $this->description,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
