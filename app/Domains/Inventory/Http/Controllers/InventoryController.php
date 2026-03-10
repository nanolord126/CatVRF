<?php

namespace App\Domains\Inventory\Http\Controllers;

use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Services\InventoryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InventoryItem::class);
        return response()->json(
            InventoryItem::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', InventoryItem::class);
        return response()->json($this->service->createItem($request->all()), 201);
    }

    public function show(InventoryItem $item): JsonResponse
    {
        $this->authorize('view', $item);
        return response()->json($item);
    }

    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        $this->authorize('update', $item);
        return response()->json($this->service->updateStock($item, $request->input('stock')));
    }

    public function destroy(InventoryItem $item): JsonResponse
    {
        $this->authorize('delete', $item);
        $item->delete();
        return response()->json(['message' => 'Item deleted']);
    }
}
