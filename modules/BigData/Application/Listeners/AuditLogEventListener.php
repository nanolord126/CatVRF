<?php

declare(strict_types=1);

namespace Modules\BigData\Application\Listeners;

use App\Services\Audit\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\BigData\Application\Services\BigDataService;
use Modules\BigData\Domain\Enums\EventType;

/**
 * Audit Log Event Listener
 *
 * Automatically tracks audit log events in Big Data.
 * Integrates with existing AuditService to capture all audit events.
 */
final readonly class AuditLogEventListener implements ShouldQueue
{
    public string $queue = 'bigdata-audit';

    public function __construct(
        private readonly BigDataService $bigData,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle audit log event
     * This would be called from AuditService when an audit log is created
     */
    public function handle(array $auditData): void
    {
        try {
            $eventType = $this->mapAuditActionToEventType($auditData['action'] ?? 'unknown');

            $event = \Modules\BigData\Domain\DTOs\BaseEventDTO::create(
                eventType: $eventType,
                tenantId: $auditData['tenant_id'] ?? 1,
                userId: $auditData['user_id'] ?? null,
                sellerId: $auditData['seller_id'] ?? null,
                productId: $auditData['product_id'] ?? null,
                orderId: $auditData['order_id'] ?? null,
                vertical: $auditData['vertical'] ?? null,
                properties: [
                    'action' => $auditData['action'] ?? 'unknown',
                    'entity_type' => $auditData['entity_type'] ?? null,
                    'entity_id' => $auditData['entity_id'] ?? null,
                    'old_values' => $auditData['old_values'] ?? [],
                    'new_values' => $auditData['new_values'] ?? [],
                ],
                monetaryValue: $auditData['monetary_value'] ?? null,
                context: [
                    'ip_address' => $auditData['ip_address'] ?? null,
                    'user_agent' => $auditData['user_agent'] ?? null,
                ],
            );

            $this->bigData->track($event);

            Log::debug('Audit event tracked in Big Data', [
                'audit_id' => $auditData['id'] ?? null,
                'action' => $auditData['action'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track audit event in Big Data', [
                'error' => $e->getMessage(),
                'audit_data' => $auditData,
            ]);
        }
    }

    /**
     * Map audit action to Big Data event type
     */
    private function mapAuditActionToEventType(string $action): EventType
    {
        return match ($action) {
            'created' => EventType::AuditLog,
            'updated' => EventType::AuditLog,
            'deleted' => EventType::AuditLog,
            'payment_success' => EventType::PaymentCompleted,
            'payment_failed' => EventType::PaymentFailed,
            'login' => EventType::UserLoggedIn,
            'logout' => EventType::UserLoggedOut,
            'order_placed' => EventType::OrderPlaced,
            'order_paid' => EventType::OrderPaid,
            'order_cancelled' => EventType::OrderCancelled,
            'fraud_detected' => EventType::FraudDetected,
            default => EventType::AuditLog,
        };
    }
}
