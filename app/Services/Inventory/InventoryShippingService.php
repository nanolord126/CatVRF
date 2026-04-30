<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Shipping Service
 *
 * Manages warehouse shipping operations:
 * - Create shipments
 * - Generate shipping labels
 * - Track shipments
 * - Handle returns
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryShippingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create shipment from pack list
     *
     * @param  int  $packListId  Pack list ID
     * @param  string  $shippingCarrier  Shipping carrier
     * @param  string  $serviceLevel  Service level
     * @param  array<string, mixed>  $shippingAddress  Shipping address
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Shipment ID
     */
    public function createShipment(
        int $packListId,
        string $shippingCarrier,
        string $serviceLevel,
        array $shippingAddress,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $packListId,
            $shippingCarrier,
            $serviceLevel,
            $shippingAddress,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $packList = $this->db->table('inventory_pack_lists')
                ->where('id', $packListId)
                ->where('status', 'completed')
                ->first();

            if (! $packList) {
                throw new \RuntimeException("Completed pack list {$packListId} not found");
            }

            $shipmentId = $this->db->table('inventory_shipments')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'shipment_number' => $this->generateShipmentNumber(),
                'pack_list_id' => $packListId,
                'order_id' => $packList->order_id,
                'order_type' => $packList->order_type,
                'warehouse_id' => $packList->warehouse_id,
                'shipping_carrier' => $shippingCarrier,
                'service_level' => $serviceLevel,
                'shipping_address' => json_encode($shippingAddress),
                'status' => 'pending',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->logCreated(
                entityType: 'InventoryShipment',
                entityId: $shipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'shipment_number' => $this->generateShipmentNumber(),
                    'pack_list_id' => $packListId,
                    'order_id' => $packList->order_id,
                    'shipping_carrier' => $shippingCarrier,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $shipmentId;
        });
    }

    /**
     * Generate shipping label
     *
     * @param  int  $shipmentId  Shipment ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Label data
     */
    public function generateShippingLabel(int $shipmentId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $shipmentId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $shipment = $this->db->table('inventory_shipments')
                ->where('id', $shipmentId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $shipment) {
                throw new \RuntimeException("Pending shipment {$shipmentId} not found");
            }

            $trackingNumber = $this->generateTrackingNumber($shipment->shipping_carrier);

            $labelData = [
                'tracking_number' => $trackingNumber,
                'shipment_number' => $shipment->shipment_number,
                'carrier' => $shipment->shipping_carrier,
                'service_level' => $shipment->service_level,
                'shipping_address' => json_decode($shipment->shipping_address, true),
                'generated_at' => now()->toIso8601String(),
            ];

            $this->db->table('inventory_shipments')
                ->where('id', $shipmentId)
                ->update([
                    'status' => 'labeled',
                    'tracking_number' => $trackingNumber,
                    'label_generated_at' => now(),
                    'label_data' => json_encode($labelData),
                ]);

            $this->logAction(
                action: 'shipping_label_generated',
                entityType: 'InventoryShipment',
                entityId: $shipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'tracking_number' => $trackingNumber,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $labelData;
        });
    }

    /**
     * Mark shipment as shipped
     *
     * @param  int  $shipmentId  Shipment ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function markAsShipped(int $shipmentId, int $userId, int $tenantId): bool
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $shipmentId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $shipment = $this->db->table('inventory_shipments')
                ->where('id', $shipmentId)
                ->where('status', 'labeled')
                ->lockForUpdate()
                ->first();

            if (! $shipment) {
                throw new \RuntimeException("Labeled shipment {$shipmentId} not found");
            }

            $this->db->table('inventory_shipments')
                ->where('id', $shipmentId)
                ->update([
                    'status' => 'shipped',
                    'shipped_at' => now(),
                    'shipped_by' => $userId,
                ]);

            $this->logAction(
                action: 'shipment_marked_as_shipped',
                entityType: 'InventoryShipment',
                entityId: $shipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'tracking_number' => $shipment->tracking_number,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Update shipment tracking
     *
     * @param  int  $shipmentId  Shipment ID
     * @param  string  $status  Tracking status
     * @param  string|null  $location  Current location
     * @param  string|null  $estimatedDelivery  Estimated delivery date
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function updateTracking(
        int $shipmentId,
        string $status,
        ?string $location = null,
        ?string $estimatedDelivery = null,
        int $userId = 0,
        int $tenantId = 0
    ): bool {
        $correlationId = Str::uuid()->toString();

        $updateData = [
            'tracking_status' => $status,
            'last_tracking_update' => now(),
        ];

        if ($location) {
            $updateData['current_location'] = $location;
        }

        if ($estimatedDelivery) {
            $updateData['estimated_delivery'] = $estimatedDelivery;
        }

        if ($status === 'delivered') {
            $updateData['status'] = 'delivered';
            $updateData['delivered_at'] = now();
        }

        $this->db->table('inventory_shipments')
            ->where('id', $shipmentId)
            ->update($updateData);

        $this->db->table('inventory_tracking_events')->insert([
            'uuid' => Str::uuid()->toString(),
            'shipment_id' => $shipmentId,
            'status' => $status,
            'location' => $location,
            'event_time' => now(),
            'tenant_id' => $tenantId,
            'created_at' => now(),
        ]);

        if ($userId > 0 && $tenantId > 0) {
            $this->logAction(
                action: 'shipment_tracking_updated',
                entityType: 'InventoryShipment',
                entityId: $shipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'status' => $status,
                    'location' => $location,
                ],
                userId: $userId,
                tenantId: $tenantId
            );
        }

        return true;
    }

    /**
     * Process return
     *
     * @param  int  $shipmentId  Original shipment ID
     * @param  string  $returnReason  Return reason
     * @param  array<array<string, mixed>>  $returnedItems  Returned items
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Return shipment ID
     */
    public function processReturn(
        int $shipmentId,
        string $returnReason,
        array $returnedItems,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $shipmentId,
            $returnReason,
            $returnedItems,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $originalShipment = $this->db->table('inventory_shipments')
                ->where('id', $shipmentId)
                ->first();

            if (! $originalShipment) {
                throw new \RuntimeException("Original shipment {$shipmentId} not found");
            }

            $returnShipmentId = $this->db->table('inventory_shipments')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'shipment_number' => $this->generateShipmentNumber('RTN'),
                'original_shipment_id' => $shipmentId,
                'order_id' => $originalShipment->order_id,
                'order_type' => $originalShipment->order_type,
                'warehouse_id' => $originalShipment->warehouse_id,
                'shipping_carrier' => $originalShipment->shipping_carrier,
                'status' => 'return_pending',
                'return_reason' => $returnReason,
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($returnedItems as $item) {
                $this->db->table('inventory_return_items')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'return_shipment_id' => $returnShipmentId,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'] ?? 'unknown',
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logAction(
                action: 'return_shipment_created',
                entityType: 'InventoryShipment',
                entityId: $returnShipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'original_shipment_id' => $shipmentId,
                    'return_reason' => $returnReason,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $returnShipmentId;
        });
    }

    /**
     * Complete return and restock
     *
     * @param  int  $returnShipmentId  Return shipment ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Completion result
     */
    public function completeReturnAndRestock(int $returnShipmentId, int $userId, int $tenantId): array
    {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $returnShipmentId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $returnShipment = $this->db->table('inventory_shipments')
                ->where('id', $returnShipmentId)
                ->where('status', 'return_pending')
                ->lockForUpdate()
                ->first();

            if (! $returnShipment) {
                throw new \RuntimeException("Pending return shipment {$returnShipmentId} not found");
            }

            $returnItems = $this->db->table('inventory_return_items')
                ->where('return_shipment_id', $returnShipmentId)
                ->get();

            $restockedItems = [];

            foreach ($returnItems as $item) {
                if (in_array($item->condition, ['new', 'like_new', 'good'])) {
                    $this->db->table('inventory_items')
                        ->where('id', $item->inventory_item_id)
                        ->increment('current_stock', $item->quantity);

                    $this->db->table('stock_movements')->insert([
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                        'inventory_item_id' => $item->inventory_item_id,
                        'type' => 'in',
                        'quantity' => $item->quantity,
                        'reason' => "Return restock - Condition: {$item->condition}",
                        'source_type' => 'return',
                        'source_id' => $returnShipmentId,
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]);

                    $restockedItems[] = [
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $item->quantity,
                    ];
                }
            }

            $this->db->table('inventory_shipments')
                ->where('id', $returnShipmentId)
                ->update([
                    'status' => 'return_completed',
                    'completed_at' => now(),
                    'completed_by' => $userId,
                ]);

            $this->logAction(
                action: 'return_completed_and_restocked',
                entityType: 'InventoryShipment',
                entityId: $returnShipmentId,
                context: [
                    'correlation_id' => $correlationId,
                    'restocked_items' => $restockedItems,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'return_shipment_id' => $returnShipmentId,
                'total_items' => $returnItems->count(),
                'restocked_count' => count($restockedItems),
                'restocked_items' => $restockedItems,
            ];
        });
    }

    /**
     * Generate shipment number
     *
     * @param  string  $prefix  Prefix
     * @return string Shipment number
     */
    private function generateShipmentNumber(string $prefix = 'SHP'): string
    {
        $date = now()->format('Ymd');
        $sequence = $this->db->table('inventory_shipments')
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Generate tracking number
     *
     * @param  string  $carrier  Carrier
     * @return string Tracking number
     */
    private function generateTrackingNumber(string $carrier): string
    {
        $prefix = match ($carrier) {
            'fedex' => 'FDX',
            'ups' => 'UPS',
            'dhl' => 'DHL',
            default => 'TRK',
        };

        return $prefix.strtoupper(Str::random(12));
    }
}
