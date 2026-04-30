<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * Mobile Warehouse Service
 *
 * Manages mobile app functionality for warehouse workers:
 * - Mobile authentication
 * - Task assignment and tracking
 * - Real-time inventory updates
 * - Offline mode support
 * - Push notifications
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class MobileWarehouseService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Register mobile device for worker
     *
     * @param  int  $userId  User ID
     * @param  string  $deviceToken  Device token
     * @param  string  $deviceType  Device type (ios, android)
     * @param  string  $appVersion  App version
     * @return string Device registration ID
     */
    public function registerMobileDevice(
        int $userId,
        string $deviceToken,
        string $deviceType,
        string $appVersion
    ): string {
        $deviceId = (string) \Illuminate\Support\Str::uuid();

        $this->db->table('mobile_devices')->insert([
            'id' => $deviceId,
            'user_id' => $userId,
            'device_token' => $deviceToken,
            'device_type' => $deviceType,
            'app_version' => $appVersion,
            'is_active' => true,
            'registered_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->logAction(
            action: 'mobile_device_registered',
            entityType: 'MobileDevice',
            entityId: $deviceId,
            context: [
                'device_type' => $deviceType,
                'app_version' => $appVersion,
            ],
            userId: $userId,
            tenantId: 0
        );

        return $deviceId;
    }

    /**
     * Assign task to mobile worker
     *
     * @param  string  $taskId  Task ID
     * @param  int  $workerId  Worker ID
     * @param  int  $priority  Priority (1-10)
     * @return bool
     */
    public function assignTaskToWorker(string $taskId, int $workerId, int $priority = 5): bool
    {
        $this->db->table('mobile_worker_tasks')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'task_id' => $taskId,
            'worker_id' => $workerId,
            'priority' => $priority,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->sendPushNotification($workerId, 'New Task Assigned', "You have a new task with priority {$priority}");

        return true;
    }

    /**
     * Get worker's active tasks
     *
     * @param  int  $workerId  Worker ID
     * @return array Active tasks
     */
    public function getWorkerActiveTasks(int $workerId): array
    {
        $tasks = $this->db->table('mobile_worker_tasks as mwt')
            ->where('mwt.worker_id', $workerId)
            ->whereIn('mwt.status', ['assigned', 'in_progress'])
            ->orderBy('mwt.priority', 'desc')
            ->orderBy('mwt.assigned_at', 'asc')
            ->get()
            ->toArray();

        return $tasks;
    }

    /**
     * Update task status from mobile
     *
     * @param  string  $taskId  Task ID
     * @param  string  $status  New status
     * @param  array  $metadata  Additional metadata
     * @param  int  $workerId  Worker ID
     * @return bool
     */
    public function updateTaskStatus(string $taskId, string $status, array $metadata, int $workerId): bool
    {
        $this->db->table('mobile_worker_tasks')
            ->where('task_id', $taskId)
            ->where('worker_id', $workerId)
            ->update([
                'status' => $status,
                'metadata' => json_encode($metadata),
                'updated_at' => now(),
            ]);

        if ($status === 'completed') {
            $this->db->table('mobile_worker_tasks')
                ->where('task_id', $taskId)
                ->update([
                    'completed_at' => now(),
                ]);
        }

        $this->logAction(
            action: 'mobile_task_status_updated',
            entityType: 'MobileWorkerTask',
            entityId: $taskId,
            context: [
                'status' => $status,
                'metadata' => $metadata,
            ],
            userId: $workerId,
            tenantId: 0
        );

        return true;
    }

    /**
     * Sync inventory updates from mobile
     *
     * @param  int  $workerId  Worker ID
     * @param  array  $updates  Inventory updates
     * @return array Sync results
     */
    public function syncInventoryUpdates(int $workerId, array $updates): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($updates as $update) {
            try {
                $this->db->table('inventory_items')
                    ->where('id', $update['inventory_item_id'])
                    ->update([
                        'current_stock' => $update['new_quantity'],
                        'updated_at' => now(),
                    ]);

                $this->db->table('stock_movements')->insert([
                    'inventory_item_id' => $update['inventory_item_id'],
                    'type' => 'adjust',
                    'quantity' => $update['quantity_change'],
                    'reason' => 'Mobile sync update',
                    'source_type' => 'mobile_sync',
                    'source_id' => $workerId,
                    'correlation_id' => $update['correlation_id'] ?? null,
                    'created_by' => $workerId,
                    'created_at' => now(),
                ]);

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'inventory_item_id' => $update['inventory_item_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->cache->tags(['inventory'])->flush();

        return $results;
    }

    /**
     * Download data for offline mode
     *
     * @param  int  $workerId  Worker ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Offline data
     */
    public function downloadOfflineData(int $workerId, int $warehouseId): array
    {
        $inventoryItems = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->select('id', 'name', 'sku', 'current_stock', 'location', 'unit_cost')
            ->get()
            ->toArray();

        $products = $this->db->table('products')
            ->whereIn('id', array_column($inventoryItems, 'id'))
            ->select('id', 'name', 'sku', 'category', 'image_url')
            ->get()
            ->toArray();

        $locations = $this->db->table('warehouse_locations')
            ->where('warehouse_id', $warehouseId)
            ->select('id', 'name', 'zone', 'aisle', 'shelf', 'bin')
            ->get()
            ->toArray();

        $tasks = $this->getWorkerActiveTasks($workerId);

        return [
            'sync_timestamp' => now()->toIso8601String(),
            'warehouse_id' => $warehouseId,
            'inventory_items' => $inventoryItems,
            'products' => $products,
            'locations' => $locations,
            'tasks' => $tasks,
        ];
    }

    /**
     * Upload offline changes
     *
     * @param  int  $workerId  Worker ID
     * @param  array  $changes  Offline changes
     * @return array Upload results
     */
    public function uploadOfflineChanges(int $workerId, array $changes): array
    {
        $results = [
            'inventory_updates' => $this->syncInventoryUpdates($workerId, $changes['inventory_updates'] ?? []),
            'task_updates' => [],
            'errors' => [],
        ];

        foreach ($changes['task_updates'] ?? [] as $taskUpdate) {
            try {
                $this->updateTaskStatus(
                    $taskUpdate['task_id'],
                    $taskUpdate['status'],
                    $taskUpdate['metadata'] ?? [],
                    $workerId
                );
                $results['task_updates'][] = ['task_id' => $taskUpdate['task_id'], 'status' => 'success'];
            } catch (\Exception $e) {
                $results['task_updates'][] = [
                    'task_id' => $taskUpdate['task_id'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Send push notification to worker
     *
     * @param  int  $workerId  Worker ID
     * @param  string  $title  Notification title
     * @param  string  $message  Notification message
     * @return bool
     */
    private function sendPushNotification(int $workerId, string $title, string $message): bool
    {
        $devices = $this->db->table('mobile_devices')
            ->where('user_id', $workerId)
            ->where('is_active', true)
            ->get();

        foreach ($devices as $device) {
            $this->db->table('push_notifications')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'device_id' => $device->id,
                'title' => $title,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Get worker location
     *
     * @param  int  $workerId  Worker ID
     * @return array|null Location data
     */
    public function getWorkerLocation(int $workerId): ?array
    {
        $location = $this->db->table('worker_locations')
            ->where('worker_id', $workerId)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (! $location) {
            return null;
        }

        return [
            'worker_id' => $workerId,
            'zone' => $location->zone,
            'aisle' => $location->aisle,
            'shelf' => $location->shelf,
            'bin' => $location->bin,
            'updated_at' => $location->updated_at,
        ];
    }

    /**
     * Update worker location
     *
     * @param  int  $workerId  Worker ID
     * @param  string  $zone  Zone
     * @param  string  $aisle  Aisle
     * @param  string  $shelf  Shelf
     * @param  string  $bin  Bin
     * @return bool
     */
    public function updateWorkerLocation(
        int $workerId,
        string $zone,
        string $aisle,
        string $shelf,
        string $bin
    ): bool {
        $this->db->table('worker_locations')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'worker_id' => $workerId,
            'zone' => $zone,
            'aisle' => $aisle,
            'shelf' => $shelf,
            'bin' => $bin,
            'updated_at' => now(),
        ]);

        $this->cache->tags(['worker_locations', "worker:{$workerId}"])->flush();

        return true;
    }

    /**
     * Get nearby workers
     *
     * @param  string  $zone  Zone
     * @param  string  $aisle  Aisle
     * @param  int  $minutesAgo  Minutes ago threshold
     * @return array Nearby workers
     */
    public function getNearbyWorkers(string $zone, string $aisle, int $minutesAgo = 10): array
    {
        $workers = $this->db->table('worker_locations as wl')
            ->join('users as u', 'wl.worker_id', '=', 'u.id')
            ->where('wl.zone', $zone)
            ->where('wl.aisle', $aisle)
            ->where('wl.updated_at', '>=', now()->subMinutes($minutesAgo))
            ->select('wl.worker_id', 'u.name', 'wl.zone', 'wl.aisle', 'wl.updated_at')
            ->get()
            ->toArray();

        return $workers;
    }

    /**
     * Logout from mobile device
     *
     * @param  int  $userId  User ID
     * @param  string  $deviceId  Device ID
     * @return bool
     */
    public function logoutMobileDevice(int $userId, string $deviceId): bool
    {
        $this->db->table('mobile_devices')
            ->where('id', $deviceId)
            ->where('user_id', $userId)
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
            ]);

        return true;
    }
}
