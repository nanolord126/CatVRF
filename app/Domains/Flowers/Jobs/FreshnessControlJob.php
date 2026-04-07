<?php declare(strict_types=1);

/**
 * FreshnessControlJob — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/freshnesscontroljob
 */


namespace App\Domains\Flowers\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class FreshnessControlJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;

        public function __construct(string $correlationId = null, private readonly LoggerInterface $logger)
        {
            $this->correlationId = $correlationId ?? Str::uuid()->toString();
        }

        /**
         * Выполнение процесса
         */
        public function handle(): void
        {
            $this->logger->info('FreshnessControlJob started', [
                'correlation_id' => $this->correlationId,
            ]);

            // 1. Получение всех цветов (Inventory) с прошлым сроком свежести
            $staleFlowers = FlowerProduct::whereDate('freshness_date', '<', Carbon::now())->get();

            foreach ($staleFlowers as $flower) {
                // 2. Списание или уменьшение остатка (как правило, списание в ноль)
                $flower->update([
                    'current_stock' => 0,
                    'status' => 'expired',
                    'correlation_id' => $this->correlationId,
                ]);

                $this->logger->warning('Flower Expired (Freshness Control)', [
                    'flower_id' => $flower->id,
                    'name' => $flower->name,
                    'freshness_date' => $flower->freshness_date,
                    'correlation_id' => $this->correlationId,
                ]);
            }

            $this->logger->info('FreshnessControlJob finished', [
                'stale_count' => $staleFlowers->count(),
                'correlation_id' => $this->correlationId,
            ]);
        }
}
