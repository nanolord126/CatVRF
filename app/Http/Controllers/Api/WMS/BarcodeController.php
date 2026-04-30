<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\WMS;

use App\Broadcasting\BarcodeScanUpdated;
use App\Services\Inventory\BarcodeScanningService;
use App\Services\Inventory\InventoryDomainService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class BarcodeController
{
    use WithAuditLogging;

    public function __construct(
        private BarcodeScanningService $service,
        private InventoryDomainService $domainService,
        private AuditService $auditService,
    ) {}

    public function lookup(string $barcode, Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id', Auth::user()?->warehouse_id ?? 1);

        $result = $this->service->lookupByBarcode($barcode, $warehouseId);

        return response()->json($result);
    }

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
        ]);

        $result = $this->service->validateBarcode($validated['barcode']);

        return response()->json($result);
    }

    public function validateChestnyZnak(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marking_code' => 'required|string',
        ]);

        $result = $this->service->validateChestnyZnakCode($validated['marking_code']);

        return response()->json($result);
    }

    public function lookupBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'warehouse_id' => 'required|integer',
        ]);

        $result = $this->service->lookupBatchByCode($validated['code'], $validated['warehouse_id']);

        return response()->json($result);
    }

    public function bulkLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcodes' => 'required|array',
            'barcodes.*' => 'required|string',
            'warehouse_id' => 'required|integer',
        ]);

        $results = $this->service->bulkLookup($validated['barcodes'], $validated['warehouse_id']);

        return response()->json($results);
    }

    /**
     * Process barcode scan for stock adjustment
     * Integrates barcode scanning with stock movements
     */
    public function scanAndAdjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'adjustment_type' => 'required|in:in,out,adjust',
            'reason' => 'required|string|max:255',
        ]);

        $warehouseId = $validated['warehouse_id'];
        $barcode = $validated['barcode'];
        $quantity = $validated['quantity'];
        $adjustmentType = $validated['adjustment_type'];
        $reason = $validated['reason'];
        $userId = Auth::id();
        $tenantId = Auth::user()->tenant_id;

        return DB::transaction(function () use (
            $barcode,
            $warehouseId,
            $quantity,
            $adjustmentType,
            $reason,
            $userId,
            $tenantId
        ) {
            $lookup = $this->service->lookupByBarcode($barcode, $warehouseId);

            if (!$lookup['found']) {
                return response()->json([
                    'success' => false,
                    'barcode' => $barcode,
                    'message' => 'Item not found for barcode',
                ], 404);
            }

            $itemId = $lookup['item_id'];
            $currentStock = $lookup['current_stock'];

            if ($adjustmentType === 'out' && $currentStock < $quantity) {
                return response()->json([
                    'success' => false,
                    'barcode' => $barcode,
                    'message' => 'Insufficient stock',
                    'available' => $currentStock,
                    'requested' => $quantity,
                ], 400);
            }

            $newStock = match ($adjustmentType) {
                'in' => $currentStock + $quantity,
                'out' => $currentStock - $quantity,
                'adjust' => $quantity,
            };

            DB::table('inventory_items')
                ->where('id', $itemId)
                ->update([
                    'current_stock' => $newStock,
                    'updated_at' => now(),
                ]);

            $movementQuantity = match ($adjustmentType) {
                'in' => $quantity,
                'out' => -$quantity,
                'adjust' => $quantity - $currentStock,
            };

            $movementId = DB::table('stock_movements')->insertGetId([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'correlation_id' => \Illuminate\Support\Str::uuid(),
                'inventory_item_id' => $itemId,
                'type' => $adjustmentType === 'adjust' ? 'adjustment' : $adjustmentType,
                'quantity' => $movementQuantity,
                'reason' => $reason,
                'source_type' => 'barcode_scanner',
                'source_id' => null,
                'created_by' => $userId,
                'performed_by' => (string) $userId,
                'created_at' => now(),
            ]);

            $this->logAction(
                action: 'barcode_scan_adjustment',
                entityType: 'StockMovement',
                entityId: $movementId,
                context: [
                    'barcode' => $barcode,
                    'adjustment_type' => $adjustmentType,
                    'quantity' => $quantity,
                    'previous_stock' => $currentStock,
                    'new_stock' => $newStock,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            broadcast(new BarcodeScanUpdated(
                tenantId: $tenantId,
                warehouseId: $warehouseId,
                barcode: $barcode,
                itemId: $itemId,
                itemName: $lookup['name'],
                sku: $lookup['sku'],
                previousStock: $currentStock,
                newStock: $newStock,
                adjustmentType: $adjustmentType,
                quantity: $quantity,
                movementId: $movementId,
                userId: $userId,
                userName: Auth::user()->name,
                correlationId: \Illuminate\Support\Str::uuid(),
            ));

            return response()->json([
                'success' => true,
                'movement_id' => $movementId,
                'item_id' => $itemId,
                'barcode' => $barcode,
                'adjustment_type' => $adjustmentType,
                'quantity' => $quantity,
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
            ], 201);
        });
    }

    /**
     * Bulk process barcode scans for stock adjustments
     */
    public function bulkScanAndAdjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scans' => 'required|array',
            'scans.*.barcode' => 'required|string',
            'scans.*.warehouse_id' => 'required|integer',
            'scans.*.quantity' => 'required|integer|min:1',
            'scans.*.adjustment_type' => 'required|in:in,out,adjust',
            'scans.*.reason' => 'required|string|max:255',
        ]);

        $results = [];
        $userId = Auth::id();
        $tenantId = Auth::user()->tenant_id;

        foreach ($validated['scans'] as $scan) {
            try {
                $result = DB::transaction(function () use ($scan, $userId, $tenantId) {
                    $lookup = $this->service->lookupByBarcode($scan['barcode'], $scan['warehouse_id']);

                    if (!$lookup['found']) {
                        return [
                            'success' => false,
                            'barcode' => $scan['barcode'],
                            'message' => 'Item not found',
                        ];
                    }

                    $itemId = $lookup['item_id'];
                    $currentStock = $lookup['current_stock'];

                    if ($scan['adjustment_type'] === 'out' && $currentStock < $scan['quantity']) {
                        return [
                            'success' => false,
                            'barcode' => $scan['barcode'],
                            'message' => 'Insufficient stock',
                            'available' => $currentStock,
                            'requested' => $scan['quantity'],
                        ];
                    }

                    $newStock = match ($scan['adjustment_type']) {
                        'in' => $currentStock + $scan['quantity'],
                        'out' => $currentStock - $scan['quantity'],
                        'adjust' => $scan['quantity'],
                    };

                    DB::table('inventory_items')
                        ->where('id', $itemId)
                        ->update([
                            'current_stock' => $newStock,
                            'updated_at' => now(),
                        ]);

                    $movementQuantity = match ($scan['adjustment_type']) {
                        'in' => $scan['quantity'],
                        'out' => -$scan['quantity'],
                        'adjust' => $scan['quantity'] - $currentStock,
                    };

                    $movementId = DB::table('stock_movements')->insertGetId([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'correlation_id' => \Illuminate\Support\Str::uuid(),
                        'inventory_item_id' => $itemId,
                        'type' => $scan['adjustment_type'] === 'adjust' ? 'adjustment' : $scan['adjustment_type'],
                        'quantity' => $movementQuantity,
                        'reason' => $scan['reason'],
                        'source_type' => 'barcode_scanner_bulk',
                        'source_id' => null,
                        'created_by' => $userId,
                        'performed_by' => (string) $userId,
                        'created_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'movement_id' => $movementId,
                        'item_id' => $itemId,
                        'barcode' => $scan['barcode'],
                        'new_stock' => $newStock,
                    ];
                });

                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'barcode' => $scan['barcode'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->logAction(
            action: 'bulk_barcode_scan_adjustment',
            entityType: 'StockMovement',
            entityId: null,
            context: [
                'total_scans' => count($validated['scans']),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
                'failed' => count(array_filter($results, fn($r) => !$r['success'])),
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'failed' => count(array_filter($results, fn($r) => !$r['success'])),
        ], 201);
    }

    /**
     * Get scanner session history
     */
    public function getScannerHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'sometimes|integer',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date',
        ]);

        $query = DB::table('stock_movements')
            ->where('source_type', 'barcode_scanner')
            ->orWhere('source_type', 'barcode_scanner_bulk');

        if ($request->has('warehouse_id')) {
            $query->whereHas('inventoryItem', function ($q) use ($validated) {
                $q->where('warehouse_id', $validated['warehouse_id']);
            });
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $validated['from_date']);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $validated['to_date']);
        }

        $movements = $query->with('inventoryItem')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($movements);
    }

    /**
     * Validate and prepare for scan (pre-flight check)
     */
    public function preFlightScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'warehouse_id' => 'required|integer',
            'operation' => 'required|in:in,out,adjust',
            'quantity' => 'required|integer|min:1',
        ]);

        $validation = $this->service->validateBarcode($validated['barcode']);

        if (!$validation['valid']) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid barcode format',
                'validation' => $validation,
            ], 400);
        }

        $lookup = $this->service->lookupByBarcode($validated['barcode'], $validated['warehouse_id']);

        if (!$lookup['found']) {
            return response()->json([
                'valid' => false,
                'message' => 'Item not found',
            ], 404);
        }

        if ($validated['operation'] === 'out' && $lookup['current_stock'] < $validated['quantity']) {
            return response()->json([
                'valid' => false,
                'message' => 'Insufficient stock',
                'available' => $lookup['current_stock'],
                'requested' => $validated['quantity'],
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'item' => $lookup,
            'operation' => $validated['operation'],
            'quantity' => $validated['quantity'],
            'can_proceed' => true,
        ]);
    }
}
