<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;
use Carbon\CarbonImmutable;

final readonly class IoTSecurityEvent
{
    public function __construct(
        public int $id,
        public ?int $iotDeviceId,
        public int $tenantId,
        public IoTSecurityEventType $eventType,
        public string $severity,
        public string $description,
        public ?array $eventData,
        public ?string $sourceIp,
        public ?string $userAgent,
        public ?string $fingerprint,
        public string $actionTaken,
        public ?string $actionDetails,
        public ?CarbonImmutable $resolvedAt,
        public ?string $resolutionNotes,
        public ?string $correlationId,
        public ?int $parentEventId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        ?int $iotDeviceId,
        int $tenantId,
        IoTSecurityEventType $eventType,
        string $description,
        ?array $eventData = null,
        ?string $sourceIp = null,
        ?string $userAgent = null,
        ?string $fingerprint = null,
        ?string $correlationId = null,
        ?int $parentEventId = null,
    ): self {
        return new self(
            id: 0,
            iotDeviceId: $iotDeviceId,
            tenantId: $tenantId,
            eventType: $eventType,
            severity: $eventType->getSeverity(),
            description: $description,
            eventData: $eventData,
            sourceIp: $sourceIp,
            userAgent: $userAgent,
            fingerprint: $fingerprint,
            actionTaken: 'logged_only',
            actionDetails: null,
            resolvedAt: null,
            resolutionNotes: null,
            correlationId: $correlationId,
            parentEventId: $parentEventId,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withAction(string $actionTaken, ?string $actionDetails = null): self
    {
        return new self(
            id: $this->id,
            iotDeviceId: $this->iotDeviceId,
            tenantId: $this->tenantId,
            eventType: $this->eventType,
            severity: $this->severity,
            description: $this->description,
            eventData: $this->eventData,
            sourceIp: $this->sourceIp,
            userAgent: $this->userAgent,
            fingerprint: $this->fingerprint,
            actionTaken: $actionTaken,
            actionDetails: $actionDetails,
            resolvedAt: $this->resolvedAt,
            resolutionNotes: $this->resolutionNotes,
            correlationId: $this->correlationId,
            parentEventId: $this->parentEventId,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function resolve(string $notes): self
    {
        return new self(
            id: $this->id,
            iotDeviceId: $this->iotDeviceId,
            tenantId: $this->tenantId,
            eventType: $this->eventType,
            severity: $this->severity,
            description: $this->description,
            eventData: $this->eventData,
            sourceIp: $this->sourceIp,
            userAgent: $this->userAgent,
            fingerprint: $this->fingerprint,
            actionTaken: $this->actionTaken,
            actionDetails: $this->actionDetails,
            resolvedAt: CarbonImmutable::now(),
            resolutionNotes: $notes,
            correlationId: $this->correlationId,
            parentEventId: $this->parentEventId,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isResolved(): bool
    {
        return $this->resolvedAt !== null;
    }

    public function isCritical(): bool
    {
        return in_array($this->severity, ['critical', 'emergency']);
    }

    public function requiresImmediateAction(): bool
    {
        return $this->eventType->requiresQuarantine() || $this->severity === 'emergency';
    }
}
