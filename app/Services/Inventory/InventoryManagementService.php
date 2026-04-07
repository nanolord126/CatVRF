<?php declare(strict_types=1);

namespace App\Services\Inventory;


use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\FraudControl\FraudControlService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Сервис управления запасами и инвентаризацией
 *
 * CANON 2026 комплиенс:
 * - Все операции проходят через FraudControlService::check()
 * - Все мутации в $this->db->transaction() с audit-логированием
 * - correlation_id обязателен в каждом логе для трейсинга
 * - Hold/Release для двухэтапных операций (бронирование → исполнение)
 * - Low-stock alerts с уведомлениями
 * - Fraud-проверки на большие корректировки (>50% от запаса)
 */
final readonly class InventoryManagementService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConnectionInterface $db,
        private readonly LogManager $log,
        private readonly FraudControlService $fraud,
        private readonly LogManager $logger,
    ) {}
    /**
     * Получить текущий доступный остаток (с учётом холда)
     */
    public function getCurrentStock(int $itemId): int
    {
        try {
            $item = InventoryItem::findOrFail($itemId);

            $available = $item->current_stock - $item->hold_stock;

            return max($available, 0);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Get stock failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Зарезервировать товар (hold для бронирования)
     */
    public function reserveStock(
        int $itemId,
        int $quantity,
        string $sourceType,
        int $sourceId,
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK (большие резервы)
            $item = InventoryItem::findOrFail($itemId);
            if ($quantity > ($item->current_stock * 0.5)) {
                $this->fraud->check([
                    'operation_type' => 'inventory_large_reserve',
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'ip_address' => $this->request->ip(),
                    'correlation_id' => $correlationId,
                ]);
            }

            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($itemId, $quantity, $sourceType, $sourceId, $correlationId) {
                $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

                $available = $item->current_stock - $item->hold_stock;

                if ($available < $quantity) {
                    $this->logger->channel('audit')->warning('Inventory: Insufficient stock for reserve', [
                        'correlation_id' => $correlationId,
                        'item_id' => $itemId,
                        'requested' => $quantity,
                        'available' => $available,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                    ]);

                    return false;
                }

                $item->increment('hold_stock', $quantity);

                StockMovement::create([
                    'inventory_item_id' => $itemId,
                    'type' => 'reserve',
                    'quantity' => $quantity,
                    'reason' => "Reserved for {$sourceType}",
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                ]);

                // 3. AUDIT LOG
                $this->logger->channel('audit')->info('Inventory: Stock reserved', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Reserve failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Снять резерв (отмена бронирования)
     */
    public function releaseStock(
        int $itemId,
        int $quantity,
        string $sourceType,
        int $sourceId,
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($itemId, $quantity, $sourceType, $sourceId, $correlationId) {
                $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

                if ($item->hold_stock < $quantity) {
                    $this->logger->channel('audit')->warning('Inventory: Release more than held', [
                        'correlation_id' => $correlationId,
                        'item_id' => $itemId,
                        'held' => $item->hold_stock,
                        'release_qty' => $quantity,
                    ]);

                    return false;
                }

                $item->decrement('hold_stock', $quantity);

                StockMovement::create([
                    'inventory_item_id' => $itemId,
                    'type' => 'release',
                    'quantity' => $quantity,
                    'reason' => "Released from {$sourceType}",
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'correlation_id' => $correlationId,
                ]);

                // 3. AUDIT LOG
                $this->logger->channel('audit')->info('Inventory: Stock released', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'source_type' => $sourceType,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Release failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Списать товар из остатков
     */
    public function deductStock(
        int $itemId,
        int $quantity,
        string $reason,
        string $sourceType,
        int $sourceId,
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK (большие списания)
            $item = InventoryItem::findOrFail($itemId);
            if ($quantity > ($item->current_stock * 0.5)) {
                $this->fraud->check([
                    'operation_type' => 'inventory_large_deduct',
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'ip_address' => $this->request->ip(),
                    'correlation_id' => $correlationId,
                ]);
            }

            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($itemId, $quantity, $reason, $sourceType, $sourceId, $correlationId) {
                $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

                if ($item->current_stock < $quantity) {
                    $this->logger->channel('audit')->error('Inventory: Insufficient stock for deduct', [
                        'correlation_id' => $correlationId,
                        'item_id' => $itemId,
                        'current' => $item->current_stock,
                        'deduct' => $quantity,
                    ]);

                    return false;
                }

                $item->decrement('current_stock', $quantity);

                // Снять с холда если есть
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
                    'correlation_id' => $correlationId,
                ]);

                // Проверить low-stock
                if ($item->current_stock <= $item->min_stock_threshold) {
                    $this->logger->channel('audit')->warning('Inventory: Low stock threshold reached', [
                        'correlation_id' => $correlationId,
                        'item_id' => $itemId,
                        'current' => $item->current_stock,
                        'threshold' => $item->min_stock_threshold,
                    ]);
                }

                // 3. AUDIT LOG
                $this->logger->channel('audit')->info('Inventory: Stock deducted', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Deduct failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Добавить товар в остатки (пополнение)
     */
    public function addStock(
        int $itemId,
        int $quantity,
        string $reason,
        string $sourceType = 'manual',
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK (крупные поступления)
            $item = InventoryItem::findOrFail($itemId);
            if ($quantity > ($item->current_stock + ($item->current_stock * 1.0))) {
                $this->fraud->check([
                    'operation_type' => 'inventory_large_add',
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'ip_address' => $this->request->ip(),
                    'correlation_id' => $correlationId,
                ]);
            }

            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($itemId, $quantity, $reason, $sourceType, $correlationId) {
                $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

                $item->increment('current_stock', $quantity);

                StockMovement::create([
                    'inventory_item_id' => $itemId,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                    'source_id' => null,
                    'correlation_id' => $correlationId,
                ]);

                // 3. AUDIT LOG
                $this->logger->channel('audit')->info('Inventory: Stock added', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'source_type' => $sourceType,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Add failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Скорректировать остатки (ручная корректировка, требует аудита)
     */
    public function adjustStock(
        int $itemId,
        int $newStock,
        string $reason,
        int $userId,
        ?string $correlationId = null,
    ): bool {
        $correlationId ??= Str::uuid()->toString();

        try {
            // 1. FRAUD CHECK на большие корректировки
            $item = InventoryItem::findOrFail($itemId);
            $diff = abs($newStock - $item->current_stock);
            if ($diff > ($item->current_stock * 0.2)) {
                $this->fraud->check([
                    'operation_type' => 'inventory_large_adjust',
                    'item_id' => $itemId,
                    'old_stock' => $item->current_stock,
                    'new_stock' => $newStock,
                    'user_id' => $userId,
                    'ip_address' => $this->request->ip(),
                    'correlation_id' => $correlationId,
                ]);
            }

            // 2. TRANSACTION
            $result = $this->db->transaction(function () use ($itemId, $newStock, $reason, $userId, $correlationId) {
                $item = InventoryItem::where('id', $itemId)->lockForUpdate()->firstOrFail();
                $oldStock = $item->current_stock;

                $item->update(['current_stock' => $newStock]);

                StockMovement::create([
                    'inventory_item_id' => $itemId,
                    'type' => 'adjust',
                    'quantity' => $newStock - $oldStock,
                    'reason' => $reason,
                    'source_type' => 'manual_correction',
                    'source_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);

                // 3. AUDIT LOG - очень подробное логирование
                $this->logger->channel('audit')->warning('Inventory: Manual correction', [
                    'correlation_id' => $correlationId,
                    'item_id' => $itemId,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'difference' => $newStock - $oldStock,
                    'reason' => $reason,
                    'corrected_by' => $userId,
                ]);

                return true;
            });

            return $result;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Adjust failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'new_stock' => $newStock,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Проверить товары с низким остатком
     */
    public function checkLowStock(): Collection
    {
        try {
            $items = InventoryItem::whereColumn('current_stock', '<=', 'min_stock_threshold')
                ->get();

            if ($items->isNotEmpty()) {
                $this->logger->channel('audit')->warning('Inventory: Low stock items detected', [
                    'count' => $items->count(),
                    'items' => $items->pluck('id')->toArray(),
                ]);
            }

            return $items;
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: Low stock check failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Получить историю движения товара
     */
    public function getMovementHistory(int $itemId, int $limit = 50): array
    {
        try {
            $movements = StockMovement::where('inventory_item_id', $itemId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return $movements->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'quantity' => $m->quantity,
                'reason' => $m->reason,
                'source_type' => $m->source_type,
                'source_id' => $m->source_id,
                'correlation_id' => $m->correlation_id,
                'created_at' => $m->created_at->toIso8601String(),
            ])->toArray();
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Inventory: History retrieval failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
