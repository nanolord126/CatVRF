<?php

declare(strict_types=1);

namespace App\Traits;

use App\Domains\Audit\Services\AuditService;
use Illuminate\Support\Str;

/**
 * Trait WithAuditLogging - Production Ready
 *
 * @deprecated 2026-04 Use Domain Events (App\Domain\Audit\Events\AuditEvent) instead.
 *             This trait is being replaced by Clean Architecture approach where
 *             Domain/Services dispatch AuditEvent and Infrastructure\Listeners handle persistence.
 *             See app/Domain/Audit/Events/AuditEvent.php for the new pattern.
 *
 * Provides convenient methods for audit logging across all verticals.
 * Simplifies integration of canonical AuditService in vertical services.
 *
 * Features:
 * - Automatic correlation ID generation
 * - Automatic user/tenant ID extraction from context
 * - Type-safe methods for all common audit events
 * - Support for custom actions
 * - Payment and authentication logging helpers
 * - Fraud detection logging
 * - Error logging with context
 *
 * Usage:
 *   use App\Traits\WithAuditLogging;
 *
 *   public function __construct(
 *       private readonly AuditService $auditService,
 *   ) {}
 *
 *   $this->logCreated('Order', $orderId, ['amount' => 100]);
 *   $this->logUpdated('User', $userId, ['email' => $oldEmail], ['email' => $newEmail]);
 *   $this->logPayment('init', 1000, 'RUB', 'pending', $orderId);
 *
 * @property-read AuditService $auditService
 * @see \App\Services\AuditService
 * @see \App\Models\AuditLog
 * @see \App\Domain\Audit\Events\AuditEvent (replacement)
 */
trait WithAuditLogging
{
    /**
     * Log creation event for an entity
     *
     * @param  string  $entityType  Entity type (e.g., 'Order', 'User', 'Appointment')
     * @param  int  $entityId  Entity ID
     * @param  array  $context  Additional context data
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logCreated(
        string $entityType,
        int $entityId,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $this->auditService->record(
            action: "{$entityType}_created",
            subjectType: $entityType,
            subjectId: $entityId,
            oldValues: [],
            newValues: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Log update event for an entity with before/after values
     *
     * @param  string  $entityType  Entity type
     * @param  int  $entityId  Entity ID
     * @param  array  $oldValues  Values before update
     * @param  array  $newValues  Values after update
     * @param  array  $context  Additional context
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logUpdated(
        string $entityType,
        int $entityId,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $this->auditService->record(
            action: "{$entityType}_updated",
            subjectType: $entityType,
            subjectId: $entityId,
            oldValues: array_merge($oldValues, $context),
            newValues: $newValues,
            correlationId: $correlationId
        );
    }

    /**
     * Log deletion event for an entity
     *
     * @param  string  $entityType  Entity type
     * @param  int  $entityId  Entity ID
     * @param  array  $context  Additional context (e.g., deleted values)
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logDeleted(
        string $entityType,
        int $entityId,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $this->auditService->record(
            action: "{$entityType}_deleted",
            subjectType: $entityType,
            subjectId: $entityId,
            oldValues: $context,
            newValues: [],
            correlationId: $correlationId
        );
    }

    /**
     * Log custom action event for an entity
     *
     * @param  string  $action  Action name (e.g., 'approved', 'rejected', 'cancelled')
     * @param  string  $entityType  Entity type
     * @param  int|null  $entityId  Entity ID (null if not applicable)
     * @param  array  $context  Additional context data
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logAction(
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $this->auditService->record(
            action: "{$entityType}_{$action}",
            subjectType: $entityType,
            subjectId: $entityId,
            oldValues: [],
            newValues: $context,
            correlationId: $correlationId
        );
    }

    /**
     * Log payment event
     *
     * @param  string  $paymentType  Payment type (init, capture, refund, void)
     * @param  int  $amount  Amount in minor units (e.g., 1000 = 10.00)
     * @param  string  $currency  Currency code (default: RUB)
     * @param  string  $status  Payment status (pending, success, failed)
     * @param  array  $context  Additional context (payment_id, gateway, etc.)
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logPayment(
        string $paymentType,
        int $amount,
        string $currency = 'RUB',
        string $status = 'pending',
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $paymentData = array_merge($context, [
            'payment_type' => $paymentType,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
        ]);

        $this->auditService->logPayment(
            action: $paymentType,
            paymentData: $paymentData,
            correlationId: $correlationId
        );
    }

    /**
     * Log wallet operation
     *
     * @param  string  $action  Wallet action (hold, release, credit, debit)
     * @param  int  $walletId  Wallet ID
     * @param  int  $amount  Amount in minor units
     * @param  string  $reason  Operation reason
     * @param  array  $context  Additional context
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logWallet(
        string $action,
        int $walletId,
        int $amount,
        string $reason,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $walletData = array_merge($context, [
            'wallet_id' => $walletId,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        $this->auditService->logWallet(
            action: $action,
            walletData: $walletData,
            correlationId: $correlationId
        );
    }

    /**
     * Log fraud check result
     *
     * @param  string  $operationType  Operation type (payment, registration, login)
     * @param  float  $score  Fraud score (0.0 - 1.0)
     * @param  string  $decision  Decision (approve, reject, review)
     * @param  array  $context  Additional context
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logFraudCheck(
        string $operationType,
        float $score,
        string $decision,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $checkData = array_merge($context, [
            'operation_type' => $operationType,
            'score' => $score,
            'decision' => $decision,
            'user_id' => $userId,
        ]);

        $this->auditService->logFraudCheck(
            checkData: $checkData,
            correlationId: $correlationId
        );
    }

    /**
     * Log promo code application
     *
     * @param  string  $code  Promo code
     * @param  int  $discountAmount  Discount amount in minor units
     * @param  int  $orderAmount  Order amount in minor units
     * @param  string  $vertical  Vertical name
     * @param  array  $context  Additional context
     * @param  int|null  $userId  User ID (auto-detected if null)
     * @param  int|null  $tenantId  Tenant ID (auto-detected if null)
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logPromo(
        string $code,
        int $discountAmount,
        int $orderAmount,
        string $vertical,
        array $context = [],
        ?int $userId = null,
        ?int $tenantId = null,
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();
        $userId ??= $this->getCurrentUserId();
        $tenantId ??= $this->getCurrentTenantId();

        $promoData = array_merge($context, [
            'code' => $code,
            'discount_amount' => $discountAmount,
            'order_amount' => $orderAmount,
            'vertical' => $vertical,
        ]);

        $this->auditService->logPromo(
            promoData: $promoData,
            correlationId: $correlationId
        );
    }

    /**
     * Log error with context
     *
     * @param  string  $operation  Operation name
     * @param  \Exception  $exception  Exception thrown
     * @param  array  $context  Additional context
     * @param  string|null  $correlationId  Correlation ID (auto-generated if null)
     */
    protected function logError(
        string $operation,
        \Exception $exception,
        array $context = [],
        ?string $correlationId = null
    ): void {
        $correlationId ??= $this->generateCorrelationId();

        $this->auditService->logError(
            operation: $operation,
            exception: $exception,
            correlationId: $correlationId,
            context: $context
        );
    }

    /**
     * Generate unique correlation ID for distributed tracing
     *
     * @return string
     */
    protected function generateCorrelationId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Get current user ID from authenticated session
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        if (function_exists('auth') && auth()->check()) {
            return auth()->id();
        }

        return null;
    }

    /**
     * Get current tenant ID from tenancy context
     *
     * @return int|null
     */
    protected function getCurrentTenantId(): ?int
    {
        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }

        return null;
    }

    /**
     * Check if audit logging is enabled
     *
     * @return bool
     */
    protected function isAuditEnabled(): bool
    {
        return $this->auditService->isEnabled();
    }

    /**
     * Check if async audit logging is enabled
     *
     * @return bool
     */
    protected function isAuditAsync(): bool
    {
        return $this->auditService->isAsync();
    }
}
