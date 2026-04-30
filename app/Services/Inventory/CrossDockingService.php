<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Cross Docking Service
 * 
 * Сервис для cross-docking оптимизации
 * Прямая передача товаров от приемки к отгрузке без размещения на хранение
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class CrossDockingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Проверка, подходит ли товар для cross-docking
     */
    public function isEligibleForCrossDocking(int $productId, int $warehouseId): bool
    {
        $product = $this->db->table('products')
            ->where('id', $productId)
            ->first();

        if (!$product) {
            return false;
        }

        // Критерии для cross-docking:
        // 1. Товар с высоким оборотом
        // 2. Есть ожидающие заказы на этот товар
        // 3. Короткий срок годности (для FEFO)
        // 4. Товар не требует специального хранения

        $hasPendingOrders = $this->hasPendingOrders($productId, $warehouseId);
        $isHighTurnover = $this->isHighTurnover($productId);
        $requiresSpecialStorage = $this->requiresSpecialStorage($product);

        return $hasPendingOrders && $isHighTurnover && !$requiresSpecialStorage;
    }

    /**
     * Создание cross-docking задачи
     */
    public function createCrossDockingTask(
        int $incomingShipmentId,
        int $warehouseId,
        int $tenantId,
        ?int $createdBy = null
    ): string {
        $taskId = (string) \Illuminate\Support\Str::uuid();

        return $this->db->transaction(function () use (
            $incomingShipmentId,
            $warehouseId,
            $tenantId,
            $createdBy,
            $taskId
        ) {
            // Получение товаров во входящей поставке
            $items = $this->db->table('incoming_shipment_items')
                ->where('shipment_id', $incomingShipmentId)
                ->get();

            $crossDockingItems = [];

            foreach ($items as $item) {
                if ($this->isEligibleForCrossDocking($item->product_id, $warehouseId)) {
                    $crossDockingItems[] = $item;
                }
            }

            if (empty($crossDockingItems)) {
                throw new \RuntimeException('No items eligible for cross-docking');
            }

            // Создание задачи cross-docking
            $this->db->table('cross_docking_tasks')->insert([
                'id' => $taskId,
                'incoming_shipment_id' => $incomingShipmentId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'status' => 'pending',
                'item_count' => count($crossDockingItems),
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            // Связь с товарами
            foreach ($crossDockingItems as $item) {
                $this->db->table('cross_docking_items')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'task_id' => $taskId,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'batch_id' => $item->batch_id,
                    'status' => 'pending',
                ]);

                // Автоматическое резервирование для заказов
                $this->reserveForOrders($item->product_id, $item->quantity, $warehouseId);
            }

            $this->logger->info('Cross-docking task created', [
                'task_id' => $taskId,
                'warehouse_id' => $warehouseId,
                'item_count' => count($crossDockingItems),
            ]);

            return $taskId;
        });
    }

    /**
     * Проверка наличия ожидающих заказов
     */
    private function hasPendingOrders(int $productId, int $warehouseId): bool
    {
        return $this->db->table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.warehouse_id', $warehouseId)
            ->where('orders.status', 'ready_to_pick')
            ->exists();
    }

    /**
     * Проверка на высокий оборот
     */
    private function isHighTurnover(int $productId): bool
    {
        // Товар считается высокооборотным если продается > 100 единиц в месяц
        $monthlySales = $this->db->table('stock_movements')
            ->where('product_id', $productId)
            ->where('movement_type', 'out')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantity');

        return $monthlySales > 100;
    }

    /**
     * Проверка на требование специального хранения
     */
    private function requiresSpecialStorage($product): bool
    {
        $category = strtolower($product->category ?? '');
        
        $specialCategories = [
            'narcotic',
            'psychotropic',
            'refrigerated',
            'frozen',
            'controlled',
        ];

        foreach ($specialCategories as $specialCategory) {
            if (str_contains($category, $specialCategory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Резервирование для заказов
     */
    private function reserveForOrders(int $productId, int $quantity, int $warehouseId): void
    {
        $orders = $this->db->table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.warehouse_id', $warehouseId)
            ->where('orders.status', 'ready_to_pick')
            ->orderBy('orders.priority', 'desc')
            ->orderBy('orders.created_at', 'asc')
            ->get();

        $remainingQuantity = $quantity;

        foreach ($orders as $order) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $needed = min($order->quantity, $remainingQuantity);

            $this->db->table('stock_reservations')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'order_id' => $order->order_id,
                'product_id' => $productId,
                'quantity' => $needed,
                'warehouse_id' => $warehouseId,
                'source_type' => 'cross_docking',
                'status' => 'reserved',
                'expires_at' => now()->addHours(24),
                'created_at' => now(),
            ]);

            $remainingQuantity -= $needed;
        }
    }

    /**
     * Получение статистики cross-docking
     */
    public function getCrossDockingStatistics(int $warehouseId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $tasks = $this->db->table('cross_docking_tasks')
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('created_at', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->get();

        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'total_items_processed' => $tasks->sum('item_count'),
            'average_processing_time_minutes' => $this->calculateAverageProcessingTime($tasks),
        ];
    }

    /**
     * Расчет среднего времени обработки
     */
    private function calculateAverageProcessingTime($tasks): float
    {
        $completedTasks = $tasks->where('status', 'completed')->filter(function ($task) {
            return $task->completed_at && $task->started_at;
        });

        if ($completedTasks->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedTasks->sum(function ($task) {
            $started = \Carbon\Carbon::parse($task->started_at);
            $completed = \Carbon\Carbon::parse($task->completed_at);
            return $started->diffInMinutes($completed);
        });

        return $totalMinutes / $completedTasks->count();
    }
}
