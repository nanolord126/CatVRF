<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Models\InventoryItem;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InventoryService
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
    }

    public function createItem(array $data): InventoryItem
    {
        try {
            return DB::transaction(function () use ($data) {
                $item = InventoryItem::create([...$data, 'tenant_id' => tenant()->id]);
                AuditLog::create([
                    'entity_type' => 'InventoryItem',
                    'entity_id' => $item->id,
                    'action' => 'create',
                    'correlation_id' => $this->correlationId,
                    'user_id' => auth()->id(),
                ]);
                return $item;
            });
        } catch (Throwable $e) {
            Log::error('InventoryService.createItem failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateStock(InventoryItem $item, int $quantity): InventoryItem
    {
        return DB::transaction(function () use ($item, $quantity) {
            $item->update(['stock' => $quantity]);
            return $item;
        });
    }

    public function adjustStock(InventoryItem $item, int $delta, string $reason): InventoryItem
    {
        return DB::transaction(function () use ($item, $delta, $reason) {
            $item->update(['stock' => $item->stock + $delta]);
            AuditLog::create([
                'entity_type' => 'InventoryItem',
                'entity_id' => $item->id,
                'action' => 'adjust_stock',
                'changes' => ['delta' => $delta, 'reason' => $reason],
            ]);
            return $item;
        });
    }
}
