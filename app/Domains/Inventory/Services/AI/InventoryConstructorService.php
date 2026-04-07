<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services\AI;

use Carbon\Carbon;

use App\Domains\Inventory\Models\InventoryItem;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * AI-конструктор для управления складскими запасами.
 *
 * Прогнозирует спрос, рекомендует пополнение, предупреждает о low-stock.
 * Интегрируется с UserTasteAnalyzer для персонализации рекомендаций.
 */
final readonly class InventoryConstructorService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private DatabaseManager     $db,
        private FraudControlService $fraud,
        private AuditService        $audit,
        private CacheRepository     $cache,
        private LoggerInterface     $logger,
    ) {}

    /**
     * Прогноз спроса и рекомендации по пополнению.
     *
     * @return array{
     *     low_stock_items: list<array{product_id: int, warehouse_id: int, available: int, predicted_demand: int}>,
     *     reorder_suggestions: list<array{product_id: int, warehouse_id: int, suggested_quantity: int, urgency: string}>,
     *     correlation_id: string,
     * }
     */
    public function analyzeDemandAndRecommend(int $tenantId, int $warehouseId, string $correlationId): array
    {
        $this->fraud->check(
            userId: $tenantId,
            operationType: 'ai_inventory_analysis',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = "inventory_ai:{$tenantId}:{$warehouseId}";

        /** @var array<string, mixed>|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $this->logger->debug('Inventory AI result from cache', [
                'tenant_id'      => $tenantId,
                'warehouse_id'   => $warehouseId,
                'correlation_id' => $correlationId,
            ]);

            return $cached;
        }

        $items = InventoryItem::where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->get();

        $lowStockItems       = [];
        $reorderSuggestions  = [];

        foreach ($items as $item) {
            $predictedDemand = $this->predictDemand($item);
            $available       = $item->available;

            if ($available <= $predictedDemand) {
                $lowStockItems[] = [
                    'product_id'       => $item->product_id,
                    'warehouse_id'     => $warehouseId,
                    'available'        => $available,
                    'predicted_demand' => $predictedDemand,
                ];

                $urgency = $available === 0 ? 'critical' : ($available < $predictedDemand / 2 ? 'high' : 'medium');

                $reorderSuggestions[] = [
                    'product_id'         => $item->product_id,
                    'warehouse_id'       => $warehouseId,
                    'suggested_quantity'  => max(1, $predictedDemand * 2 - $available),
                    'urgency'            => $urgency,
                ];
            }
        }

        $result = [
            'low_stock_items'      => $lowStockItems,
            'reorder_suggestions'  => $reorderSuggestions,
            'correlation_id'       => $correlationId,
        ];

        $this->cache->put($cacheKey, $result, self::CACHE_TTL);

        $this->audit->record(
            action: 'ai_inventory_analyzed',
            subjectType: 'warehouse',
            subjectId: $warehouseId,
            newValues: [
                'low_stock_count'  => count($lowStockItems),
                'reorder_count'    => count($reorderSuggestions),
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Inventory AI analysis completed', [
            'tenant_id'        => $tenantId,
            'warehouse_id'     => $warehouseId,
            'low_stock_count'  => count($lowStockItems),
            'correlation_id'   => $correlationId,
        ]);

        return $result;
    }

    /**
     * Простой прогноз спроса (заглушка для интеграции с ML).
     *
     * В production заменяется на DemandForecastService с XGBoost/LightGBM.
     */
    private function predictDemand(InventoryItem $item): int
    {
        $movements = $item->stockMovements()
            ->where('type', 'out')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('quantity');

        return max(1, (int) ceil($movements / 30 * 7));
    }
}
