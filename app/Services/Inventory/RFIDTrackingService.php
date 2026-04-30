<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

/**
 * RFID Tracking Service
 *
 * Manages RFID-based inventory tracking:
 * - RFID tag registration and management
 * - Real-time location tracking
 * - Batch-level RFID association
 * - RFID event processing
 * - Anti-collision handling
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class RFIDTrackingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly CacheRepository $cache
    ) {}

    /**
     * Register RFID tag for inventory item
     *
     * @param  string  $rfidTag  RFID tag EPC
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  int  $batchId  Batch ID
     * @param  int  $tenantId  Tenant ID
     * @param  int  $userId  User registering
     * @return string RFID record ID
     */
    public function registerRFIDTag(
        string $rfidTag,
        int $inventoryItemId,
        int $batchId,
        int $tenantId,
        int $userId
    ): string {
        $rfidId = (string) \Illuminate\Support\Str::uuid();

        return $this->db->transaction(function () use (
            $rfidTag,
            $inventoryItemId,
            $batchId,
            $tenantId,
            $userId,
            $rfidId
        ) {
            $existing = $this->db->table('rfid_tags')
                ->where('rfid_epc', $rfidTag)
                ->first();

            if ($existing) {
                throw new \RuntimeException("RFID tag already registered: {$rfidTag}");
            }

            $this->db->table('rfid_tags')->insert([
                'id' => $rfidId,
                'rfid_epc' => $rfidTag,
                'inventory_item_id' => $inventoryItemId,
                'batch_id' => $batchId,
                'tenant_id' => $tenantId,
                'status' => 'active',
                'registered_at' => now(),
                'registered_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'rfid_tag_registered',
                entityType: 'RFIDTag',
                entityId: $rfidId,
                context: [
                    'rfid_epc' => $rfidTag,
                    'inventory_item_id' => $inventoryItemId,
                    'batch_id' => $batchId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $rfidId;
        });
    }

    /**
     * Process RFID read event
     *
     * @param  string  $rfidTag  RFID tag EPC
     * @param  string  $readerId  Reader ID
     * @param  string  $location  Location
     * @param  int  $signalStrength  Signal strength (RSSI)
     * @param  int  $tenantId  Tenant ID
     * @return string Event ID
     */
    public function processRFIDEvent(
        string $rfidTag,
        string $readerId,
        string $location,
        int $signalStrength,
        int $tenantId
    ): string {
        $eventId = (string) \Illuminate\Support\Str::uuid();

        $rfidRecord = $this->db->table('rfid_tags')
            ->where('rfid_epc', $rfidTag)
            ->first();

        if (! $rfidRecord) {
            $this->logger->warning('RFID tag not registered', [
                'rfid_epc' => $rfidTag,
                'reader_id' => $readerId,
            ]);

            $this->db->table('rfid_unknown_reads')->insert([
                'id' => $eventId,
                'rfid_epc' => $rfidTag,
                'reader_id' => $readerId,
                'location' => $location,
                'signal_strength' => $signalStrength,
                'tenant_id' => $tenantId,
                'read_at' => now(),
            ]);

            return $eventId;
        }

        $this->db->table('rfid_events')->insert([
            'id' => $eventId,
            'rfid_tag_id' => $rfidRecord->id,
            'reader_id' => $readerId,
            'location' => $location,
            'signal_strength' => $signalStrength,
            'tenant_id' => $tenantId,
            'read_at' => now(),
        ]);

        $this->db->table('rfid_tags')
            ->where('id', $rfidRecord->id)
            ->update([
                'last_read_at' => now(),
                'last_location' => $location,
                'last_reader_id' => $readerId,
            ]);

        $this->cache->tags(['rfid', "rfid:{$rfidRecord->id}"])->flush();

        return $eventId;
    }

    /**
     * Get current location of RFID-tagged item
     *
     * @param  string  $rfidTag  RFID tag EPC
     * @return array Location data
     */
    public function getItemLocation(string $rfidTag): array
    {
        $rfidRecord = $this->db->table('rfid_tags')
            ->where('rfid_epc', $rfidTag)
            ->first();

        if (! $rfidRecord) {
            throw new \RuntimeException("RFID tag not found: {$rfidTag}");
        }

        $lastEvent = $this->db->table('rfid_events')
            ->where('rfid_tag_id', $rfidRecord->id)
            ->orderBy('read_at', 'desc')
            ->first();

        return [
            'rfid_epc' => $rfidTag,
            'inventory_item_id' => $rfidRecord->inventory_item_id,
            'batch_id' => $rfidRecord->batch_id,
            'last_location' => $rfidRecord->last_location,
            'last_reader_id' => $rfidRecord->last_reader_id,
            'last_read_at' => $rfidRecord->last_read_at,
            'signal_strength' => $lastEvent->signal_strength ?? null,
        ];
    }

    /**
     * Find items by location via RFID
     *
     * @param  string  $location  Location
     * @param  int  $warehouseId  Warehouse ID
     * @return array Items at location
     */
    public function findItemsByLocation(string $location, int $warehouseId): array
    {
        $itemIds = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->pluck('id')
            ->toArray();

        $rfidRecords = $this->db->table('rfid_tags')
            ->whereIn('inventory_item_id', $itemIds)
            ->where('last_location', $location)
            ->where('last_read_at', '>=', now()->subMinutes(30))
            ->get();

        $items = [];

        foreach ($rfidRecords as $rfid) {
            $inventoryItem = $this->db->table('inventory_items')
                ->where('id', $rfid->inventory_item_id)
                ->first();

            $items[] = [
                'rfid_epc' => $rfid->rfid_epc,
                'inventory_item_id' => $rfid->inventory_item_id,
                'item_name' => $inventoryItem->name ?? null,
                'item_sku' => $inventoryItem->sku ?? null,
                'batch_id' => $rfid->batch_id,
                'last_read_at' => $rfid->last_read_at,
                'signal_strength' => $rfid->last_event_signal_strength ?? null,
            ];
        }

        return $items;
    }

    /**
     * Perform bulk RFID scan
     *
     * @param  array  $rfidTags  Array of RFID tags
     * @param  string  $readerId  Reader ID
     * @param  string  $location  Location
     * @param  int  $tenantId  Tenant ID
     * @return array Scan results
     */
    public function bulkRFIDScan(
        array $rfidTags,
        string $readerId,
        string $location,
        int $tenantId
    ): array {
        $results = [
            'total_scanned' => count($rfidTags),
            'registered' => 0,
            'unregistered' => 0,
            'events' => [],
        ];

        foreach ($rfidTags as $rfidTag) {
            $rfidRecord = $this->db->table('rfid_tags')
                ->where('rfid_epc', $rfidTag)
                ->first();

            if ($rfidRecord) {
                $results['registered']++;
                $eventId = $this->processRFIDEvent($rfidTag, $readerId, $location, 0, $tenantId);
                $results['events'][] = [
                    'rfid_epc' => $rfidTag,
                    'status' => 'registered',
                    'event_id' => $eventId,
                    'inventory_item_id' => $rfidRecord->inventory_item_id,
                ];
            } else {
                $results['unregistered']++;
                $eventId = (string) \Illuminate\Support\Str::uuid();
                $this->db->table('rfid_unknown_reads')->insert([
                    'id' => $eventId,
                    'rfid_epc' => $rfidTag,
                    'reader_id' => $readerId,
                    'location' => $location,
                    'signal_strength' => 0,
                    'tenant_id' => $tenantId,
                    'read_at' => now(),
                ]);
                $results['events'][] = [
                    'rfid_epc' => $rfidTag,
                    'status' => 'unregistered',
                    'event_id' => $eventId,
                ];
            }
        }

        return $results;
    }

    /**
     * Deactivate RFID tag
     *
     * @param  string  $rfidTag  RFID tag EPC
     * @param  string  $reason  Deactivation reason
     * @param  int  $userId  User deactivating
     * @return bool
     */
    public function deactivateRFIDTag(string $rfidTag, string $reason, int $userId): bool
    {
        $rfidRecord = $this->db->table('rfid_tags')
            ->where('rfid_epc', $rfidTag)
            ->first();

        if (! $rfidRecord) {
            throw new \RuntimeException("RFID tag not found: {$rfidTag}");
        }

        $this->db->table('rfid_tags')
            ->where('id', $rfidRecord->id)
            ->update([
                'status' => 'deactivated',
                'deactivation_reason' => $reason,
                'deactivated_at' => now(),
                'deactivated_by' => $userId,
            ]);

        $this->logAction(
            action: 'rfid_tag_deactivated',
            entityType: 'RFIDTag',
            entityId: $rfidRecord->id,
            context: [
                'rfid_epc' => $rfidTag,
                'reason' => $reason,
            ],
            userId: $userId,
            tenantId: $rfidRecord->tenant_id
        );

        return true;
    }

    /**
     * Get RFID movement history
     *
     * @param  string  $rfidTag  RFID tag EPC
     * @param  int  $days  Period in days
     * @return array Movement history
     */
    public function getRFIDMovementHistory(string $rfidTag, int $days = 30): array
    {
        $rfidRecord = $this->db->table('rfid_tags')
            ->where('rfid_epc', $rfidTag)
            ->first();

        if (! $rfidRecord) {
            throw new \RuntimeException("RFID tag not found: {$rfidTag}");
        }

        $events = $this->db->table('rfid_events')
            ->where('rfid_tag_id', $rfidRecord->id)
            ->where('read_at', '>=', now()->subDays($days))
            ->orderBy('read_at', 'desc')
            ->get()
            ->toArray();

        return [
            'rfid_epc' => $rfidTag,
            'inventory_item_id' => $rfidRecord->inventory_item_id,
            'period_days' => $days,
            'total_events' => count($events),
            'events' => $events,
        ];
    }

    /**
     * Generate RFID inventory report
     *
     * @param  int  $warehouseId  Warehouse ID
     * @return array Inventory report
     */
    public function generateRFIDInventoryReport(int $warehouseId): array
    {
        $itemIds = $this->db->table('inventory_items')
            ->where('warehouse_id', $warehouseId)
            ->pluck('id')
            ->toArray();

        $rfidTags = $this->db->table('rfid_tags')
            ->whereIn('inventory_item_id', $itemIds)
            ->where('status', 'active')
            ->get();

        $totalItems = count($itemIds);
        $taggedItems = $rfidTags->pluck('inventory_item_id')->unique()->count();
        $taggingPercentage = $totalItems > 0 ? ($taggedItems / $totalItems) * 100 : 0;

        $byLocation = $rfidTags->groupBy('last_location')->map(function ($group) {
            return [
                'count' => $group->count(),
                'items' => $group->pluck('inventory_item_id')->unique()->count(),
            ];
        })->toArray();

        return [
            'warehouse_id' => $warehouseId,
            'total_items' => $totalItems,
            'tagged_items' => $taggedItems,
            'tagging_percentage' => $taggingPercentage,
            'total_rfid_tags' => $rfidTags->count(),
            'by_location' => $byLocation,
        ];
    }
}
