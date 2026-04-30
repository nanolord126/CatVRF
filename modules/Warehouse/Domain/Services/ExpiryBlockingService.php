<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Expiry Blocking Service for ФЗ-61 compliance
 * 
 * Сервис обеспечивает автоматическую блокировку просроченных лекарственных средств:
 * - Проверка сроков годности
 * - Автоматическая блокировка партий
 * - Предупреждения о приближении срока годности
 * - Генерация отчетов об истечении срока
 */
final readonly class ExpiryBlockingService
{
    private const WARNING_DAYS = 30;
    private const CRITICAL_DAYS = 7;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Проверка срока годности партии
     */
    public function checkBatchExpiry(string $batchId, int $warningDays = self::WARNING_DAYS): array
    {
        $batch = $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->first();

        if (!$batch) {
            throw new \RuntimeException("Batch not found: {$batchId}");
        }

        $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
        $now = now();
        $daysUntilExpiry = $now->diffInDays($expiryDate, false);

        $status = 'valid';
        $severity = null;

        if ($daysUntilExpiry < 0) {
            $status = 'expired';
            $severity = 'critical';
            $this->blockBatch($batchId, 'expired');
        } elseif ($daysUntilExpiry <= self::CRITICAL_DAYS) {
            $status = 'critical';
            $severity = 'critical';
            $this->logger->critical('Batch expires very soon', [
                'batch_id' => $batchId,
                'batch_number' => $batch->batch_number,
                'days_until_expiry' => $daysUntilExpiry,
            ]);
        } elseif ($daysUntilExpiry <= $warningDays) {
            $status = 'warning';
            $severity = 'high';
            $this->logger->warning('Batch expires soon', [
                'batch_id' => $batchId,
                'batch_number' => $batch->batch_number,
                'days_until_expiry' => $daysUntilExpiry,
            ]);
        }

        return [
            'batch_id' => $batchId,
            'batch_number' => $batch->batch_number,
            'expiry_date' => $batch->expiry_date,
            'days_until_expiry' => $daysUntilExpiry,
            'status' => $status,
            'severity' => $severity,
            'is_blocked' => $batch->status === 'blocked',
        ];
    }

    /**
     * Блокировка партии
     */
    public function blockBatch(string $batchId, string $reason, ?int $blockedBy = null): void
    {
        $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->update([
                'status' => 'blocked',
                'block_reason' => $reason,
                'blocked_at' => now(),
                'blocked_by' => $blockedBy,
            ]);

        $this->logger->info('Batch blocked', [
            'batch_id' => $batchId,
            'reason' => $reason,
            'blocked_by' => $blockedBy,
        ]);

        // Инвалидация кэша
        Cache::tags(['inventory', 'batches'])->flush();
    }

    /**
     * Разблокировка партии
     */
    public function unblockBatch(string $batchId, int $unblockedBy): void
    {
        $batch = $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->first();

        if (!$batch) {
            throw new \RuntimeException("Batch not found: {$batchId}");
        }

        // Проверка срока годности перед разблокировкой
        $expiryCheck = $this->checkBatchExpiry($batchId);
        if ($expiryCheck['status'] === 'expired') {
            throw new \RuntimeException('Cannot unblock expired batch');
        }

        $this->db->table('inventory_batches')
            ->where('id', $batchId)
            ->update([
                'status' => 'available',
                'block_reason' => null,
                'blocked_at' => null,
                'blocked_by' => null,
            ]);

        $this->logger->info('Batch unblocked', [
            'batch_id' => $batchId,
            'unblocked_by' => $unblockedBy,
        ]);

        Cache::tags(['inventory', 'batches'])->flush();
    }

    /**
     * Автоматическая проверка всех партий
     */
    public function runExpiryCheck(): array
    {
        $results = [
            'checked' => 0,
            'blocked' => 0,
            'warnings' => 0,
            'critical' => 0,
        ];

        $batches = $this->db->table('inventory_batches')
            ->where('status', '!=', 'blocked')
            ->where('expiry_date', '<=', now()->addDays(self::WARNING_DAYS))
            ->get();

        foreach ($batches as $batch) {
            $check = $this->checkBatchExpiry($batch->id);
            $results['checked']++;

            if ($check['status'] === 'expired') {
                $results['blocked']++;
            } elseif ($check['severity'] === 'critical') {
                $results['critical']++;
            } elseif ($check['severity'] === 'high') {
                $results['warnings']++;
            }
        }

        $this->logger->info('Expiry check completed', $results);

        return $results;
    }

    /**
     * Получение отчета о просроченных партиях
     */
    public function getExpiryReport(int $warehouseId = null): array
    {
        $query = $this->db->table('inventory_batches')
            ->select([
                'id',
                'batch_number',
                'product_id',
                'warehouse_id',
                'expiry_date',
                'quantity',
                'status',
            ])
            ->orderBy('expiry_date', 'asc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $batches = $query->get();

        $now = now();
        $report = [
            'expired' => [],
            'critical' => [],
            'warning' => [],
            'valid' => [],
        ];

        foreach ($batches as $batch) {
            $expiryDate = \Carbon\Carbon::parse($batch->expiry_date);
            $daysUntilExpiry = $now->diffInDays($expiryDate, false);

            if ($daysUntilExpiry < 0) {
                $report['expired'][] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'days_expired' => abs($daysUntilExpiry),
                    'quantity' => $batch->quantity,
                ];
            } elseif ($daysUntilExpiry <= self::CRITICAL_DAYS) {
                $report['critical'][] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $batch->quantity,
                ];
            } elseif ($daysUntilExpiry <= self::WARNING_DAYS) {
                $report['warning'][] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $batch->quantity,
                ];
            } else {
                $report['valid'][] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'expiry_date' => $batch->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $batch->quantity,
                ];
            }
        }

        return $report;
    }
}
