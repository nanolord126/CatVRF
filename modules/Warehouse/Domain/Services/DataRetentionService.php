<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Modules\Warehouse\Domain\Exceptions\PIIProtectionException;

/**
 * Data Retention Service for 152-ФЗ compliance
 * 
 * Сервис обеспечивает автоматическое удаление данных в соответствии с политикой хранения:
 * - Логи: 1 год
 * - Инвентаризации: 5 лет
 * - Движения: 7 лет
 * - Партии: до истечения срока годности + 1 год
 */
final readonly class DataRetentionService
{
    public function __construct(
        private readonly DatabaseManager $db
    ) {}
    private const RETENTION_LOGS = '1 year';
    private const RETENTION_INVENTORY_COUNTS = '5 years';
    private const RETENTION_STOCK_MOVEMENTS = '7 years';
    private const RETENTION_BATCHES_AFTER_EXPIRY = '1 year';

    /**
     * Удаление устаревших логов
     */
    public function deleteOldLogs(): int
    {
        $cutoffDate = Carbon::now()->sub(self::RETENTION_LOGS);

        return $this->db->table('warehouse_stock_movements')
            ->where('created_at', '<', $cutoffDate)
            ->whereNotNull('metadata') // Удаляем только старые записи с метаданными
            ->update([
                'metadata' => $this->db->raw("JSON_SET(metadata, '$.pii_deleted', true)"),
                'notes' => $this->db->raw("COALESCE(CONCAT(notes, ' [PII deleted after retention]'), '[PII deleted after retention]')")
            ]);
    }

    /**
     * Удаление устаревших инвентаризаций
     */
    public function deleteOldInventoryCounts(): int
    {
        $cutoffDate = Carbon::now()->sub(self::RETENTION_INVENTORY_COUNTS);

        return $this->db->table('warehouse_inventory_counts')
            ->where('completed_at', '<', $cutoffDate)
            ->where('status', 'approved')
            ->update([
                'notes' => $this->db->raw("COALESCE(CONCAT(notes, ' [PII deleted after retention]'), '[PII deleted after retention]')"),
                'performed_by' => null,
                'approved_by' => null
            ]);
    }

    /**
     * Удаление PII из устаревших движений
     */
    public function anonymizeOldStockMovements(): int
    {
        $cutoffDate = Carbon::now()->sub(self::RETENTION_STOCK_MOVEMENTS);

        return $this->db->table('warehouse_stock_movements')
            ->where('created_at', '<', $cutoffDate)
            ->update([
                'reason' => '[Anonymized after retention]',
                'metadata' => $this->db->raw("JSON_SET(COALESCE(metadata, '{}'), '$.pii_anonymized', true)")
            ]);
    }

    /**
     * Удаление истекших партий (после срока годности + период хранения)
     */
    public function deleteExpiredBatches(): int
    {
        $cutoffDate = Carbon::now()->sub(self::RETENTION_BATCHES_AFTER_EXPIRY);

        return $this->db->table('warehouse_batches')
            ->where('expiry_date', '<', $cutoffDate)
            ->where('current_quantity', 0) // Только с нулевым остатком
            ->where('status', 'depleted')
            ->delete();
    }

    /**
     * Проверка соответствия retention policy
     */
    public function checkRetentionPolicy(string $entityType, Carbon $entityDate): bool
    {
        $retentionPeriod = match ($entityType) {
            'stock_movement' => self::RETENTION_STOCK_MOVEMENTS,
            'inventory_count' => self::RETENTION_INVENTORY_COUNTS,
            'log' => self::RETENTION_LOGS,
            default => throw new PIIProtectionException("Unknown entity type: {$entityType}")
        };

        $cutoffDate = Carbon::now()->sub($retentionPeriod);

        return $entityDate->gte($cutoffDate);
    }

    /**
     * Получение срока хранения для типа сущности
     */
    public function getRetentionPeriod(string $entityType): string
    {
        return match ($entityType) {
            'stock_movement' => self::RETENTION_STOCK_MOVEMENTS,
            'inventory_count' => self::RETENTION_INVENTORY_COUNTS,
            'log' => self::RETENTION_LOGS,
            'batch' => self::RETENTION_BATCHES_AFTER_EXPIRY,
            default => '7 years' // Default retention
        };
    }

    /**
     * Запуск всех процедур очистки
     */
    public function runAllRetentionPolicies(): array
    {
        $results = [
            'logs_anonymized' => $this->deleteOldLogs(),
            'movements_anonymized' => $this->anonymizeOldStockMovements(),
            'inventory_counts_anonymized' => $this->deleteOldInventoryCounts(),
            'expired_batches_deleted' => $this->deleteExpiredBatches(),
        ];

        return $results;
    }
}
