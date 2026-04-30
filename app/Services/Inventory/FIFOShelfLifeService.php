<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Services\Security\AuditService;
use App\Services\FraudControl\FraudControlService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Modules\Inventory\Application\Services\FIFOShelfLifeService as ModuleFIFOService;

/**
 * FIFO Shelf Life Service (LEGACY - DEPRECATED)
 *
 * @deprecated Use Modules\Inventory\Application\Services\FIFOShelfLifeService instead
 * This is a legacy wrapper for backward compatibility. All new code should use the module version.
 *
 * Implements First-In-First-Out logic for inventory items with expiry dates.
 * Ensures compliance with medical/pharmaceutical regulations (ФЗ-323, 152-ФЗ).
 *
 * Features:
 * - Automatic FIFO allocation based on expiry dates
 * - Expiry date validation and tracking
 * - Fraud detection on stock operations
 * - Full audit logging with correlation IDs
 * - Batch status management (active, expiring_soon, expired, quarantine)
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class FIFOShelfLifeService
{
    use WithAuditLogging;

    private readonly ModuleFIFOService $moduleService;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {
        $this->moduleService = app(ModuleFIFOService::class);
        Log::warning('Using deprecated App\Services\Inventory\FIFOShelfLifeService. Migrate to Modules\Inventory\Application\Services\FIFOShelfLifeService.');
    }

    /**
     * Allocate stock using FIFO logic
     *
     * Selects batches closest to expiry first (First-In-First-Out).
     * Performs fraud check before allocation.
     *
     * @param  int  $itemId  Inventory item ID
     * @param  int  $quantity  Quantity to allocate
     * @param  string  $context  Context: 'sale', 'prescription', 'transfer', etc.
     * @param  int  $userId  User ID performing the operation
     * @param  int  $tenantId  Tenant ID
     * @param  array  $meta  Additional metadata
     * @return array Allocation result with batch details
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function allocateFromFIFO(
        int $itemId,
        int $quantity,
        string $context,
        int $userId,
        int $tenantId,
        array $meta = []
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }

        // Forward to module service
        return $this->moduleService->allocateStock($itemId, $quantity, $context, $userId, $tenantId, $meta);
    }

    /**
     * Check expiry status for inventory item
     *
     * @param  int  $itemId  Inventory item ID
     * @return array Expiry status information
     */
    public function checkExpiry(int $itemId): array
    {
        $item = InventoryItem::findOrFail($itemId);
        $batches = $this->getBatchesForFIFO($itemId);

        $expiredCount = 0;
        $expiringSoonCount = 0;
        $activeCount = 0;

        foreach ($batches as $batch) {
            if (!$batch->expiry_date) {
                $activeCount++;
                continue;
            }

            if ($batch->expiry_date->isPast()) {
                $expiredCount++;
            } elseif ($batch->expiry_date->lte(now()->addDays(30))) {
                $expiringSoonCount++;
            } else {
                $activeCount++;
            }
        }

        return [
            'item_id' => $itemId,
            'item_name' => $item->name,
            'sku' => $item->sku,
            'total_batches' => $batches->count(),
            'expired_batches' => $expiredCount,
            'expiring_soon_batches' => $expiringSoonCount,
            'active_batches' => $activeCount,
            'next_expiry_date' => $batches->first()?->expiry_date?->format('Y-m-d'),
            'days_until_next_expiry' => $batches->first()?->expiry_date 
                ? now()->diffInDays($batches->first()->expiry_date, false) 
                : null,
        ];
    }

    /**
     * Mark expired items and batches
     *
     * Should be run daily via scheduled task.
     *
     * @param  int  $tenantId  Tenant ID
     * @return array Statistics of marked items
     */
    public function markExpired(int $tenantId): array
    {
        return $this->db->transaction(function () use ($tenantId) {
            // Mark expired batches
            $expiredBatches = \App\Models\WarehouseBatch::where('expiry_date', '<', now()->startOfDay())
                ->where('status', '!=', 'expired')
                ->update(['status' => 'expired']);

            // Mark batches expiring soon (within 30 days)
            $expiringSoonBatches = \App\Models\WarehouseBatch::whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->where('status', 'active')
                ->update(['status' => 'expiring_soon']);

            // Log the operation
            $this->logAction(
                'inventory_expiry_check',
                'WarehouseBatch',
                null,
                [
                    'expired_batches_marked' => $expiredBatches,
                    'expiring_soon_batches_marked' => $expiringSoonBatches,
                ],
                null,
                $tenantId
            );

            return [
                'expired_batches' => $expiredBatches,
                'expiring_soon_batches' => $expiringSoonBatches,
                'timestamp' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get batches ordered by expiry date for FIFO allocation
     *
     * @param  int  $itemId  Inventory item ID
     * @return Collection
     */
    private function getBatchesForFIFO(int $itemId): Collection
    {
        return \App\Models\WarehouseBatch::where('inventory_item_id', $itemId)
            ->where('current_quantity', '>', 0)
            ->where('status', '!=', 'expired')
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->startOfDay());
            })
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Update batch status based on quantity and expiry
     *
     * @param  \App\Models\WarehouseBatch  $batch
     * @return void
     */
    private function updateBatchStatus(\App\Models\WarehouseBatch $batch): void
    {
        $status = 'active';

        if ($batch->expiry_date && $batch->expiry_date->isPast()) {
            $status = 'expired';
        } elseif ($batch->current_quantity <= 0) {
            $status = 'quarantine';
        } elseif ($batch->expiry_date && $batch->expiry_date->lte(now()->addDays(14))) {
            $status = 'expiring_soon';
        }

        if ($batch->status !== $status) {
            $batch->update(['status' => $status]);
        }
    }
}
