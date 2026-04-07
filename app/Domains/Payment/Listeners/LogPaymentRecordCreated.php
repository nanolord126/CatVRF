<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Events\PaymentRecordCreated;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * Listener: логирование создания платёжной записи.
 *
 * Пишет в канал audit через LoggerInterface + AuditService::record().
 */
final class LogPaymentRecordCreated
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Обработка события.
     */
    public function handle(PaymentRecordCreated $event): void
    {
        $this->logger->info('Payment record created', [
            'payment_record_id' => $event->paymentRecord->id,
            'provider_code' => $event->paymentRecord->provider_code?->value ?? 'unknown',
            'amount_kopecks' => $event->paymentRecord->amount_kopecks,
            'status' => $event->paymentRecord->status?->value ?? 'unknown',
            'correlation_id' => $event->correlationId,
            'user_id' => $event->userId,
            'tenant_id' => $event->getTenantId(),
        ]);

        $this->audit->record(
            action: 'payment_record_created',
            subjectType: get_class($event->paymentRecord),
            subjectId: $event->paymentRecord->id,
            newValues: $event->toAuditContext(),
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
