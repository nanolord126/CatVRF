<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Jobs;


use App\Domains\Inventory\Services\InventoryAuditService;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Обработка инвентаризации в фоновом режиме.
 *
 * Запускается после создания InventoryCheck.
 * Может быть длительной операцией для крупных складов.
 *
 * CANON: LoggerInterface и AuditService — ТОЛЬКО в handle().
 */
final class ProcessInventoryCheckJob implements ShouldQueue
{

    public int $tries   = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly int    $checkId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('inventory');
    }

    public function handle(InventoryAuditService $auditService, LoggerInterface $logger, AuditService $audit): void
    {
        $logger->info('Processing inventory check', [
            'check_id'       => $this->checkId,
            'correlation_id' => $this->correlationId,
        ]);

        $audit->record(
            action: 'inventory_check_processing',
            subjectType: 'inventory_check',
            subjectId: $this->checkId,
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'Inventory check processing failed [check_id=%d, correlation_id=%s]: %s',
                $this->checkId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }

    public function getCheckId(): int
    {
        return $this->checkId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
}
