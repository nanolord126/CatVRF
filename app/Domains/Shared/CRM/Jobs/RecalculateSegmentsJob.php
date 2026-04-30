<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

use LoggerInterface;

use App\Domains\CRM\Services\CrmSegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * RecalculateSegmentsJob — пересчёт всех динамических сегментов CRM для тенанта.
 *
 * Запускается по расписанию (ежедневно 03:00) или вручную из панели.
 * Пересчитывает правила сегментов и обновляет привязки клиентов.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 * Очередь: crm-segments
 */
final class RecalculateSegmentsJob implements ShouldQueue
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
    public int $timeout = 300;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        private readonly int $tenantId,
        private readonly string $correlationId,) {
        $this->onQueue('crm-segments');
    }

    public function handle(
        CrmSegmentationService $segmentationService,
        LoggerInterface $logger,
    ): void {
        $logger->$this->logger->info('CRM: starting segments recalculation', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        $startTime = microtime(true);

        $segmentationService->recalculateAllSegments($this->tenantId, $this->correlationId);
        $segmentationService->autoSegmentAllClients($this->tenantId, $this->correlationId);

        $elapsed = round(microtime(true) - $startTime, 2);

        $logger->$this->logger->info('CRM: segments recalculation completed', [
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
            'RecalculateSegmentsJob[tenant_id=%d, correlation_id=%s]',
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
