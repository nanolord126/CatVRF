<?php

declare(strict_types=1);

namespace App\Domains\Finances\Listeners;

use App\Domains\Finances\Events\FinanceRecordCreated;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Лисенер создания финансовой записи.
 *
 * Обрабатывает FinanceRecordCreated асинхронно.
 * Логирует событие в audit и application каналы.
 * Поддерживает цепочку correlation_id.
 *
 * @package App\Domains\Finances\Listeners
 */
final class LogFinanceRecordCreated implements ShouldQueue
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
    public function handle(FinanceRecordCreated $event): void
    {
        $record = $event->financeRecord;

        $this->logger->info('FinanceRecord created', [
            'finance_record_id' => $record->id,
            'tenant_id'         => $record->tenant_id ?? null,
            'business_group_id' => $record->business_group_id ?? null,
            'type'              => $record->type ?? null,
            'amount'            => $record->amount ?? null,
            'correlation_id'    => $event->correlationId,
            'user_id'           => $event->userId,
        ]);

        $this->audit->record(
            action: 'finance_record_created',
            subjectType: $record::class,
            subjectId: $record->id,
            oldValues: [],
            newValues: $record->toArray(),
            correlationId: $event->correlationId,
        );
    }

    /**
     * Обработка окончательного сбоя.
     */
    public function failed(FinanceRecordCreated $event, \Throwable $exception): void
    {
        $this->logger->error('LogFinanceRecordCreated: listener FAILED', [
            'finance_record_id' => $event->financeRecord->id ?? null,
            'correlation_id'    => $event->correlationId,
            'error'             => $exception->getMessage(),
        ]);
    }
}
