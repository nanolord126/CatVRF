<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\AutoPart;
use App\Domains\Auto\Services\AutoPartsInventoryService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления запчастями (только для персонала).
 * Production 2026.
 */
final class AutoPartController
{
    public function __construct(
        private readonly AutoPartsInventoryService $inventoryService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $parts = AutoPart::query()
                ->where('tenant_id', tenant('id'))
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $parts,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении запчастей',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'auto_part_creation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $this->authorize('create', AutoPart::class);

            $request->validate([
                'sku' => 'required|unique:auto_parts',
                'name' => 'required|string',
                'brand' => 'required|string',
                'price' => 'required|integer',
                'current_stock' => 'required|integer',
                'min_stock_threshold' => 'required|integer',
            ]);

            $validated = $request->all();
            $part = DB::transaction(function () use ($validated) {
                return AutoPart::create([
                    'tenant_id' => tenant('id'),
                    'sku' => ($validated['sku'] ?? null),
                    'name' => ($validated['name'] ?? null),
                    'brand' => ($validated['brand'] ?? null),
                    'price' => ($validated['price'] ?? null),
                    'current_stock' => ($validated['current_stock'] ?? null),
                    'min_stock_threshold' => ($validated['min_stock_threshold'] ?? null),
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $part,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании запчасти',
            ], 500);
        }
    }

    public function show(AutoPart $part): JsonResponse
    {
        $this->authorize('view', $part);

        return response()->json([
            'success' => true,
            'data' => $part,
        ]);
    }

    public function update(Request $request, AutoPart $part): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'auto_part_update',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $this->authorize('update', $part);

            $request->validate([
                'price' => 'nullable|integer',
                'min_stock_threshold' => 'nullable|integer',
            ]);

            $part->update($request->only(['price', 'min_stock_threshold']));

            return response()->json([
                'success' => true,
                'data' => $part,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении запчасти',
            ], 500);
        }
    }

    public function restock(Request $request, AutoPart $part): JsonResponse
    {
        try {
            $this->authorize('update', $part);

            $request->validate([
                'quantity' => 'required|integer|min:1',
                'reason' => 'nullable|string',
            ]);

            $this->inventoryService->addStock(
                $part->id,
                $request->get('quantity'),
                $request->get('reason', 'Пополнение остатка'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Остаток пополнен',
                'data' => $part->fresh(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при пополнении остатка',
            ], 500);
        }
    }

    public function lowStock(Request $request): JsonResponse
    {
        $parts = AutoPart::query()
            ->where('tenant_id', tenant('id'))
            ->whereRaw('current_stock < min_stock_threshold')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parts,
            'count' => $parts->count(),
        ]);
    }

    public function delete(AutoPart $part): JsonResponse
    {
        try {
            $this->authorize('delete', $part);

            $part->delete();

            return response()->json([
                'success' => true,
                'message' => 'Запчасть удалена',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении запчасти',
            ], 500);
        }
    }
}
