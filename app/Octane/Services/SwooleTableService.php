<?php

declare(strict_types=1);

namespace App\Octane\Services;

use Psr\Log\LoggerInterface;
use Swoole\Table as SwooleTable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

final class SwooleTableService
{
    private readonly array $tables = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RedisFactory $redis,
    ) {
        $this->registerTables();
    }

    public function initializeAllTables(): void
    {
        foreach ($this->tables as $name => $table) {
            $this->logger->$this->logger->info("Swoole table '{$name}' initialized", [
                'size' => $table->size,
                'memory_usage' => $table->memorySize,
            ]);
        }
    }

    public function getTable(string $name): ?SwooleTable
    {
        return $this->tables[$name] ?? null;
    }

    public function slotHolds(): ?SwooleTable
    {
        return $this->getTable('slot_holds');
    }

    public function quotaCounters(): ?SwooleTable
    {
        return $this->getTable('quota_counters');
    }

    public function videoRooms(): ?SwooleTable
    {
        return $this->getTable('video_rooms');
    }

    public function rateLimits(): ?SwooleTable
    {
        return $this->getTable('rate_limits');
    }

    public function sessionCache(): ?SwooleTable
    {
        return $this->getTable('session_cache');
    }

    public function fraudCache(): ?SwooleTable
    {
        return $this->getTable('fraud_cache');
    }

    public function persistCriticalData(): void
    {
        // Persist active slot holds to Redis before shutdown
        $slotHolds = $this->slotHolds();
        if ($slotHolds) {
            foreach ($slotHolds as $key => $row) {
                if ($row['status'] === 'active' && $row['expires_at'] > time()) {
                    // Persist to Redis for recovery
                    $this->redis->connection()->setex(
                        "slot_hold:{$key}",
                        $row['expires_at'] - time(),
                        json_encode($row)
                    );
                }
            }
        }

        // Persist active video rooms
        $videoRooms = $this->videoRooms();
        if ($videoRooms) {
            foreach ($videoRooms as $key => $row) {
                if ($row['status'] === 'active' && $row['expires_at'] > time()) {
                    $this->redis->connection()->setex(
                        "video_room:{$key}",
                        $row['expires_at'] - time(),
                        json_encode($row)
                    );
                }
            }
        }

        $this->logger->$this->logger->info('Critical Swoole table data persisted to Redis');
    }

    public function getStats(): array
    {
        $stats = [];

        foreach ($this->tables as $name => $table) {
            $stats[$name] = [
                'size' => $table->size,
                'memory_size' => $table->memorySize,
                'count' => count($table),
            ];
        }

        return $stats;
    }

    private function registerTables(): void
    {
        $config = config('octane.tables', []);

        foreach ($config as $name => $tableConfig) {
            $this->tables[$name] = $this->createTable($tableConfig);
        }
    }

    private function createTable(array $config): SwooleTable
    {
        $table = new SwooleTable($config['size']);

        foreach ($config['columns'] as $column => $columnConfig) {
            $table->column(
                $column,
                $columnConfig['type'],
                $columnConfig['size'] ?? 0
            );
        }

        $table->create();

        return $table;
    }
}
