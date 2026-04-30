<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Wave Picking Service
 * 
 * Сервис для групповой комплектации (wave picking)
 * Оптимизирует сборку заказов группами для повышения эффективности
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class WavePickingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Создание волны комплектации
     */
    public function createWave(
        int $warehouseId,
        int $tenantId,
        string $waveType = 'standard',
        array $orderIds = [],
        ?int $createdBy = null
    ): string {
        $waveId = (string) Str::uuid();

        return $this->db->transaction(function () use (
            $warehouseId,
            $tenantId,
            $waveType,
            $orderIds,
            $createdBy,
            $waveId
        ) {
            // Создание волны
            $this->db->table('picking_waves')->insert([
                'id' => $waveId,
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'wave_type' => $waveType,
                'status' => 'pending',
                'order_count' => count($orderIds),
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            // Добавление заказов в волну
            if (!empty($orderIds)) {
                foreach ($orderIds as $orderId) {
                    $this->db->table('wave_orders')->insert([
                        'id' => (string) Str::uuid(),
                        'wave_id' => $waveId,
                        'order_id' => $orderId,
                        'status' => 'pending',
                        'created_at' => now(),
                    ]);
                }
            }

            $this->logger->info('Picking wave created', [
                'wave_id' => $waveId,
                'warehouse_id' => $warehouseId,
                'wave_type' => $waveType,
                'order_count' => count($orderIds),
            ]);

            return $waveId;
        });
    }

    /**
     * Автоматическое формирование волны на основе критериев
     */
    public function autoCreateWave(
        int $warehouseId,
        int $tenantId,
        array $criteria = []
    ): string {
        $criteria = array_merge([
            'max_orders' => 50,
            'zone_filter' => null,
            'priority_filter' => null,
            'time_window_minutes' => 60,
        ], $criteria);

        // Получение заказов для волны
        $orders = $this->db->table('orders')
            ->where('warehouse_id', $warehouseId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'ready_to_pick')
            ->where('created_at', '>=', now()->subMinutes($criteria['time_window_minutes']))
            ->limit($criteria['max_orders'])
            ->pluck('id')
            ->toArray();

        if (empty($orders)) {
            throw new \RuntimeException('No orders found for wave creation');
        }

        // Определение типа волны на основе характеристик заказов
        $waveType = $this->determineWaveType($orders);

        return $this->createWave(
            $warehouseId,
            $tenantId,
            $waveType,
            $orders,
            auth()->id()
        );
    }

    /**
     * Генерация заданий на комплектацию для волны
     */
    public function generatePickTasks(string $waveId): array
    {
        $wave = $this->db->table('picking_waves')
            ->where('id', $waveId)
            ->first();

        if (!$wave) {
            throw new \RuntimeException("Wave not found: {$waveId}");
        }

        // Получение всех товаров в заказах волны
        $items = $this->db->table('wave_orders')
            ->join('order_items', 'wave_orders.order_id', '=', 'order_items.order_id')
            ->where('wave_orders.wave_id', $waveId)
            ->select([
                'order_items.product_id',
                'order_items.quantity',
                'wave_orders.order_id',
            ])
            ->get();

        // Группировка товаров для оптимизации маршрута
        $groupedItems = $this->groupItemsByLocation($items, $wave->warehouse_id);

        // Создание заданий на комплектацию
        $tasks = [];
        foreach ($groupedItems as $groupId => $group) {
            $taskId = (string) Str::uuid();

            $this->db->table('picking_tasks')->insert([
                'id' => $taskId,
                'wave_id' => $waveId,
                'group_id' => $groupId,
                'location_id' => $group['location_id'],
                'product_id' => $group['product_id'],
                'total_quantity' => $group['total_quantity'],
                'order_count' => count($group['orders']),
                'status' => 'pending',
                'estimated_pick_time' => $this->estimatePickTime($group['total_quantity']),
                'created_at' => now(),
            ]);

            $tasks[] = [
                'task_id' => $taskId,
                'location_id' => $group['location_id'],
                'product_id' => $group['product_id'],
                'quantity' => $group['total_quantity'],
            ];
        }

        // Обновление статуса волны
        $this->db->table('picking_waves')
            ->where('id', $waveId)
            ->update([
                'status' => 'in_progress',
                'task_count' => count($tasks),
            ]);

        $this->logger->info('Pick tasks generated for wave', [
            'wave_id' => $waveId,
            'task_count' => count($tasks),
        ]);

        return $tasks;
    }

    /**
     * Группировка товаров по локациям
     */
    private function groupItemsByLocation($items, int $warehouseId): array
    {
        $grouped = [];

        foreach ($items as $item) {
            // Получение локации товара
            $location = $this->db->table('inventory_locations')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $item->product_id)
                ->first();

            $locationId = $location ? $location->id : 'unassigned';

            $key = $locationId . '_' . $item->product_id;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'location_id' => $locationId,
                    'product_id' => $item->product_id,
                    'total_quantity' => 0,
                    'orders' => [],
                ];
            }

            $grouped[$key]['total_quantity'] += $item->quantity;
            $grouped[$key]['orders'][] = $item->order_id;
        }

        return $grouped;
    }

    /**
     * Определение типа волны
     */
    private function determineWaveType(array $orderIds): string
    {
        $orders = $this->db->table('orders')
            ->whereIn('id', $orderIds)
            ->get();

        // Проверка на экстренные заказы
        $urgentCount = $orders->where('priority', 'urgent')->count();
        if ($urgentCount > 0) {
            return 'urgent';
        }

        // Проверка на заказы с контролируемыми веществами
        $controlledCount = $orders->where('contains_controlled_substances', true)->count();
        if ($controlledCount > 0) {
            return 'controlled_substances';
        }

        return 'standard';
    }

    /**
     * Оценка времени комплектации
     */
    private function estimatePickTime(int $quantity): int
    {
        // Базовое время: 30 секунд на единицу
        $baseTime = $quantity * 30;

        // Добавляем время на перемещение между локациями
        $travelTime = 60; // 1 минута на перемещение

        return $baseTime + $travelTime;
    }

    /**
     * Запуск волны комплектации
     */
    public function startWave(string $waveId, int $startedBy): void
    {
        $this->db->table('picking_waves')
            ->where('id', $waveId)
            ->update([
                'status' => 'started',
                'started_at' => now(),
                'started_by' => $startedBy,
            ]);

        $this->logger->info('Picking wave started', [
            'wave_id' => $waveId,
            'started_by' => $startedBy,
        ]);
    }

    /**
     * Завершение волны комплектации
     */
    public function completeWave(string $waveId, int $completedBy): void
    {
        $this->db->table('picking_waves')
            ->where('id', $waveId)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => $completedBy,
            ]);

        $this->logger->info('Picking wave completed', [
            'wave_id' => $waveId,
            'completed_by' => $completedBy,
        ]);
    }
}
