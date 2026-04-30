<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\WMS;

use App\Models\StockMovement;
use App\Services\Cache\WMSCacheService;
use App\Services\Inventory\BulkStockMovementService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class StockMovementController
{
    use AuthorizesRequests;
    use WithAuditLogging;

    public function __construct(
        private WMSCacheService $cacheService,
        private BulkStockMovementService $bulkService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $query = StockMovement::whereHas('inventoryItem', fn ($q) => $q->where('tenant_id', $tenantId));

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        $movements = $query->with('inventoryItem')->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($movements);
    }

    public function show(int $id): JsonResponse
    {
        $movement = StockMovement::with('inventoryItem')->findOrFail($id);

        $this->authorize('view', $movement);

        return response()->json($movement);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'type' => 'required|in:in,out,adjustment,transfer,damage',
            'quantity' => 'required|integer',
            'reason' => 'required|string|max:255',
            'source_type' => 'sometimes|string|max:255',
            'source_id' => 'sometimes|integer',
            'performed_by' => 'sometimes|string|max:255',
            'approved_by' => 'sometimes|string|max:255',
        ]);

        $item = \App\Models\InventoryItem::findOrFail($validated['inventory_item_id']);
        $this->authorize('create', StockMovement::class);

        $movement = StockMovement::create([
            ...$validated,
            'uuid' => \Illuminate\Support\Str::uuid(),
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'created_by' => Auth::id(),
        ]);

        if ($validated['type'] === 'in') {
            $item->increment('current_stock', abs($validated['quantity']));
        } elseif ($validated['type'] === 'out' || $validated['type'] === 'damage') {
            $item->decrement('current_stock', abs($validated['quantity']));
        }

        $this->cacheService->invalidateStockLevel($item->id, $item->warehouse_id);
        $this->logAction(
            action: 'stock_movement_created',
            entityType: 'StockMovement',
            context: [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
            ],
            userId: Auth::id(),
            tenantId: Auth::user()->tenant_id
        );

        return response()->json($movement, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $movement = StockMovement::findOrFail($id);
        $this->authorize('update', $movement);

        $validated = $request->validate([
            'reason' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|integer',
        ]);

        $movement->update($validated);

        return response()->json($movement);
    }

    public function delete(int $id): JsonResponse
    {
        $movement = StockMovement::findOrFail($id);
        $this->authorize('delete', $movement);

        $movement->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $movement = StockMovement::findOrFail($id);
        $this->authorize('approve', $movement);

        $movement->update([
            'approved_by' => $request->user()->name,
            'approved_at' => now(),
        ]);

        $this->logAction(
            action: 'stock_movement_approved',
            entityType: 'StockMovement',
            context: ['movement_id' => $movement->id],
            userId: Auth::id(),
            tenantId: Auth::user()->tenant_id
        );

        return response()->json($movement);
    }

    public function reverse(int $id): JsonResponse
    {
        $movement = StockMovement::findOrFail($id);
        $this->authorize('reverse', $movement);

        $item = $movement->inventoryItem;

        if ($movement->type === 'in') {
            $item->decrement('current_stock', abs($movement->quantity));
        } elseif ($movement->type === 'out') {
            $item->increment('current_stock', abs($movement->quantity));
        }

        $movement->update(['reversed_at' => now()]);
        $this->cacheService->invalidateStockLevel($item->id, $item->warehouse_id);

        return response()->json($movement);
    }

    public function bulkAdjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'adjustments.*.quantity' => 'required|integer',
            'adjustments.*.reason' => 'required|string|max:255',
        ]);

        $result = $this->bulkService->bulkAdjust(
            $validated['adjustments'],
            Auth::id(),
            Auth::user()->tenant_id
        );

        return response()->json($result, 201);
    }

    public function bulkTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transfers' => 'required|array',
            'transfers.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'transfers.*.from_warehouse_id' => 'required|integer',
            'transfers.*.to_warehouse_id' => 'required|integer',
            'transfers.*.quantity' => 'required|integer',
        ]);

        $result = $this->bulkService->bulkTransfer(
            $validated['transfers'],
            Auth::id(),
            Auth::user()->tenant_id
        );

        return response()->json($result, 201);
    }

    public function bulkReceipt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipts' => 'required|array',
            'receipts.*.product_id' => 'required|integer',
            'receipts.*.warehouse_id' => 'required|integer',
            'receipts.*.quantity' => 'required|integer',
            'receipts.*.sku' => 'required|string',
            'receipts.*.source_type' => 'sometimes|string',
            'receipts.*.source_id' => 'sometimes|integer',
        ]);

        $result = $this->bulkService->bulkReceipt(
            $validated['receipts'],
            Auth::id(),
            Auth::user()->tenant_id
        );

        return response()->json($result, 201);
    }

    public function bulkWriteOff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'writeoffs' => 'required|array',
            'writeoffs.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'writeoffs.*.quantity' => 'required|integer',
            'writeoffs.*.reason' => 'required|string|max:255',
        ]);

        $result = $this->bulkService->bulkWriteOff(
            $validated['writeoffs'],
            Auth::id(),
            Auth::user()->tenant_id
        );

        return response()->json($result, 201);
    }
}
