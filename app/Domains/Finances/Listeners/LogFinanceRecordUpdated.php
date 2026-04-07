<?php

declare(strict_types=1);

namespace App\Domains\Finances\Listeners;

use App\Domains\Finances\Events\FinanceRecordUpdated;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Лисенер обновления финансовой записи.
 *
 * Обрабатывает FinanceRecordUpdated асинхронно.
 * Логирует old/new значения в audit канал.
 * Поддерживает цепочку correlation_id.
 *
 * @package App\Domains\Finances\Listeners
 */
final class LogFinanceRecordUpdated implements ShouldQueue
{
    /**
     * Очередь для обработки.
     */
    public string $queue = 'audit-logs';

    /**
     * Количество попыток.
     */
    public int $tries = 3;

    /**
     * Задержка между попытками (сек).
     */
    public int $backoff = 30;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Обработать событие.
     */
    public function handle(FinanceRecordUpdated $event): void
    {
        $record = $event->financeRecord;

        $this->logger->info('FinanceRecord updated', [
            'finance_record_id' => $record->id,
            'tenant_id'         => $record->tenant_id ?? null,
            'business_group_id' => $record->business_group_id ?? null,
            'old_values'        => $event->oldValues,
            'new_values'        => $event->newValues,
            'correlation_id'    => $event->correlationId,
            'user_id'           => $event->userId,
        ]);

        $this->audit->record(
            action: 'finance_record_updated',
            subjectType: $record::class,
            subjectId: $record->id,
            oldValues: $event->oldValues,
            newValues: $event->newValues,
            correlationId: $event->correlationId,
        );
    }

    /**
     * Обработка окончательного сбоя.
     */
    public function failed(FinanceRecordUpdated $event, \Throwable $exception): void
    {
        $this->logger->error('LogFinanceRecordUpdated: listener FAILED', [
            'finance_record_id' => $event->financeRecord->id ?? null,
            'correlation_id'    => $event->correlationId,
            'error'             => $exception->getMessage(),
        ]);
    }
}
