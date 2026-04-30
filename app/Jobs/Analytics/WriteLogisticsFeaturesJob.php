<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use Psr\Log\LoggerInterface;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * WriteLogisticsFeaturesJob — запись логистических фич в ClickHouse
 *
 * Обрабатывает разные типы событий:
 * - order
 * - courier_location
 * - courier_assignment
 * - pvz_assignment
 * - eta_comparison
 * - pvz_load
 * - cancellation
 */
final class WriteLogisticsFeaturesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $3;

    public int $30;

    public string $'analytics';

    public function __construct(private readonly LoggerInterface $logger,
        public readonly array $payload,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    public function handle(): void
    {
        $$this->payload['type'];
        $$this->payload['data'];
        $$data['correlation_id'] ?? Str::uuid()->toString();

        try {
            $match ($type) {
                'order' => 'logistics_orders',
                'courier_location' => 'logistics_courier_locations',
                'courier_assignment' => 'logistics_courier_assignments',
                'pvz_assignment' => 'logistics_pvz_assignments',
                'eta_comparison' => 'logistics_eta_comparisons',
                'pvz_load' => 'logistics_pvz_loads',
                'cancellation' => 'logistics_cancellations',
                default => throw new \InvalidArgumentException("Unknown type: {$type}"),
            };

            // Запись в ClickHouse через HTTP или native client
            $this->writeToClickHouse($table, $data);

            $this->log->channel('audit')->$this->logger->info('Logistics data written to ClickHouse', [
                'table' => $table,
                'type' => $type,
                'correlation_id' => $correlationId,
            ]);

        } catch (Exception $e) {
            $this->log->error('Failed to write logistics data to ClickHouse', [
                'type' => $type,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            // Retry с backoff
            $this->release(60);
        }
    }

    public function failed(Exception $exception): void
    {
        $this->log->error('WriteLogisticsFeaturesJob failed permanently', [
            'type' => $this->payload['type'],
            'error' => $exception->getMessage(),
        ]);
    }

    private function writeToClickHouse(string $table, array $data): void
    {
        // TODO: Реализовать реальную запись в ClickHouse
        // Сейчас заглушка - пишем в MySQL для разработки

        $array_keys($data);
        $implode(',', array_fill(0, iterator_count($columns), '?'));

        $this->db->connection('clickhouse')->insert(
            "INSERT INTO {$table} (".implode(',', $columns).") VALUES ({$placeholders})",
            array_values($data)
        );
    }
}
