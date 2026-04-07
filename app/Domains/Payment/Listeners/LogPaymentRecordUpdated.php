<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Events\PaymentRecordUpdated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Listener: логирование обновления платёжной записи.
 */
final class LogPaymentRecordUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Обработка события.
     */
    public function handle(PaymentRecordUpdated $event): void
    {
        $this->logger->info('Payment record updated', [
            'payment_record_id' => $event->paymentRecord->id,
            'old_status' => $event->oldValues['status'] ?? 'unknown',
            'new_status' => $event->newValues['status'] ?? 'unknown',
            'changed_fields' => array_keys($event->newValues),
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'tenant_id' => $event->getTenantId(),
        ]);

        $this->audit->record(
            action: 'payment_record_updated',
            subjectType: get_class($event->paymentRecord),
            subjectId: $event->paymentRecord->id,
            oldValues: $event->oldValues,
            newValues: $event->newValues,
            correlationId: $event->correlationId,
        );
    }

    /**
     * Листенер не должен блокировать очередь при сбое.
     */
    public function shouldQueue(): bool
    {
        return false;
    }

    /**
     * Канал очереди (если будет очередь).
     */
    public function viaQueue(): string
    {
        return 'audit';
    }
}
