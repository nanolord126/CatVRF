<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Safety Stock Service
 * 
 * Сервис управления страховым запасом для предотвращения дефицита
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class SafetyStockService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Расчет страхового запаса
     */
    public function calculateSafetyStock(
        int $inventoryItemId,
        int $leadTimeDays,
        float $serviceLevel = 0.95,
        float $demandStdDev = null
    ): float {
        // Получение исторических данных о спросе
        $demandData = $this->getDemandHistory($inventoryItemId, 90);

        if (empty($demandData)) {
            // Если нет данных, используем эвристику
            return $this->calculateHeuristicSafetyStock($inventoryItemId);
        }

        // Расчет стандартного отклонения спроса
        $stdDev = $demandStdDev ?? $this->calculateStandardDeviation($demandData);

        // Z-score для уровня сервиса (95% = 1.645)
        $zScore = $this->getZScore($serviceLevel);

        // Формула страхового запаса: Z * σ * √LT
        $safetyStock = $zScore * $stdDev * sqrt($leadTimeDays);

        // Обновление в БД
        $this->updateSafetyStock($inventoryItemId, $safetyStock);

        return round($safetyStock, 2);
    }

    /**
     * Обновление страхового запаса в БД
     */
    public function updateSafetyStock(int $inventoryItemId, float $safetyStock): void
    {
        $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->update([
                'safety_stock' => $safetyStock,
                'updated_at' => now(),
            ]);

        $this->cache->tags(['inventory'])->flush();

        $this->logger->info('Safety stock updated', [
            'inventory_item_id' => $inventoryItemId,
            'safety_stock' => $safetyStock,
        ]);
    }

    /**
     * Проверка, нужно ли пополнение с учетом страхового запаса
     */
    public function needsReplenishment(int $inventoryItemId): bool
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (!$item) {
            return false;
        }

        $currentStock = $item->current_stock ?? 0;
        $safetyStock = $item->safety_stock ?? 0;
        $reorderPoint = $item->min_stock_threshold ?? 0;

        // Нужно пополнение если текущий запас < reorder point + safety stock
        return $currentStock < ($reorderPoint + $safetyStock);
    }

    /**
     * Получение истории спроса
     */
    private function getDemandHistory(int $inventoryItemId, int $days): array
    {
        return $this->db->table('stock_movements')
            ->where('inventory_item_id', $inventoryItemId)
            ->where('movement_type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->selectRaw('DATE(created_at) as date, SUM(quantity) as demand')
            ->orderBy('date')
            ->pluck('demand')
            ->toArray();
    }

    /**
     * Расчет стандартного отклонения
     */
    private function calculateStandardDeviation(array $data): float
    {
        $n = count($data);
        if ($n < 2) {
            return 0;
        }

        $mean = array_sum($data) / $n;
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $data)) / ($n - 1);

        return sqrt($variance);
    }

    /**
     * Получение Z-score для уровня сервиса
     */
    private function getZScore(float $serviceLevel): float
    {
        // Упрощенная таблица Z-score
        $zScores = [
            0.90 => 1.28,
            0.95 => 1.645,
            0.97 => 1.88,
            0.99 => 2.33,
            0.999 => 3.09,
        ];

        // Интерполяция для промежуточных значений
        $levels = array_keys($zScores);
        sort($levels);

        foreach ($levels as $level) {
            if ($serviceLevel <= $level) {
                return $zScores[$level];
            }
        }

        return $zScores[0.99];
    }

    /**
     * Эвристический расчет страхового запаса (без исторических данных)
     */
    private function calculateHeuristicSafetyStock(int $inventoryItemId): float
    {
        $item = $this->db->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->first();

        if (!$item) {
            return 0;
        }

        // Базовый страховой запас = 20% от reorder point
        $reorderPoint = $item->min_stock_threshold ?? 100;
        
        // Для критических товаров (ABC-A) увеличиваем до 30%
        $abcClass = $item->abc_class ?? 'C';
        $multiplier = match ($abcClass) {
            'A' => 0.3,
            'B' => 0.25,
            'C' => 0.2,
            default => 0.2,
        };

        return $reorderPoint * $multiplier;
    }

    /**
     * Массовый пересчет страховых запасов
     */
    public function recalculateAllSafetyStocks(): array
    {
        $items = $this->db->table('inventory_items')
            ->where('min_stock_threshold', '>', 0)
            ->get();

        $results = [
            'total' => $items->count(),
            'updated' => 0,
            'failed' => 0,
        ];

        foreach ($items as $item) {
            try {
                $this->calculateSafetyStock(
                    $item->id,
                    7, // стандартное время выполнения заказа
                    0.95 // уровень сервиса 95%
                );
                $results['updated']++;
            } catch (\Exception $e) {
                $this->logger->error('Failed to calculate safety stock', [
                    'inventory_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
                $results['failed']++;
            }
        }

        return $results;
    }
}
