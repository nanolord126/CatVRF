<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\WMS;

use App\Models\InventoryItem;
use App\Services\Cache\WMSCacheService;
use App\Services\Inventory\InventoryDomainService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class InventoryController
{
    use AuthorizesRequests;

    public function __construct(
        private InventoryDomainService $domainService,
        private WMSCacheService $cacheService,
    ) {}

    public function getStats(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $export = $request->boolean('export');

        $cacheKey = "inventory_stats:{$tenantId}";
        
        if (! $export) {
            $stats = $this->cacheService->getInventoryItem($tenantId, function () use ($tenantId) {
                return [
                    'total_items' => InventoryItem::where('tenant_id', $tenantId)->count(),
                    'total_stock' => InventoryItem::where('tenant_id', $tenantId)->sum('current_stock'),
                    'low_stock_count' => InventoryItem::where('tenant_id', $tenantId)
                        ->whereColumn('current_stock', '<', 'reorder_point')
                        ->count(),
                    'expiring_count' => InventoryItem::where('tenant_id', $tenantId)
                        ->whereHas('batches', fn ($q) => $q->where('expiry_date', '<=', now()->addDays(30)))
                        ->count(),
                    'itemsChange' => 5,
                    'stockChange' => 12,
                    'alertsChange' => -2,
                    'expiringChange' => 3,
                    'itemsTrend' => 'up',
                    'stockTrend' => 'up',
                    'alertsTrend' => 'down',
                    'expiringTrend' => 'up',
                ];
            });
        } else {
            $stats = [
                'total_items' => InventoryItem::where('tenant_id', $tenantId)->count(),
                'total_stock' => InventoryItem::where('tenant_id', $tenantId)->sum('current_stock'),
                'generated_at' => now()->toIso8601String(),
            ];
        }

        return response()->json($stats);
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%");
        }

        $items = $query->with('batches')->paginate($request->per_page ?? 20);

        return response()->json($items);
    }

    public function show(int $id): JsonResponse
    {
        $item = InventoryItem::with(['batches', 'warehouse'])->findOrFail($id);

        $this->authorize('view', $item);

        return response()->json($item);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'sku' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'warehouse_id' => 'required|integer',
            'current_stock' => 'integer|min:0',
            'unit_cost' => 'numeric|min:0',
            'reorder_point' => 'integer|min:0',
            'safety_stock' => 'integer|min:0',
        ]);

        $item = InventoryItem::create([
            ...$validated,
            'tenant_id' => Auth::user()->tenant_id,
        ]);

        $this->cacheService->invalidateInventoryItem($item->id);

        return response()->json($item, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $this->authorize('update', $item);

        $validated = $request->validate([
            'sku' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'current_stock' => 'sometimes|integer|min:0',
            'unit_cost' => 'sometimes|numeric|min:0',
            'reorder_point' => 'sometimes|integer|min:0',
            'safety_stock' => 'sometimes|integer|min:0',
        ]);

        $item->update($validated);
        $this->cacheService->invalidateInventoryItem($item->id);

        return response()->json($item);
    }

    public function delete(int $id): JsonResponse
    {
        $item = InventoryItem::findOrFail($id);
        $this->authorize('delete', $item);

        $item->delete();
        $this->cacheService->invalidateInventoryItem($item->id);

        return response()->json(null, 204);
    }

    public function getLowStock(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $items = InventoryItem::where('tenant_id', $tenantId)
            ->whereColumn('current_stock', '<', 'reorder_point')
            ->with('warehouse')
            ->get();

        return response()->json($items);
    }

    public function getReorderRecommendations(int $tenantId): JsonResponse
    {
        $recommendations = $this->domainService->calculateReorderPoint(1, 30);

        return response()->json([
            'items' => $recommendations,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function getWarehouses(): JsonResponse
    {
        $warehouses = \Modules\Warehouse\Domain\Entities\Warehouse::all();

        return response()->json($warehouses);
    }

    public function getWarehouse(int $id): JsonResponse
    {
        $warehouse = \Modules\Warehouse\Domain\Entities\Warehouse::findOrFail($id);

        return response()->json($warehouse);
    }

    public function getWarehouseConfig(int $id): JsonResponse
    {
        $config = $this->cacheService->getWarehouseConfig($id, function () use ($id) {
            return \Modules\Warehouse\Domain\Entities\Warehouse::findOrFail($id)->config ?? [];
        });

        return response()->json($config);
    }
}
