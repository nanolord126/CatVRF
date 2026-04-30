<?php

declare(strict_types=1);

namespace App\Domains\Audit\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use App\Domains\Audit\DTOs\CreateAuditLogDto;
use App\Domains\Audit\Jobs\AsyncAuditLogger;
use App\Domains\Audit\Models\AuditLog;
use Carbon\Carbon;

/**
 * AuditService — Centralized audit logging service.
 * Production-ready with async queue, field masking, and retention policy.
 *
 * Features:
 * - Async queue-based logging (Redis/Database)
 * - Multi-tenant support
 * - Field masking for sensitive data
 * - Correlation ID for distributed tracing
 * - Retention policy with automatic cleanup
 * - GDPR compliance with deletion support
 */
final readonly class AuditService
{
    public function __construct(
        private readonly BusDispatcher $bus,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Request $request,
    ) {}

    /**
     * Record audit log entry (async via Job)
     */
    public function record(
        string $action,
        string $subjectType,
        ?int $subjectId,
        array $oldValues = [],
        array $newValues = [],
        ?string $correlationId = null,
    ): void {
        if (! $this->isEnabled()) {
            return;
        }

        $correlationId ??= Str::uuid()->toString();

        $dto = new CreateAuditLogDto(
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $this->maskSensitiveData($oldValues),
            newValues: $this->maskSensitiveData($newValues),
            userId: $this->getCurrentUserId(),
            tenantId: $this->getCurrentTenantId(),
            businessGroupId: $this->request->get('business_group_id'),
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->generateDeviceFingerprint(),
            correlationId: $correlationId,
            userAgent: $this->request->userAgent(),
            url: $this->request->fullUrl(),
        );

        $this->logger->info($action, [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'correlation_id' => $correlationId,
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
        ]);

        if ($this->isAsync()) {
            AsyncAuditLogger::dispatch($dto->toArray())->onQueue('audit-logs');
        } else {
            $this->syncRecord($dto);
        }
    }

    /**
     * Record audit log from DTO
     */
    public function recordFromDto(CreateAuditLogDto $dto): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $dto = $dto->withCorrelationId($dto->correlationId ?? Str::uuid()->toString());

        if ($this->isAsync()) {
            AsyncAuditLogger::dispatch($dto->toArray())->onQueue('audit-logs');
        } else {
            $this->syncRecord($dto);
        }
    }

    /**
     * Get audit logs for subject
     */
    public function getLogsForSubject(string $subjectType, ?int $subjectId = null, int $limit = 100)
    {
        return AuditLog::bySubject($subjectType, $subjectId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by correlation ID
     */
    public function getLogsByCorrelationId(string $correlationId)
    {
        return AuditLog::byCorrelationId($correlationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get audit logs for user
     */
    public function getLogsForUser(int $userId, int $limit = 100)
    {
        return AuditLog::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for tenant
     */
    public function getLogsForTenant(int $tenantId, int $limit = 100)
    {
        return AuditLog::forTenant($tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audit logs by payload
     */
    public function searchByPayload(string $searchTerm, int $limit = 100)
    {
        return AuditLog::searchPayload($searchTerm)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs in date range
     */
    public function getLogsInDateRange(Carbon $from, Carbon $to, int $limit = 100)
    {
        return AuditLog::inDateRange($from, $to)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Delete audit logs for user (GDPR compliance)
     */
    public function deleteLogsForUser(int $userId): int
    {
        return AuditLog::forUser($userId)->delete();
    }

    /**
     * Delete audit logs for subject
     */
    public function deleteLogsForSubject(string $subjectType, ?int $subjectId = null): int
    {
        return AuditLog::bySubject($subjectType, $subjectId)->delete();
    }

    /**
     * Delete audit logs by correlation ID
     */
    public function deleteLogsByCorrelationId(string $correlationId): int
    {
        return AuditLog::byCorrelationId($correlationId)->delete();
    }

    /**
     * Delete audit logs older than retention period
     */
    public function pruneOldLogs(): int
    {
        $retentionMonths = config('audit.retention_months', 12);
        
        return AuditLog::where('created_at', '<=', now()->subMonths($retentionMonths))
            ->delete();
    }

    /**
     * Check if audit logging is enabled
     */
    public function isEnabled(): bool
    {
        return config('audit.enabled', true);
    }

    /**
     * Check if async audit logging is enabled
     */
    public function isAsync(): bool
    {
        return config('audit.async', true);
    }

    /**
     * Get retention period in months
     */
    public function getRetentionMonths(): int
    {
        return config('audit.retention_months', 12);
    }

    /**
     * Log payment event
     */
    public function logPayment(
        string $action,
        array $paymentData,
        ?string $correlationId = null,
    ): void {
        $this->record(
            action: "payment_{$action}",
            subjectType: 'Payment',
            subjectId: $paymentData['payment_id'] ?? null,
            oldValues: [],
            newValues: $paymentData,
            correlationId: $correlationId,
        );
    }

    /**
     * Log wallet operation
     */
    public function logWallet(
        string $action,
        array $walletData,
        ?string $correlationId = null,
    ): void {
        $this->record(
            action: "wallet_{$action}",
            subjectType: 'Wallet',
            subjectId: $walletData['wallet_id'] ?? null,
            oldValues: [],
            newValues: $walletData,
            correlationId: $correlationId,
        );
    }

    /**
     * Log fraud check result
     */
    public function logFraudCheck(
        array $checkData,
        ?string $correlationId = null,
    ): void {
        $this->record(
            action: 'fraud_check',
            subjectType: 'FraudCheck',
            subjectId: null,
            oldValues: [],
            newValues: $checkData,
            correlationId: $correlationId,
        );
    }

    /**
     * Log promo code application
     */
    public function logPromo(
        array $promoData,
        ?string $correlationId = null,
    ): void {
        $this->record(
            action: 'promo_applied',
            subjectType: 'PromoCode',
            subjectId: null,
            oldValues: [],
            newValues: $promoData,
            correlationId: $correlationId,
        );
    }

    /**
     * Log error with context
     */
    public function logError(
        string $operation,
        \Exception $exception,
        ?string $correlationId = null,
        array $context = [],
    ): void {
        $errorData = array_merge($context, [
            'operation' => $operation,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $this->record(
            action: 'error_occurred',
            subjectType: 'System',
            subjectId: null,
            oldValues: [],
            newValues: $errorData,
            correlationId: $correlationId,
        );
    }

    /**
     * Log authentication event
     */
    public function logAuth(
        string $action,
        array $authData,
        ?string $correlationId = null,
    ): void {
        $this->record(
            action: "auth_{$action}",
            subjectType: 'Auth',
            subjectId: $authData['user_id'] ?? null,
            oldValues: [],
            newValues: $authData,
            correlationId: $correlationId,
        );
    }

    // ── Private Helpers ─────────────────────────────────────

    private function syncRecord(CreateAuditLogDto $dto): void
    {
        AuditLog::create([
            'uuid' => Str::uuid(),
            'correlation_id' => $dto->correlationId,
            'tenant_id' => $dto->tenantId,
            'business_group_id' => $dto->businessGroupId,
            'user_id' => $dto->userId,
            'action' => $dto->action,
            'subject_type' => $dto->subjectType,
            'subject_id' => $dto->subjectId,
            'old_values' => $dto->oldValues,
            'new_values' => $dto->newValues,
            'ip_address' => $dto->ipAddress,
            'device_fingerprint' => $dto->deviceFingerprint,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getCurrentUserId(): ?int
    {
        return Auth::id();
    }

    private function getCurrentTenantId(): ?int
    {
        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }

        return null;
    }

    private function generateDeviceFingerprint(): ?string
    {
        $ip = $this->request->ip();
        $userAgent = $this->request->userAgent();

        if (! $ip || ! $userAgent) {
            return null;
        }

        return hash('sha256', $ip.$userAgent);
    }

    private function maskSensitiveData(array $data): array
    {
        $maskedFields = config('audit.masked_fields', [
            'password', 'password_confirmation', 'card_number', 'cvv',
            'token', 'api_key', 'secret', 'ssn', 'passport',
        ]);

        foreach ($maskedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = str_repeat('*', strlen((string) $data[$field]));
            }
        }

        return $data;
    }
}
