<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Slotting Optimization Service
 * 
 * Сервис для оптимизации размещения товаров на складе
 * Оптимизирует размещение на основе ABC класса, оборота и совместимости
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class SlottingOptimizationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Оптимизация размещения товаров
     */
    public function optimizeSlotting(int $warehouseId, int $tenantId, ?int $userId = null): array
    {
        $results = [
            'analyzed' => 0,
            'optimized' => 0,
            'no_change' => 0,
            'recommendations' => [],
        ];

        // Получение всех товаров на складе
        $products = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->get();

        foreach ($products as $product) {
            $results['analyzed']++;

            $recommendation = $this->generateSlottingRecommendation($product, $warehouseId);

            if ($recommendation['action'] === 'move') {
                $results['optimized']++;
                $results['recommendations'][] = $recommendation;
            } else {
                $results['no_change']++;
            }
        }

        $this->logger->info('Slotting optimization completed', [
            'warehouse_id' => $warehouseId,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Генерация рекомендации по размещению
     */
    private function generateSlottingRecommendation($product, int $warehouseId): array
    {
        $currentLocation = $this->db->table('inventory_locations')
            ->where('product_id', $product->product_id)
            ->where('warehouse_id', $warehouseId)
            ->first();

        $abcClass = $product->abc_class ?? 'C';
        $turnover = $this->getProductTurnover($product->product_id);
        $currentZone = $currentLocation ? $currentLocation->zone : null;

        $optimalZone = $this->determineOptimalZone($abcClass, $turnover);

        if ($currentZone === $optimalZone) {
            return [
                'product_id' => $product->product_id,
                'action' => 'no_change',
                'reason' => 'Already in optimal zone',
            ];
        }

        return [
            'product_id' => $product->product_id,
            'action' => 'move',
            'current_zone' => $currentZone,
            'optimal_zone' => $optimalZone,
            'abc_class' => $abcClass,
            'turnover' => $turnover,
            'reason' => $this->getMoveReason($abcClass, $turnover, $currentZone, $optimalZone),
        ];
    }

    /**
     * Определение оптимальной зоны
     */
    private function determineOptimalZone(string $abcClass, int $turnover): string
    {
        // ABC-A товары должны быть в зоне быстрого доступа
        // Высокий оборот - зона быстрого доступа
        
        if ($abcClass === 'A' || $turnover > 100) {
            return 'fast_pick';
        }

        if ($abcClass === 'B' || $turnover > 50) {
            return 'medium_pick';
        }

        return 'bulk_storage';
    }

    /**
     * Получение оборота товара
     */
    private function getProductTurnover(int $productId): int
    {
        return $this->db->table('stock_movements')
            ->where('product_id', $productId)
            ->where('movement_type', 'out')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantity');
    }

    /**
     * Получение причины перемещения
     */
    private function getMoveReason(string $abcClass, int $turnover, ?string $currentZone, string $optimalZone): string
    {
        if ($abcClass === 'A' && $currentZone !== 'fast_pick') {
            return 'ABC-A products should be in fast pick zone';
        }

        if ($turnover > 100 && $currentZone !== 'fast_pick') {
            return 'High turnover products should be in fast pick zone';
        }

        if ($abcClass === 'C' && $currentZone === 'fast_pick') {
            return 'ABC-C products should not occupy fast pick zone';
        }

        return 'Product turnover/ABC class mismatch with current zone';
    }

    /**
     * Анализ совместимости товаров для совместного хранения
     */
    public function analyzeCompatibility(int $productId1, int $productId2): array
    {
        $product1 = $this->db->table('products')->where('id', $productId1)->first();
        $product2 = $this->db->table('products')->where('id', $productId2)->first();

        if (!$product1 || !$product2) {
            throw new \RuntimeException('One or both products not found');
        }

        $incompatible = false;
        $reasons = [];

        // Проверка на несовместимость категорий
        $category1 = strtolower($product1->category ?? '');
        $category2 = strtolower($product2->category ?? '');

        // Наркотики нельзя хранить с другими товарами
        if ($this->isNarcotic($category1) || $this->isNarcotic($category2)) {
            $incompatible = true;
            $reasons[] = 'Narcotics require dedicated storage';
        }

        // Психотропные вещества отдельно
        if ($this->isPsychotropic($category1) || $this->isPsychotropic($category2)) {
            $incompatible = true;
            $reasons[] = 'Psychotropic substances require dedicated storage';
        }

        // Холодильная цепочка отдельно от обычных
        if ($this->requiresRefrigeration($category1) !== $this->requiresRefrigeration($category2)) {
            $incompatible = true;
            $reasons[] = 'Temperature requirements mismatch';
        }

        return [
            'compatible' => !$incompatible,
            'reasons' => $reasons,
        ];
    }

    /**
     * Проверка на наркотический препарат
     */
    private function isNarcotic(string $category): bool
    {
        return str_contains($category, 'narcotic') || str_contains($category, 'опиум');
    }

    /**
     * Проверка на психотропный препарат
     */
    private function isPsychotropic(string $category): bool
    {
        return str_contains($category, 'psychotropic') || str_contains($category, 'психотроп');
    }

    /**
     * Проверка на требование охлаждения
     */
    private function requiresRefrigeration(string $category): bool
    {
        return str_contains($category, 'refrigerated') || 
               str_contains($category, 'vaccine') || 
               str_contains($category, 'insulin');
    }
}
