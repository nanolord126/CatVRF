<?php

declare(strict_types=1);

namespace App\Infrastructure\Listeners;

use App\Domain\Audit\Events\AuditEvent;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Infrastructure Listener - Audit Event Listener
 * 
 * Handles AuditEvent domain events and persists them to the audit log.
 * 
 * Architecture: Infrastructure Layer - Event Listener
 * - Listens to domain events (decoupled from business logic)
 * - Implements audit persistence logic
 * - Uses AuditService (infrastructure implementation)
 * 
 * Queue: async processing to avoid blocking application flow
 * 
 * @see \App\Domain\Audit\Events\AuditEvent
 * @see \App\Services\AuditService
 */
final class AuditEventListener implements ShouldQueue
{
    /**
     * Queue name for audit event processing
     */
    public string $queue;

    /**
     * Number of times the job may be attempted
     */
    public int $tries;

    /**
     * Number of seconds to wait before retrying
     */
    public int $backoff;

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle the audit event
     * 
     * @param  AuditEvent  $event  Domain event to log
     */
    public function handle(AuditEvent $event): void
    {
        try {
            // Extract context values
            $userId = $event->getUserId();
            $tenantId = $event->getTenantId();
            $businessGroupId = $event->getBusinessGroupId();

            // Record audit event using infrastructure service
            $this->auditService->record(
                action: $event->action,
                subjectType: $event->subjectType,
                subjectId: $event->subjectId,
                oldValues: $event->oldValues,
                newValues: $event->newValues,
                correlationId: $event->correlationId
            );

            // Log to Laravel channel for immediate visibility
            Log::channel('audit')->info($event->action, [
                'subject_type' => $event->subjectType,
                'subject_id' => $event->subjectId,
                'correlation_id' => $event->correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'business_group_id' => $businessGroupId,
            ]);
        } catch (\Throwable $e) {
            // Log error but don't fail the job (audit should not break business logic)
            Log::channel('security')->error('[AuditEventListener] Failed to process audit event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $event->correlationId,
                'action' => $event->action,
                'subject_type' => $event->subjectType,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure
     * 
     * @param  AuditEvent  $event  Event that failed
     * @param  \Throwable  $exception  Exception that caused failure
     */
    public function failed(AuditEvent $event, \Throwable $exception): void
    {
        // Log critical failure - audit log is important for compliance
        Log::channel('security')->critical('[AuditEventListener] Audit event processing failed permanently', [
            'error' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
            'action' => $event->action,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
        ]);

        // Consider alerting monitoring system here
        // Sentry::captureException($exception);
    }
}
