<?php

declare(strict_types=1);

namespace App\Domains\CRM\Jobs;

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
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('crm-segments');
    }

    public function handle(
        CrmSegmentationService $segmentationService,
        LoggerInterface $logger,
    ): void {
        $logger->info('CRM: starting segments recalculation', [
            'tenant_id' => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ]);

        $startTime = microtime(true);

        $segmentationService->recalculateAllSegments($this->tenantId, $this->correlationId);
        $segmentationService->autoSegmentAllClients($this->tenantId, $this->correlationId);

        $elapsed = round(microtime(true) - $startTime, 2);

        $logger->info('CRM: segments recalculation completed', [
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
}




