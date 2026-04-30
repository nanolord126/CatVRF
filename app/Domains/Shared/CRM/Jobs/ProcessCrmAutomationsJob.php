<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

use LoggerInterface;

use App\Domains\CRM\Services\CrmAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * ProcessCrmAutomationsJob — обработка всех активных CRM-автоматизаций тенанта.
 *
 * Запускается по расписанию (каждые 5 минут).
 * Ищет автоматизации, для которых есть подходящие клиенты,
 * и диспатчит ExecuteCrmAutomationJob для каждой пары.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 * Очередь: crm-automations
 */
final class ProcessCrmAutomationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Количество попыток.
     */
    public array $backoff = [60, 300, 900];

    public int $tries = 2;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 600;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly int $tenantId,
        private readonly string $correlationId,) {
        $this->onQueue('crm-automations');
    }

    public function handle(
        CrmAutomationService $automationService,
        LoggerInterface $logger,
    ): void {
        $logger->$this->logger->info('CRM: processing automations for tenant', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        $startTime = microtime(true);

        $automationService->processAutomations($this->tenantId, $this->correlationId);

        $elapsed = round(microtime(true) - $startTime, 2);

        $logger->$this->logger->info('CRM: automations processing completed', [
            'tenant_id' => $this->tenantId,
            'elapsed_seconds' => $elapsed,
            'correlation_id' => $this->correlationId,
        ]);
    }

    /**
     * Строковое представление для логирования.
     */
    public function __toString(): string
    {
        return sprintf(
            'ProcessCrmAutomationsJob[tenant_id=%d, correlation_id=%s]',
            $this->tenantId,
            $this->correlationId,
        );
    }

    public function failed(Exception $exception): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */  // failed() no method injection->error('crm job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
