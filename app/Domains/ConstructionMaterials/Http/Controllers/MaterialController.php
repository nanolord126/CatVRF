<?php

declare(strict_types=1);

namespace App\Domains\ConstructionMaterials\Http\Controllers;

use App\Domains\ConstructionMaterials\Models\ConstructionMaterial;
use App\Domains\ConstructionMaterials\Models\MaterialOrder;
use App\Domains\ConstructionMaterials\Services\ConstructionMaterialService;
use App\Domains\ConstructionMaterials\Services\MaterialCalculatorService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Строительные материалы — КАНОН 2026.
 */
final class MaterialController
{
    public function __construct(
        private readonly ConstructionMaterialService $materialService,
        private readonly MaterialCalculatorService $calculatorService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $materials = ConstructionMaterial::where('tenant_id', $tenantId)
                ->when($request->input('category'),  fn ($q, $v) => $q->where('category', $v))
                ->when($request->input('supplier'),  fn ($q, $v) => $q->where('supplier_id', $v))
                ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))
                ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $materials, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ConstructionMaterials: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $material = ConstructionMaterial::findOrFail($id);
            return response()->json(['success' => true, 'data' => $material, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Материал не найден', 'correlation_id' => $correlationId], 404);
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $validated = $request->validate([
                'material_id' => 'required|integer|exists:construction_materials,id',
                'area'        => 'required|numeric|min:0.1',
                'unit'        => 'required|string|in:sq_m,lin_m,cubic_m',
            ]);

            $result = $this->calculatorService->calculate(
                materialId: $validated['material_id'],
                area:       (float) $validated['area'],
                unit:       $validated['unit'],
                correlationId: $correlationId,
            );

            return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка расчёта', 'correlation_id' => $correlationId], 500);
        }
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $userId = auth()->id();

            $fraudResult = $this->fraudControlService->check(
                userId: $userId,
                operationType: 'material_order',
                amount: (int) $request->input('total_kopecks', 0),
                correlationId: $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
            }

            $validated = $request->validate([
                'material_id'      => 'required|integer|exists:construction_materials,id',
                'quantity'         => 'required|numeric|min:0.1',
                'delivery_address' => 'required|string',
                'delivery_date'    => 'required|date|after:today',
                'comment'          => 'nullable|string|max:500',
            ]);

            $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): MaterialOrder {
                $material = ConstructionMaterial::findOrFail($validated['material_id']);
                $order    = MaterialOrder::create([
                    'uuid'             => Str::uuid(),
                    'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                    'client_id'        => $userId,
                    'material_id'      => $validated['material_id'],
                    'quantity'         => $validated['quantity'],
                    'delivery_address' => $validated['delivery_address'],
                    'delivery_date'    => $validated['delivery_date'],
                    'comment'          => $validated['comment'] ?? null,
                    'total_kopecks'    => (int) ($material->price * $validated['quantity']),
                    'status'           => 'pending',
                    'correlation_id'   => $correlationId,
                ]);

                $this->log->channel('audit')->info('ConstructionMaterials: Order created', [
                    'order_id'       => $order->id,
                    'material_id'    => $validated['material_id'],
                    'user_id'        => $userId,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ConstructionMaterials: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
        }
    }

    public function myOrders(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $orders = MaterialOrder::where('client_id', auth()->id())
                ->with('material')
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
        }
    }
}
