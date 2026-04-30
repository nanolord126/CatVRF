<?php

declare(strict_types=1);

namespace App\Domains\CRM\Listeners;

use Psr\Log\LoggerInterface;

use App\Domains\CRM\Events\CrmAutomationTriggered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;

/**
 * LogAutomationExecution — логирование выполнения CRM-автоматизации.
 *
 * Слушает CrmAutomationTriggered. Записывает результат выполнения
 * в audit-лог для анализа эффективности кампаний.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class LogAutomationExecution implements ShouldQueue
{
    /**
     * Количество попыток выполнения.
     * После исчерпания — событие переходит в failed_jobs.
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения в секундах.
     */
    public int $timeout = 30;

    /**
     * Очередь для обработки.
     */
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $logger,) {}

    public function handle(CrmAutomationTriggered $event): void
    {
        $this->logger->channel('audit')->$this->logger->info('CRM automation triggered', [
            'automation_id' => $event->automation->id,
            'automation_name' => $event->automation->name,
            'client_id' => $event->client->id,
            'client_name' => $event->client->full_name,
            'trigger_type' => $event->automation->trigger_type,
            'action_type' => $event->automation->action_type,
            'result' => $event->result,
            'tenant_id' => $event->automation->tenant_id,
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return 'LogAutomationExecution';
    }

    /**
     * Задержка между попытками (секунды).
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [5, 15, 60];
    }

    /**
     * Обработка неудачного выполнения listener'а.
     * Логируется в канал audit для мониторинга.
     */
    public function failed(object $event, \Throwable $exception): void
    {
        if (isset($this->logger)) {
            $this->logger->channel('audit')->error('Listener failed: '.self::class, [
                'event' => get_class($event),
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'correlation_id' => $event->correlationId ?? null,
            ]);
        }
    }

    /**
     * Определить, нужно ли ставить listener в очередь.
     * Возвращает false для критичных операций, которые должны выполниться синхронно.
     */
    public function shouldQueue(object $event): bool
    {
        return true;
    }
}
