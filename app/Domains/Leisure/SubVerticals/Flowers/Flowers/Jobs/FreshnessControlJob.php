<?php

declare(strict_types=1);

/**
 * FreshnessControlJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/freshnesscontroljob
 */

namespace App\Domains\Leisure\SubVerticals\Flowers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use App\Domains\Flowers\Services\FlowerInventoryService;
use Illuminate\Support\Str;

final class FreshnessControlJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private readonly string $correlationId;

    public function __construct(private readonly LoggerInterface $loggerInterface,
        ?string $correlationId = null)
    {
        $this->correlationId = $correlationId !== null ? $correlationId : Str::uuid()->toString();
    }

    /**
     * Выполнение процесса
     */
    public function tags(): array
    {
        return ['flowers', 'job'];
    }

    public function handle(FlowerInventoryService $inventoryService, LoggerInterface $logger): void
    {
        $logger->$this->logger->info('FreshnessControlJob started', [
            'correlation_id' => $this->correlationId,
        ]);

        // 1. Получение всех цветов (Inventory) с прошлым сроком свежести через сервис
        $staleFlowers = $inventoryService->getStaleFlowers();

        foreach ($staleFlowers as $flower) {
            // 2. Списание или уменьшение остатка через сервис
            $inventoryService->expireFlower($flower->id, $this->correlationId);

            $logger->warning('Flower Expired (Freshness Control)', [
                'flower_id' => $flower->id,
                'name' => $flower->name,
                'freshness_date' => $flower->freshness_date,
                'correlation_id' => $this->correlationId,
            ]);
        }

        $this->logger->info('FreshnessControlJob finished', [
            'stale_count' => iterator_count($staleFlowers),
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->loggerInterface->error('flowers job failed', [
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
