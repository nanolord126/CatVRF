<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Listeners;

use App\Domains\Finances\Domain\Events\PayoutInitiated;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * Listener: логирование события инициации выплаты.
 *
 * Выполняется асинхронно через очередь.
 * Записывает аудит-лог через AuditService и структурированный
 * лог через PSR LoggerInterface.
 *
 * Все данные берутся из самого события (PayoutInitiated),
 * а не из Request (т.к. слушатель работает в очереди).
 *
 * @package App\Domains\Finances\Domain\Listeners
 */
final class LogPayoutInitiatedListener implements ShouldQueue
{
    /**
     * Очередь, в которой обрабатывается слушатель.
     */
    public string $queue = 'audit-logs';

    /**
     * Количество попыток выполнения.
     */
    public int $tries = 3;

    /**
     * Задержка между повторными попытками (секунды).
     */
    public int $backoff = 10;

    public function __construct(
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Обработать событие PayoutInitiated.
     *
     * Записывает:
     * 1. Структурированный лог (PSR Logger)
     * 2. Аудит-запись через AuditService::record()
     */
    public function handle(PayoutInitiated $event): void
    {
        $this->logger->info('Payout initiated', [
            'tenant_id'      => $event->tenantId,
            'amount'         => $event->amount,
            'period_start'   => $event->periodStart,
            'period_end'     => $event->periodEnd,
            'correlation_id' => $event->correlationId,
        ]);

        $this->audit->record(
            action: 'payout_initiated',
            subjectType: 'payout',
            subjectId: $event->tenantId,
            newValues: [
                'amount'       => $event->amount,
                'period_start' => $event->periodStart,
                'period_end'   => $event->periodEnd,
            ],
            correlationId: $event->correlationId,
        );
    }

    /**
     * Обработка ошибки при выполнении слушателя.
     */
    public function failed(PayoutInitiated $event, \Throwable $exception): void
    {
        $this->logger->error('LogPayoutInitiatedListener failed', [
            'event'          => 'PayoutInitiated',
            'tenant_id'      => $event->tenantId,
            'error'          => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);
    }
}