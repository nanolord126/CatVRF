<?php

declare(strict_types=1);

namespace App\Domains\Jewelry\Http\Controllers;

use App\Domains\Jewelry\Models\JewelryItem;
use App\Domains\Jewelry\Models\JewelryOrder;
use App\Domains\Jewelry\Services\JewelryService;
use App\Domains\Jewelry\Services\CertificateService;
use App\Domains\Jewelry\Services\Jewelry3DService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Ювелирные изделия — КАНОН 2026.
 */
final class JewelryController
{
    public function __construct(
        private readonly JewelryService $jewelryService,
        private readonly CertificateService $certificateService,
        private readonly Jewelry3DService $jewelry3DService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $items = JewelryItem::where('tenant_id', $tenantId)
                ->when($request->input('metal'),    fn ($q, $v) => $q->where('metal', $v))
                ->when($request->input('gem'),      fn ($q, $v) => $q->where('gem', $v))
                ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
                ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))
                ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $items, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Jewelry: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $item = JewelryItem::findOrFail($id);
            return response()->json(['success' => true, 'data' => $item, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Изделие не найдено', 'correlation_id' => $correlationId], 404);
        }
    }

    public function view3D(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $item    = JewelryItem::findOrFail($id);
            $model3d = $this->jewelry3DService->getModel($item, $correlationId);
            return response()->json(['success' => true, 'data' => $model3d, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Jewelry: 3D view error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка 3D-просмотра', 'correlation_id' => $correlationId], 500);
        }
    }

    public function certificate(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $item = JewelryItem::findOrFail($id);
            $cert = $this->certificateService->get($item, $correlationId);
            return response()->json(['success' => true, 'data' => $cert, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Сертификат не найден', 'correlation_id' => $correlationId], 404);
        }
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $userId = auth()->id();

            $fraudResult = $this->fraudControlService->check(
                userId: $userId,
                operationType: 'jewelry_order',
                amount: (int) $request->input('price_kopecks', 0),
                correlationId: $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
            }

            $validated = $request->validate([
                'item_id'          => 'required|integer|exists:jewelry_items,id',
                'size'             => 'nullable|string',
                'engraving'        => 'nullable|string|max:100',
                'gift_wrapping'    => 'boolean',
                'delivery_address' => 'required|string',
            ]);

            $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): JewelryOrder {
                $item  = JewelryItem::findOrFail($validated['item_id']);
                $order = JewelryOrder::create([
                    'uuid'             => Str::uuid(),
                    'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                    'client_id'        => $userId,
                    'item_id'          => $validated['item_id'],
                    'size'             => $validated['size'] ?? null,
                    'engraving'        => $validated['engraving'] ?? null,
                    'gift_wrapping'    => $validated['gift_wrapping'] ?? false,
                    'delivery_address' => $validated['delivery_address'],
                    'price'            => $item->price,
                    'status'           => 'pending',
                    'correlation_id'   => $correlationId,
                ]);

                $this->log->channel('audit')->info('Jewelry: Order created', [
                    'order_id'       => $order->id,
                    'item_id'        => $validated['item_id'],
                    'user_id'        => $userId,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Jewelry: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
        }
    }

    public function myOrders(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $orders = JewelryOrder::where('client_id', auth()->id())
                ->with('item')
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
        }
    }
}
