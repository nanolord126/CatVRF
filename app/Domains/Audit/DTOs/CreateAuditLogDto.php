<?php

declare(strict_types=1);

namespace App\Domains\Audit\DTOs;

/**
 * CreateAuditLogDto — Immutable DTO for audit log creation.
 * Enforces type safety and validation at the DTO level.
 */
final readonly class CreateAuditLogDto
{
    public function __construct(
        public string $action,
        public string $subjectType,
        public ?int $subjectId,
        public array $oldValues = [],
        public array $newValues = [],
        public ?int $userId = null,
        public ?int $tenantId = null,
        public ?int $businessGroupId = null,
        public ?string $ipAddress = null,
        public ?string $deviceFingerprint = null,
        public ?string $correlationId = null,
        public ?string $userAgent = null,
        public ?string $url = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            action: $data['action'],
            subjectType: $data['subject_type'],
            subjectId: $data['subject_id'] ?? null,
            oldValues: $data['old_values'] ?? [],
            newValues: $data['new_values'] ?? [],
            userId: $data['user_id'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            businessGroupId: $data['business_group_id'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            deviceFingerprint: $data['device_fingerprint'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            url: $data['url'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'ip_address' => $this->ipAddress,
            'device_fingerprint' => $this->deviceFingerprint,
            'correlation_id' => $this->correlationId,
            'user_agent' => $this->userAgent,
            'url' => $this->url,
        ];
    }

    public function withCorrelationId(string $correlationId): self
    {
        return new self(
            action: $this->action,
            subjectType: $this->subjectType,
            subjectId: $this->subjectId,
            oldValues: $this->oldValues,
            newValues: $this->newValues,
            userId: $this->userId,
            tenantId: $this->tenantId,
            businessGroupId: $this->businessGroupId,
            ipAddress: $this->ipAddress,
            deviceFingerprint: $this->deviceFingerprint,
            correlationId: $correlationId,
            userAgent: $this->userAgent,
            url: $this->url,
        );
    }
}
