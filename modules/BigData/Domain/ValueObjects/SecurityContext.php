<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\ValueObjects;

use Modules\BigData\Domain\Enums\AccessDecision;
use Modules\BigData\Domain\Enums\DataClassification;

/**
 * Security Context Value Object
 *
 * Immutable context for every BigData access decision.
 * Carries user, seller, tenant, classification, and decision metadata.
 */
final readonly class SecurityContext
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $sellerId,
        public readonly ?int $tenantId,
        public readonly DataClassification $classification,
        public readonly AccessDecision $decision,
        public readonly string $resource,
        public readonly string $action,
        public readonly string $correlationId,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly array $abacContext = [],
        public readonly \DateTimeImmutable $evaluatedAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Create from request context
     */
    public static function fromRequest(
        int $userId,
        string $resource,
        string $action,
        DataClassification $classification,
        ?int $sellerId = null,
        ?int $tenantId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            userId: $userId,
            sellerId: $sellerId,
            tenantId: $tenantId,
            classification: $classification,
            decision: AccessDecision::Allow, // default, will be evaluated
            resource: $resource,
            action: $action,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    /**
     * Create with a specific access decision
     */
    public function withDecision(AccessDecision $decision): self
    {
        return new self(
            userId: $this->userId,
            sellerId: $this->sellerId,
            tenantId: $this->tenantId,
            classification: $this->classification,
            decision: $decision,
            resource: $this->resource,
            action: $this->action,
            correlationId: $this->correlationId,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            abacContext: $this->abacContext,
            evaluatedAt: $this->evaluatedAt,
        );
    }

    /**
     * Create with ABAC context
     */
    public function withABACContext(array $context): self
    {
        return new self(
            userId: $this->userId,
            sellerId: $this->sellerId,
            tenantId: $this->tenantId,
            classification: $this->classification,
            decision: $this->decision,
            resource: $this->resource,
            action: $this->action,
            correlationId: $this->correlationId,
            ipAddress: $this->ipAddress,
            userAgent: $this->userAgent,
            abacContext: $context,
            evaluatedAt: $this->evaluatedAt,
        );
    }

    /**
     * Is this access allowed?
     */
    public function isAllowed(): bool
    {
        return $this->decision->isAllowed();
    }

    /**
     * Does this context require audit logging?
     */
    public function requiresAudit(): bool
    {
        return $this->classification->requiresAuditLog()
            || $this->decision === AccessDecision::DenyWithAudit;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            'classification' => $this->classification->value,
            'decision' => $this->decision->value,
            'resource' => $this->resource,
            'action' => $this->action,
            'correlation_id' => $this->correlationId,
            'ip_address' => $this->ipAddress,
            'evaluated_at' => $this->evaluatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
