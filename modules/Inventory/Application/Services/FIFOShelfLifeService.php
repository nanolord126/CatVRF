<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Domain\Entities\InventoryItem;
use Modules\Inventory\Domain\Exceptions\InsufficientStockWithExpiryException;
use Modules\Inventory\Domain\Exceptions\ShelfLifeException;
use Modules\Inventory\Domain\ValueObjects\BatchDeductionResult;
use Modules\Inventory\Infrastructure\Models\InventoryBatchModel;
use Modules\Inventory\Infrastructure\Models\InventoryItemModel;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Psr\Log\LoggerInterface;

final readonly class FIFOShelfLifeService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Main method: Fully automated FIFO deduction with expiry date control
     * Used everywhere: sales, prescriptions, kitchen, grooming
     *
     * @param  InventoryItem  $item
     * @param  int  $quantity
     * @param  string  $context  'sale', 'prescription', 'kitchen', 'grooming'
     * @param  array  $meta  Additional metadata (vet_id, appointment_id, groomer_id, etc.)
     * @return BatchDeductionResult
     *
     * @throws InvalidArgumentException
     * @throws ShelfLifeException
     * @throws InsufficientStockWithExpiryException
     */
    public function autoDeduct(
        InventoryItem $item,
        int $quantity,
        string $context,
        array $meta = []
    ): BatchDeductionResult {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Количество должно быть > 0');
        }

        // Validate controlled item has expiry date
        if ($item->isControlled && $item->expiryDate === null) {
            throw ShelfLifeException::expiryDateRequired($item->name);
        }

        return DB::transaction(function () use ($item, $quantity, $context, $meta) {
            // Automatic FIFO: select batches closest to expiry first
            $batches = InventoryBatchModel::where('inventory_item_id', $item->id)
                ->where('current_quantity', '>', 0)
                ->where('status', '!=', 'expired')
                ->where('expiry_date', '>=', now()->startOfDay()) // Exclude expired
                ->orderBy('expiry_date', 'asc') // ← Main: FIFO by expiry date
                ->orderBy('manufacture_date', 'asc')
                ->lockForUpdate() // Prevent race conditions
                ->get();

            $remaining = $quantity;
            $deducted = collect();

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                // Hard expiry check
                if ($batch->expiry_date->isPast()) {
                    $batch->update(['status' => 'expired']);
                    $this->logAction('inventory_batch_skipped_expired', 'InventoryBatch', $batch->id, [
                        'batch_number' => $batch->batch_number,
                        'expiry_date' => $batch->expiry_date,
                    ], null, null);
                    continue;
                }

                $canTake = min($remaining, $batch->current_quantity);

                // Deduct from batch
                $batch->decrement('current_quantity', $canTake);
                $remaining -= $canTake;

                $deducted->push([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $canTake,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'days_left' => now()->diffInDays($batch->expiry_date, false),
                ]);

                // Automatic status update for batch
                if ($batch->current_quantity <= $canTake) { // Will be 0 after decrement
                    $batch->update(['status' => 'quarantine']);
                } elseif ($batch->expiry_date->lte(now()->addDays(14))) {
                    $batch->update(['status' => 'expiring_soon']);
                }

                // Warning for expiring soon batches
                if ($batch->expiry_date->lte(now()->addDays(14))) {
                    $this->logger->warning('Использование партии близкой к просрочке', [
                        'batch' => $batch->batch_number,
                        'days_left' => $batch->expiry_date->diffInDays(now()),
                        'context' => $context,
                        'item_id' => $item->id,
                    ]);
                }
            }

            if ($remaining > 0) {
                throw InsufficientStockWithExpiryException::create($remaining);
            }

            // Update item quantity
            InventoryItemModel::where('id', $item->id)->decrement('quantity', $quantity);

            // Full audit log
            $this->logFIFOMovement($item, $quantity, $deducted, $context, $meta);

            return new BatchDeductionResult(
                totalQuantity: $quantity,
                deductedBatches: $deducted,
                context: $context,
                meta: $meta
            );
        });
    }

    /**
     * Get the next expiring batch for an item
     */
    public function getNextExpiringBatch(int $itemId): ?InventoryBatchModel
    {
        return InventoryBatchModel::where('inventory_item_id', $itemId)
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date', 'asc')
            ->first();
    }

    /**
     * Daily maintenance: Update batch statuses and notify about expiring items
     */
    public function dailyMaintenance(): void
    {
        // 1. Mark expired batches
        $expiredCount = InventoryBatchModel::where('expiry_date', '<', now()->startOfDay())
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        $this->logger->info('Обновление статусов просроченных партий', ['count' => $expiredCount]);

        // 2. Mark expiring soon batches (within 30 days)
        $expiringSoonCount = InventoryBatchModel::whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->where('status', 'active')
            ->update(['status' => 'expiring_soon']);

        $this->logger->info('Обновление статусов партий с истекающим сроком', ['count' => $expiringSoonCount]);

        // 3. Update item statuses based on batches
        $this->updateItemStatuses();

        // 4. Send notifications
        $this->notifyAboutExpiringItems();
    }

    /**
     * Update inventory item statuses based on their batches
     */
    private function updateItemStatuses(): void
    {
        // Items with expired batches
        $expiredItemIds = InventoryBatchModel::where('status', 'expired')
            ->pluck('inventory_item_id')
            ->unique();

        InventoryItemModel::whereIn('id', $expiredItemIds)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        // Items with expiring soon batches
        $expiringSoonItemIds = InventoryBatchModel::where('status', 'expiring_soon')
            ->whereNotIn('inventory_item_id', $expiredItemIds)
            ->pluck('inventory_item_id')
            ->unique();

        InventoryItemModel::whereIn('id', $expiringSoonItemIds)
            ->where('status', 'active')
            ->update(['status' => 'expiring_soon']);
    }

    /**
     * Notify about items expiring soon
     */
    private function notifyAboutExpiringItems(): void
    {
        $expiringSoon = InventoryBatchModel::expiringSoon(14)
            ->with('inventoryItem')
            ->get();

        if ($expiringSoon->isEmpty()) {
            return;
        }

        $this->logger->info('Уведомление о товарах с истекающим сроком', [
            'count' => $expiringSoon->count(),
            'batches' => $expiringSoon->map(fn ($batch) => [
                'item' => $batch->inventoryItem?->name,
                'batch' => $batch->batch_number,
                'expiry_date' => $batch->expiry_date,
                'days_left' => $batch->getDaysUntilExpiry(),
            ]),
        ]);

        // TODO: Implement actual notifications (Telegram, Email, Dashboard)
        // This would integrate with the existing notification system
    }

    /**
     * Log FIFO movement for audit trail
     */
    private function logFIFOMovement(
        InventoryItem $item,
        int $quantity,
        Collection $deductedBatches,
        string $context,
        array $meta
    ): void {
        $this->logger->info('Списание товара', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'sku' => $item->sku,
            'quantity' => $quantity,
            'context' => $context,
            'batches_used' => $deductedBatches->count(),
            'batch_details' => $deductedBatches,
            'meta' => $meta,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Validate before sale - hard block on expired items
     */
    public function validateBeforeSale(InventoryItem $item, int $quantity): void
    {
        if ($item->isExpired()) {
            throw ShelfLifeException::expiredItem(
                $item->name,
                $item->expiryDate?->format('Y-m-d') ?? 'N/A'
            );
        }

        if ($item->isExpiringSoon(14)) {
            $this->logger->warning('Продажа товара близкого к просрочке', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'days_left' => $item->getDaysUntilExpiry(),
            ]);
        }

        // Check if sufficient stock with valid expiry
        $availableStock = InventoryBatchModel::where('inventory_item_id', $item->id)
            ->usable()
            ->sum('current_quantity');

        if ($availableStock < $quantity) {
            throw InsufficientStockWithExpiryException::forItem(
                $item->name,
                $quantity,
                $availableStock
            );
        }
    }
}
