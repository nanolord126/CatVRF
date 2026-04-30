<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Inventory\BarcodeScanningService;
use App\Services\Inventory\BatchTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Barcode Scanner Controller
 *
 * Handles barcode scanner operations for warehouse:
 * - Stock in (add items)
 * - Stock out (remove items)
 * - Cycle counting
 * - Batch tracking
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class BarcodeScannerController extends Controller
{
    public function __construct(
        private readonly BarcodeScanningService $barcodeService,
        private readonly BatchTrackingService $batchService
    ) {}

    /**
     * Process barcode scan for stock operation
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'warehouse_id' => 'required|integer',
            'operation' => 'required|in:in,out,adjust,count',
            'quantity' => 'required|integer|min:1',
            'user_id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        $result = $this->barcodeService->processScannedBarcode(
            $validated['barcode'],
            $validated['warehouse_id'],
            $validated['operation'],
            $validated['quantity']
        );

        if ($result['success']) {
            $this->applyStockOperation(
                $result['item']['item_id'],
                $validated['operation'],
                $validated['quantity'],
                $validated['reason'] ?? 'Scanner operation',
                $validated['user_id']
            );
        }

        return response()->json($result);
    }

    /**
     * Bulk barcode scan
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function bulkScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcodes' => 'required|array',
            'barcodes.*' => 'required|string',
            'warehouse_id' => 'required|integer',
            'operation' => 'required|in:in,out',
            'user_id' => 'required|integer',
        ]);

        $results = $this->barcodeService->bulkLookup(
            $validated['barcodes'],
            $validated['warehouse_id']
        );

        $processed = [];
        foreach ($results as $barcode => $result) {
            if ($result['found']) {
                $this->applyStockOperation(
                    $result['item_id'],
                    $validated['operation'],
                    1,
                    'Bulk scanner operation',
                    $validated['user_id']
                );
            }
            $processed[$barcode] = $result;
        }

        return response()->json([
            'total_scanned' => count($validated['barcodes']),
            'successful' => count(array_filter($processed, fn($r) => $r['found'])),
            'failed' => count(array_filter($processed, fn($r) => !$r['found'])),
            'results' => $processed,
        ]);
    }

    /**
     * Validate barcode
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
        ]);

        $result = $this->barcodeService->validateBarcode($validated['barcode']);

        return response()->json($result);
    }

    /**
     * Get item by barcode
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'warehouse_id' => 'required|integer',
        ]);

        $result = $this->barcodeService->lookupByBarcode(
            $validated['barcode'],
            $validated['warehouse_id']
        );

        return response()->json($result);
    }

    /**
     * Process cycle count scan
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function cycleCountScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'count_item_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
            'user_id' => 'required|integer',
        ]);

        $result = $this->barcodeService->processCountScan(
            $validated['barcode'],
            $validated['count_item_id'],
            $validated['quantity'],
            $validated['user_id']
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Process picking scan
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function pickingScan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'pick_task_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'user_id' => 'required|integer',
        ]);

        $result = $this->barcodeService->processPickingScan(
            $validated['barcode'],
            $validated['pick_task_id'],
            $validated['quantity'],
            $validated['user_id']
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Associate barcode with batch
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function associateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
            'batch_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);

        $result = $this->barcodeService->associateBarcodeWithBatch(
            $validated['barcode'],
            $validated['batch_id'],
            $validated['user_id']
        );

        return response()->json([
            'success' => $result,
            'message' => 'Barcode associated with batch',
        ]);
    }

    /**
     * Get batch by barcode
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getBatchByBarcode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => 'required|string',
        ]);

        $result = $this->barcodeService->getBatchByBarcode($validated['barcode']);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found for this barcode',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'batch' => $result,
        ]);
    }

    /**
     * Apply stock operation to inventory
     *
     * @param  int  $inventoryItemId
     * @param  string  $operation
     * @param  int  $quantity
     * @param  string  $reason
     * @param  int  $userId
     * @return void
     */
    private function applyStockOperation(
        int $inventoryItemId,
        string $operation,
        int $quantity,
        string $reason,
        int $userId
    ): void {
        $quantityChange = match ($operation) {
            'in' => $quantity,
            'out' => -$quantity,
            'adjust' => $quantity,
            'count' => $quantity,
            default => $quantity,
        };

        app('db')->table('inventory_items')
            ->where('id', $inventoryItemId)
            ->increment('current_stock', $quantityChange);

        app('db')->table('stock_movements')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'inventory_item_id' => $inventoryItemId,
            'type' => $operation === 'count' ? 'adjust' : $operation,
            'quantity' => $quantityChange,
            'reason' => $reason,
            'source_type' => 'barcode_scanner',
            'correlation_id' => \Illuminate\Support\Str::uuid(),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
