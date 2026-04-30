<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\WMS;

use App\Domains\Inventory\Models\InventoryBatch;
use App\Services\Inventory\BatchTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class BatchController
{
    use AuthorizesRequests;

    public function __construct(
        private BatchTrackingService $batchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryBatch::where('tenant_id', $tenantId);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $batches = $query->with('inventoryItem')->orderBy('expiry_date')->paginate($request->per_page ?? 20);

        return response()->json($batches);
    }

    public function show(int $id): JsonResponse
    {
        $batch = InventoryBatch::with(['inventoryItem', 'warehouse'])->findOrFail($id);

        $this->authorize('view', $batch);

        return response()->json($batch);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'batch_number' => 'required|string|max:255',
            'expiry_date' => 'required|date|after:today',
            'manufacture_date' => 'required|date',
            'quantity' => 'required|integer|min:0',
            'warehouse_id' => 'required|integer',
            'serial_number' => 'sometimes|string|max:255',
        ]);

        $batch = InventoryBatch::create([
            ...$validated,
            'tenant_id' => Auth::user()->tenant_id,
            'current_quantity' => $validated['quantity'],
            'status' => 'available',
        ]);

        return response()->json($batch, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:0',
            'current_quantity' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:available,quarantine,hold,expired,recalled',
        ]);

        $batch->update($validated);

        return response()->json($batch);
    }

    public function delete(int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('delete', $batch);

        $batch->delete();

        return response()->json(null, 204);
    }

    public function selectFEFO(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $batches = $this->batchService->selectBatchesFEFO(
            $validated['product_id'],
            $validated['warehouse_id'],
            $validated['quantity']
        );

        return response()->json($batches);
    }

    public function placeOnQuarantine(Request $request, int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('update', $batch);

        $result = $this->batchService->placeOnQuarantine($id, $request->reason ?? 'Manual quarantine');

        return response()->json($result);
    }

    public function releaseFromQuarantine(int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('update', $batch);

        $result = $this->batchService->releaseFromQuarantine($id, Auth::id());

        return response()->json($result);
    }

    public function placeOnHold(Request $request, int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('update', $batch);

        $result = $this->batchService->placeOnHold($id, $request->reason ?? 'Manual hold');

        return response()->json($result);
    }

    public function createRecall(Request $request, int $id): JsonResponse
    {
        $batch = InventoryBatch::findOrFail($id);
        $this->authorize('createRecall', $batch);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'recall_type' => 'required|string',
        ]);

        $result = $this->batchService->createRecall($id, $validated['reason'], $validated['recall_type']);

        return response()->json($result);
    }
}
