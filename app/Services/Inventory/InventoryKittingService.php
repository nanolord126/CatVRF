<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Inventory Kitting Service
 *
 * Manages product kitting operations:
 * - Create kit definitions
 * - Assemble kits from components
 * - Disassemble kits
 * - Track kit inventory
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryKittingService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Create kit definition
     *
     * @param  string  $kitSku  Kit SKU
     * @param  string  $kitName  Kit name
     * @param  array<array<string, mixed>>  $components  Component items
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return int Kit ID
     */
    public function createKitDefinition(
        string $kitSku,
        string $kitName,
        array $components,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): int {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $kitSku,
            $kitName,
            $components,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $kitId = $this->db->table('inventory_kits')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'kit_sku' => $kitSku,
                'kit_name' => $kitName,
                'warehouse_id' => $warehouseId,
                'status' => 'active',
                'tenant_id' => $tenantId,
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            foreach ($components as $component) {
                $this->db->table('inventory_kit_components')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'kit_id' => $kitId,
                    'inventory_item_id' => $component['inventory_item_id'],
                    'quantity' => $component['quantity'],
                    'is_optional' => $component['is_optional'] ?? false,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logCreated(
                entityType: 'InventoryKit',
                entityId: $kitId,
                context: [
                    'correlation_id' => $correlationId,
                    'kit_sku' => $kitSku,
                    'kit_name' => $kitName,
                    'component_count' => count($components),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return $kitId;
        });
    }

    /**
     * Assemble kit from components
     *
     * @param  int  $kitId  Kit ID
     * @param  int  $quantity  Quantity to assemble
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Assembly result
     */
    public function assembleKit(
        int $kitId,
        int $quantity,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $kitId,
            $quantity,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $kit = $this->db->table('inventory_kits')
                ->where('id', $kitId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $kit) {
                throw new \RuntimeException("Active kit {$kitId} not found");
            }

            $components = $this->db->table('inventory_kit_components')
                ->where('kit_id', $kitId)
                ->get();

            $componentUsage = [];

            foreach ($components as $component) {
                $requiredQty = $component->quantity * $quantity;

                $item = $this->db->table('inventory_items')
                    ->where('id', $component->inventory_item_id)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $item || $item->current_stock < $requiredQty) {
                    throw new \RuntimeException(
                        "Insufficient stock for component {$component->inventory_item_id}. " .
                        "Required: {$requiredQty}, Available: {$item->current_stock ?? 0}"
                    );
                }

                $this->db->table('inventory_items')
                    ->where('id', $component->inventory_item_id)
                    ->decrement('current_stock', $requiredQty);

                $this->db->table('stock_movements')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $component->inventory_item_id,
                    'type' => 'out',
                    'quantity' => -$requiredQty,
                    'reason' => "Kit assembly - Kit: {$kit->kit_sku}",
                    'source_type' => 'kit_assembly',
                    'source_id' => $kitId,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                $componentUsage[] = [
                    'inventory_item_id' => $component->inventory_item_id,
                    'quantity_used' => $requiredQty,
                ];
            }

            $kitItem = $this->db->table('inventory_items')
                ->where('sku', $kit->kit_sku)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if ($kitItem) {
                $this->db->table('inventory_items')
                    ->where('id', $kitItem->id)
                    ->increment('current_stock', $quantity);
            } else {
                $kitItemId = $this->db->table('inventory_items')->insertGetId([
                    'product_id' => $kitId,
                    'sku' => $kit->kit_sku,
                    'name' => $kit->kit_name,
                    'warehouse_id' => $warehouseId,
                    'tenant_id' => $tenantId,
                    'current_stock' => $quantity,
                    'is_kit' => true,
                    'created_at' => now(),
                ]);
            }

            $this->db->table('inventory_kit_assemblies')->insert([
                'uuid' => Str::uuid()->toString(),
                'kit_id' => $kitId,
                'quantity' => $quantity,
                'component_usage' => json_encode($componentUsage),
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'assembled_by' => $userId,
                'assembled_at' => now(),
            ]);

            $this->logAction(
                action: 'kit_assembled',
                entityType: 'InventoryKit',
                entityId: $kitId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantity,
                    'component_usage' => $componentUsage,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'kit_id' => $kitId,
                'kit_sku' => $kit->kit_sku,
                'quantity_assembled' => $quantity,
                'components_used' => $componentUsage,
            ];
        });
    }

    /**
     * Disassemble kit into components
     *
     * @param  int  $kitId  Kit ID
     * @param  int  $quantity  Quantity to disassemble
     * @param  int  $warehouseId  Warehouse ID
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return array Disassembly result
     */
    public function disassembleKit(
        int $kitId,
        int $quantity,
        int $warehouseId,
        int $userId,
        int $tenantId
    ): array {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $kitId,
            $quantity,
            $warehouseId,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $kit = $this->db->table('inventory_kits')
                ->where('id', $kitId)
                ->where('warehouse_id', $warehouseId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $kit) {
                throw new \RuntimeException("Active kit {$kitId} not found");
            }

            $kitItem = $this->db->table('inventory_items')
                ->where('sku', $kit->kit_sku)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $kitItem || ($kitItem->current_stock ?? 0) < $quantity) {
                throw new \RuntimeException(
                    "Insufficient kit stock. Required: {$quantity}, Available: ".($kitItem->current_stock ?? 0)
                );
            }

            $components = $this->db->table('inventory_kit_components')
                ->where('kit_id', $kitId)
                ->get();

            $componentReturn = [];

            foreach ($components as $component) {
                $returnQty = $component->quantity * $quantity;

                $this->db->table('inventory_items')
                    ->where('id', $component->inventory_item_id)
                    ->increment('current_stock', $returnQty);

                $this->db->table('stock_movements')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'inventory_item_id' => $component->inventory_item_id,
                    'type' => 'in',
                    'quantity' => $returnQty,
                    'reason' => "Kit disassembly - Kit: {$kit->kit_sku}",
                    'source_type' => 'kit_disassembly',
                    'source_id' => $kitId,
                    'created_by' => $userId,
                    'created_at' => now(),
                ]);

                $componentReturn[] = [
                    'inventory_item_id' => $component->inventory_item_id,
                    'quantity_returned' => $returnQty,
                ];
            }

            $this->db->table('inventory_items')
                ->where('id', $kitItem->id)
                ->decrement('current_stock', $quantity);

            $this->db->table('inventory_kit_disassemblies')->insert([
                'uuid' => Str::uuid()->toString(),
                'kit_id' => $kitId,
                'quantity' => $quantity,
                'component_return' => json_encode($componentReturn),
                'warehouse_id' => $warehouseId,
                'tenant_id' => $tenantId,
                'disassembled_by' => $userId,
                'disassembled_at' => now(),
            ]);

            $this->logAction(
                action: 'kit_disassembled',
                entityType: 'InventoryKit',
                entityId: $kitId,
                context: [
                    'correlation_id' => $correlationId,
                    'quantity' => $quantity,
                    'component_return' => $componentReturn,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'kit_id' => $kitId,
                'kit_sku' => $kit->kit_sku,
                'quantity_disassembled' => $quantity,
                'components_returned' => $componentReturn,
            ];
        });
    }

    /**
     * Get kit availability
     *
     * @param  int  $kitId  Kit ID
     * @param  int  $warehouseId  Warehouse ID
     * @return array Availability data
     */
    public function getKitAvailability(int $kitId, int $warehouseId): array
    {
        $kit = $this->db->table('inventory_kits')
            ->where('id', $kitId)
            ->first();

        if (! $kit) {
            throw new \RuntimeException("Kit {$kitId} not found");
        }

        $components = $this->db->table('inventory_kit_components')
            ->where('kit_id', $kitId)
            ->get();

        $maxAssemblableQty = PHP_INT_MAX;

        foreach ($components as $component) {
            $item = $this->db->table('inventory_items')
                ->where('id', $component->inventory_item_id)
                ->where('warehouse_id', $warehouseId)
                ->first();

            $availableQty = $item->current_stock ?? 0;
            $possibleQty = intdiv($availableQty, $component->quantity);

            $maxAssemblableQty = min($maxAssemblableQty, $possibleQty);
        }

        $kitItem = $this->db->table('inventory_items')
            ->where('sku', $kit->kit_sku)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return [
            'kit_id' => $kitId,
            'kit_sku' => $kit->kit_sku,
            'kit_name' => $kit->kit_name,
            'current_kit_stock' => $kitItem->current_stock ?? 0,
            'max_assemblable_quantity' => $maxAssemblableQty === PHP_INT_MAX ? 0 : $maxAssemblableQty,
            'component_availability' => $components->map(function ($c) use ($warehouseId) {
                $item = $this->db->table('inventory_items')
                    ->where('id', $c->inventory_item_id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                return [
                    'inventory_item_id' => $c->inventory_item_id,
                    'required_quantity' => $c->quantity,
                    'available_quantity' => $item->current_stock ?? 0,
                    'can_assemble' => ($item->current_stock ?? 0) >= $c->quantity,
                ];
            })->toArray(),
        ];
    }

    /**
     * Update kit definition
     *
     * @param  int  $kitId  Kit ID
     * @param  array<array<string, mixed>>  $components  New components
     * @param  int  $userId  User ID
     * @param  int  $tenantId  Tenant ID
     * @return bool Success
     */
    public function updateKitDefinition(
        int $kitId,
        array $components,
        int $userId,
        int $tenantId
    ): bool {
        $correlationId = Str::uuid()->toString();

        return $this->db->transaction(function () use (
            $kitId,
            $components,
            $userId,
            $tenantId,
            $correlationId
        ) {
            $this->db->table('inventory_kit_components')
                ->where('kit_id', $kitId)
                ->delete();

            foreach ($components as $component) {
                $this->db->table('inventory_kit_components')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'kit_id' => $kitId,
                    'inventory_item_id' => $component['inventory_item_id'],
                    'quantity' => $component['quantity'],
                    'is_optional' => $component['is_optional'] ?? false,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                ]);
            }

            $this->logAction(
                action: 'kit_definition_updated',
                entityType: 'InventoryKit',
                entityId: $kitId,
                context: [
                    'correlation_id' => $correlationId,
                    'new_component_count' => count($components),
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }
}
