<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

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
    use \Illuminate\Foundation\Events\Dispatchable;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;

    /**
     * Количество попыток.
     */
    public int $tries = 2;

    /**
     * Таймаут (секунды).
     */
    public int $timeout = 600;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('crm-automations');
    }

    public function handle(
        CrmAutomationService $automationService,
        LoggerInterface $logger,
    ): void {
        $logger->info('CRM: processing automations for tenant', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        $startTime = microtime(true);

        $automationService->processAutomations($this->tenantId, $this->correlationId);

        $elapsed = round(microtime(true) - $startTime, 2);

        $logger->info('CRM: automations processing completed', [
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
}




