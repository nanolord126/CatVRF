<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockExpired;
use Illuminate\Support\Facades\Log;
use App\Models\WarehouseBatch;

/**
 * Handle Expired Stock Listener
 *
 * Processes expired stock batches per ФЗ-323 compliance.
 * Triggers quarantine and reporting actions.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class HandleExpiredStock
{
    public function handle(StockExpired $event): void
    {
        Log::error('Stock expired - compliance action required', [
            'batch_id' => $event->batchId,
            'product_id' => $event->productId,
            'sku' => $event->sku,
            'batch_number' => $event->batchNumber,
            'expiry_date' => $event->expiryDate,
            'quantity' => $event->quantity,
            'warehouse_id' => $event->warehouseId,
            'tenant_id' => $event->tenantId,
            'correlation_id' => $event->correlationId,
        ]);

        // Update batch status to expired/quarantine
        WarehouseBatch::where('id', $event->batchId)
            ->update(['status' => 'quarantine']);

        // TODO: Implement additional compliance actions:
        // - Create quarantine document
        // - Notify compliance officer
        // - Block from allocation
        // - Generate report for regulatory authorities
    }
}
