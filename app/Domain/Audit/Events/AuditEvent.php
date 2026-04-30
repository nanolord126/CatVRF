<?php

declare(strict_types=1);

namespace App\Domain\Audit\Events;

/**
 * Domain Event - Audit Event
 * 
 * Domain event for audit logging across all verticals.
 * Fired when any auditable action occurs in the system.
 * 
 * Architecture: Domain Layer - Domain Event
 * Decouples business logic from audit infrastructure implementation.
 * 
 * Usage:
 *   Event::dispatch(new AuditEvent(
 *       action: 'order_created',
 *       subjectType: 'Order',
 *       subjectId: $order->id,
 *       oldValues: [],
 *       newValues: $orderData,
 *       context: ['user_id' => $userId],
 *       correlationId: $correlationId
 *   ));
 * 
 * @see \App\Infrastructure\Listeners\AuditEventListener
 * @see \App\Domain\Audit\Interfaces\AuditServiceInterface
 */
final readonly class AuditEvent
{
    /**
     * @param  string  $action  Action performed (e.g., 'order_created', 'payment_succeeded')
     * @param  string  $subjectType  Fully qualified class name of the subject
     * @param  int|null  $subjectId  ID of the subject entity
     * @param  array  $oldValues  Values before the action (for updates/deletes)
     * @param  array  $newValues  Values after the action (for creates/updates)
     * @param  array  $context  Additional context data (user_id, tenant_id, etc.)
     * @param  string  $correlationId  Distributed tracing ID
     */
    public function __construct(
        public readonly string $action,
        public readonly string $subjectType,
        public readonly ?int $subjectId,
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
        public readonly array $context = [],
        public readonly string $correlationId,
    ) {
        // Validate required fields
        if (empty($this->action)) {
            throw new \InvalidArgumentException('Action cannot be empty');
        }
        
        if (empty($this->subjectType)) {
            throw new \InvalidArgumentException('Subject type cannot be empty');
        }
        
        if (empty($this->correlationId)) {
            throw new \InvalidArgumentException('Correlation ID cannot be empty');
        }
    }

    /**
     * Create audit event for entity creation
     */
    public static function created(
        string $subjectType,
        int $subjectId,
        array $newValues,
        array $context = [],
        string $correlationId
    ): self {
        return new self(
            action: "{$subjectType}_created",
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: [],
            newValues: $newValues,
            context: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Create audit event for entity update
     */
    public static function updated(
        string $subjectType,
        int $subjectId,
        array $oldValues,
        array $newValues,
        array $context = [],
        string $correlationId
    ): self {
        return new self(
            action: "{$subjectType}_updated",
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $oldValues,
            newValues: $newValues,
            context: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Create audit event for entity deletion
     */
    public static function deleted(
        string $subjectType,
        int $subjectId,
        array $oldValues,
        array $context = [],
        string $correlationId
    ): self {
        return new self(
            action: "{$subjectType}_deleted",
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $oldValues,
            newValues: [],
            context: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Create audit event for custom action
     */
    public static function action(
        string $action,
        string $subjectType,
        ?int $subjectId,
        array $context,
        string $correlationId
    ): self {
        return new self(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: [],
            newValues: [],
            context: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Get user ID from context
     */
    public function getUserId(): ?int
    {
        return $this->context['user_id'] ?? null;
    }

    /**
     * Get tenant ID from context
     */
    public function getTenantId(): ?int
    {
        return $this->context['tenant_id'] ?? null;
    }

    /**
     * Get business group ID from context
     */
    public function getBusinessGroupId(): ?int
    {
        return $this->context['business_group_id'] ?? null;
    }

    /**
     * Check if this is a payment event
     */
    public function isPaymentEvent(): bool
    {
        return str_starts_with($this->action, 'payment_');
    }

    /**
     * Check if this is a fraud check event
     */
    public function isFraudCheckEvent(): bool
    {
        return str_starts_with($this->action, 'fraud_');
    }

    /**
     * Check if this is a wallet event
     */
    public function isWalletEvent(): bool
    {
        return str_starts_with($this->action, 'wallet_');
    }
}
