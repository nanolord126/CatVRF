<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Integration Service
 *
 * Manages external system integrations:
 * - ERP integration
 * - E-commerce platform sync
 * - Supplier integration
 * - 3PL integration
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Sync inventory to ERP
     *
     * @param  int  $inventoryItemId  Inventory item ID
     * @param  string  $erpSystem  ERP system identifier
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Sync result
     */
    public function syncToERP(
        int $inventoryItemId,
        string $erpSystem,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        try {
            $item = $this->db->table('inventory_items')
                ->where('id', $inventoryItemId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $item) {
                throw new \RuntimeException("Inventory item {$inventoryItemId} not found");
            }

            $erpConfig = $this->getERPConfig($erpSystem, $tenantId);

            if (! $erpConfig) {
                throw new \RuntimeException("ERP configuration for {$erpSystem} not found");
            }

            $payload = [
                'item_id' => $item->id,
                'sku' => $item->sku,
                'quantity' => $item->current_stock,
                'reserved_quantity' => $item->reserved_stock,
                'available_quantity' => $item->current_stock - $item->reserved_stock,
                'warehouse_id' => $item->warehouse_id,
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$erpConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(30)
                ->post($erpConfig['endpoint'].'/inventory/sync', $payload);

            if (! $response->successful()) {
                throw new \RuntimeException("ERP sync failed: {$response->body()}");
            }

            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'erp',
                'integration_system' => $erpSystem,
                'inventory_item_id' => $inventoryItemId,
                'direction' => 'outbound',
                'payload' => json_encode($payload),
                'response' => $response->body(),
                'status' => 'success',
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'erp_sync_completed',
                entityType: 'InventoryIntegration',
                entityId: $inventoryItemId,
                context: [
                    'correlation_id' => $correlationId,
                    'erp_system' => $erpSystem,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'erp_system' => $erpSystem,
                'synced_at' => now()->toIso8601String(),
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'erp',
                'integration_system' => $erpSystem,
                'inventory_item_id' => $inventoryItemId,
                'direction' => 'outbound',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logger->error('ERP sync failed', [
                'inventory_item_id' => $inventoryItemId,
                'erp_system' => $erpSystem,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync inventory from E-commerce platform
     *
     * @param  string  $platform  Platform identifier
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Sync result
     */
    public function syncFromEcommerce(
        string $platform,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        try {
            $platformConfig = $this->getPlatformConfig($platform, $tenantId);

            if (! $platformConfig) {
                throw new \RuntimeException("Platform configuration for {$platform} not found");
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$platformConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(60)
                ->get($platformConfig['endpoint'].'/inventory');

            if (! $response->successful()) {
                throw new \RuntimeException("E-commerce sync failed: {$response->body()}");
            }

            $inventoryData = $response->json();

            $updatedCount = 0;

            foreach ($inventoryData['items'] ?? [] as $platformItem) {
                $localItem = $this->db->table('inventory_items')
                    ->where('sku', $platformItem['sku'])
                    ->where('tenant_id', $tenantId)
                    ->first();

                if ($localItem) {
                    $this->db->table('inventory_items')
                        ->where('id', $localItem->id)
                        ->update([
                            'current_stock' => $platformItem['quantity'],
                            'updated_at' => now(),
                        ]);

                    $updatedCount++;
                }
            }

            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'ecommerce',
                'integration_system' => $platform,
                'direction' => 'inbound',
                'payload' => json_encode($inventoryData),
                'status' => 'success',
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'ecommerce_sync_completed',
                entityType: 'InventoryIntegration',
                entityId: 0,
                context: [
                    'correlation_id' => $correlationId,
                    'platform' => $platform,
                    'updated_count' => $updatedCount,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'platform' => $platform,
                'synced_at' => now()->toIso8601String(),
                'updated_count' => $updatedCount,
            ];
        } catch (\Exception $e) {
            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'ecommerce',
                'integration_system' => $platform,
                'direction' => 'inbound',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logger->error('E-commerce sync failed', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send purchase order to supplier
     *
     * @param  int  $purchaseOrderId  Purchase order ID
     * @param  int  $supplierId  Supplier ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Send result
     */
    public function sendPurchaseOrderToSupplier(
        int $purchaseOrderId,
        int $supplierId,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        try {
            $purchaseOrder = $this->db->table('purchase_orders')
                ->where('id', $purchaseOrderId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (! $purchaseOrder) {
                throw new \RuntimeException("Purchase order {$purchaseOrderId} not found");
            }

            $supplierConfig = $this->getSupplierConfig($supplierId, $tenantId);

            if (! $supplierConfig) {
                throw new \RuntimeException("Supplier configuration for {$supplierId} not found");
            }

            $items = $this->db->table('purchase_order_items')
                ->where('purchase_order_id', $purchaseOrderId)
                ->get();

            $payload = [
                'po_number' => $purchaseOrder->po_number,
                'po_id' => $purchaseOrderId,
                'items' => $items->map(fn ($i) => [
                    'sku' => $i->sku ?? '',
                    'quantity' => $i->quantity,
                    'unit_cost' => $i->unit_cost,
                ])->toArray(),
                'total_amount' => $purchaseOrder->total_amount,
                'currency' => $purchaseOrder->currency,
                'ordered_at' => $purchaseOrder->created_at->toIso8601String(),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$supplierConfig['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(30)
                ->post($supplierConfig['endpoint'].'/purchase-orders', $payload);

            if (! $response->successful()) {
                throw new \RuntimeException("Supplier PO send failed: {$response->body()}");
            }

            $this->db->table('purchase_orders')
                ->where('id', $purchaseOrderId)
                ->update([
                    'status' => 'sent_to_supplier',
                    'sent_to_supplier_at' => now(),
                ]);

            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'supplier',
                'integration_system' => "supplier_{$supplierId}",
                'purchase_order_id' => $purchaseOrderId,
                'direction' => 'outbound',
                'payload' => json_encode($payload),
                'response' => $response->body(),
                'status' => 'success',
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'purchase_order_sent_to_supplier',
                entityType: 'PurchaseOrder',
                entityId: $purchaseOrderId,
                context: [
                    'correlation_id' => $correlationId,
                    'supplier_id' => $supplierId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'success' => true,
                'supplier_id' => $supplierId,
                'po_number' => $purchaseOrder->po_number,
                'sent_at' => now()->toIso8601String(),
                'response' => $response->json(),
            ];
        } catch (\Exception $e) {
            $this->db->table('inventory_integration_logs')->insert([
                'uuid' => Str::uuid()->toString(),
                'integration_type' => 'supplier',
                'integration_system' => "supplier_{$supplierId}",
                'purchase_order_id' => $purchaseOrderId,
                'direction' => 'outbound',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
            ]);

            $this->logger->error('Supplier PO send failed', [
                'purchase_order_id' => $purchaseOrderId,
                'supplier_id' => $supplierId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get ERP configuration
     *
     * @param  string  $erpSystem  ERP system
     * @param  int  $tenantId  Tenant ID
     * @return array|null Configuration
     */
    private function getERPConfig(string $erpSystem, int $tenantId): ?array
    {
        $config = $this->db->table('inventory_integrations')
            ->where('integration_type', 'erp')
            ->where('integration_system', $erpSystem)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return null;
        }

        return [
            'endpoint' => $config->endpoint,
            'api_key' => $config->api_key,
            'additional_config' => json_decode($config->additional_config, true),
        ];
    }

    /**
     * Get platform configuration
     *
     * @param  string  $platform  Platform
     * @param  int  $tenantId  Tenant ID
     * @return array|null Configuration
     */
    private function getPlatformConfig(string $platform, int $tenantId): ?array
    {
        $config = $this->db->table('inventory_integrations')
            ->where('integration_type', 'ecommerce')
            ->where('integration_system', $platform)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return null;
        }

        return [
            'endpoint' => $config->endpoint,
            'api_key' => $config->api_key,
            'additional_config' => json_decode($config->additional_config, true),
        ];
    }

    /**
     * Get supplier configuration
     *
     * @param  int  $supplierId  Supplier ID
     * @param  int  $tenantId  Tenant ID
     * @return array|null Configuration
     */
    private function getSupplierConfig(int $supplierId, int $tenantId): ?array
    {
        $config = $this->db->table('supplier_integrations')
            ->where('supplier_id', $supplierId)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return null;
        }

        return [
            'endpoint' => $config->endpoint,
            'api_key' => $config->api_key,
            'additional_config' => json_decode($config->additional_config, true),
        ];
    }

    /**
     * Get integration status
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Integration status
     */
    public function getIntegrationStatus(int $tenantId): array
    {
        $integrations = $this->db->table('inventory_integrations')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $recentLogs = $this->db->table('inventory_integration_logs')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'tenant_id' => $tenantId,
            'active_integrations' => $integrations->map(fn ($i) => [
                'type' => $i->integration_type,
                'system' => $i->integration_system,
                'endpoint' => $i->endpoint,
                'last_sync' => $i->last_sync_at?->toIso8601String(),
            ])->toArray(),
            'recent_activity' => $recentLogs->map(fn ($log) => [
                'type' => $log->integration_type,
                'system' => $log->integration_system,
                'direction' => $log->direction,
                'status' => $log->status,
                'created_at' => $log->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }
}
