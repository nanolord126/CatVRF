<?php declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InventoryManagementService
{
    public function getCurrentStock(int $itemId): int
    {
        $item = InventoryItem::findOrFail($itemId);

        return $item->current_stock - $item->hold_stock;
    }

    public function reserveStock(
        int $itemId,
        int $quantity,
        string $sourceType,
        int $sourceId,
        string $correlationId = '',
    ): bool {
        return DB::transaction(function () use ($itemId, $quantity, $sourceType, $sourceId, $correlationId) {
            $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            if (($item->current_stock - $item->hold_stock) < $quantity) {
                Log::channel('inventory')->warning('Insufficient stock', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'requested' => $quantity,
                    'available' => $item->current_stock - $item->hold_stock,
                ]);

                return false;
            }

            $item->increment('hold_stock', $quantity);

            StockMovement::create([
                'inventory_item_id' => $itemId,
                'type' => 'reserve',
                'quantity' => $quantity,
                'reason' => 'Reservation',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            ]);

            Log::channel('inventory')->info('Stock reserved', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'quantity' => $quantity,
            ]);

            return true;
        });
    }

    public function releaseStock(
        int $itemId,
        int $quantity,
        string $sourceType,
        int $sourceId,
        string $correlationId = '',
    ): bool {
        return DB::transaction(function () use ($itemId, $quantity, $sourceType, $sourceId, $correlationId) {
            $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            if ($item->hold_stock < $quantity) {
                return false;
            }

            $item->decrement('hold_stock', $quantity);

            StockMovement::create([
                'inventory_item_id' => $itemId,
                'type' => 'release',
                'quantity' => $quantity,
                'reason' => 'Release hold',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            ]);

            return true;
        });
    }

    public function deductStock(
        int $itemId,
        int $quantity,
        string $reason,
        string $sourceType,
        int $sourceId,
        string $correlationId = '',
    ): bool {
        return DB::transaction(function () use ($itemId, $quantity, $reason, $sourceType, $sourceId, $correlationId) {
            $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            if ($item->current_stock < $quantity) {
                throw new \Exception('Insufficient stock to deduct');
            }

            $item->decrement('current_stock', $quantity);

            // Release hold if deduction matches held stock
            if ($item->hold_stock >= $quantity) {
                $item->decrement('hold_stock', $quantity);
            } elseif ($item->hold_stock > 0) {
                $item->update(['hold_stock' => 0]);
            }

            StockMovement::create([
                'inventory_item_id' => $itemId,
                'type' => 'out',
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            ]);

            Log::channel('inventory')->info('Stock deducted', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'quantity' => $quantity,
            ]);

            return true;
        });
    }

    public function addStock(
        int $itemId,
        int $quantity,
        string $reason,
        string $sourceType = 'manual',
        string $correlationId = '',
    ): bool {
        return DB::transaction(function () use ($itemId, $quantity, $reason, $sourceType, $correlationId) {
            $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            $item->increment('current_stock', $quantity);

            StockMovement::create([
                'inventory_item_id' => $itemId,
                'type' => 'in',
                'quantity' => $quantity,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => null,
                'correlation_id' => $correlationId ?: Str::uuid()->toString(),
            ]);

            return true;
        });
    }

    public function checkLowStock(): Collection
    {
        return InventoryItem::whereColumn('current_stock', '<', 'min_stock_threshold')
            ->get();
    }
}
