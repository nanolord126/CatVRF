<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Http\Controllers;

use App\Domains\Inventory\DTOs\CreateAdjustmentDto;
use App\Domains\Inventory\DTOs\CreateReservationDto;
use App\Domains\Inventory\Http\Requests\AdjustStockRequest;
use App\Domains\Inventory\Http\Requests\ReserveStockRequest;
use App\Domains\Inventory\Http\Resources\InventoryItemResource;
use App\Domains\Inventory\Http\Resources\ReservationResource;
use App\Domains\Inventory\Models\InventoryItem;
use App\Domains\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * REST API контроллер для Inventory.
 *
 * Все мутации делегируются в InventoryService.
 * Контроллер — тонкий слой маршрутизации.
 */
final class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * GET /api/inventory — список остатков.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = InventoryItem::query();

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->has('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->boolean('in_stock_only')) {
            $query->whereRaw('quantity - reserved > 0');
        }

        return InventoryItemResource::collection(
            $query->paginate((int) $request->input('per_page', 20)),
        );
    }

    /**
     * GET /api/inventory/{id} — одна позиция.
     */
    public function show(int $id): InventoryItemResource
    {
        return new InventoryItemResource(
            InventoryItem::findOrFail($id),
        );
    }

    /**
     * POST /api/inventory/reserve — резервирование.
     */
    public function reserve(ReserveStockRequest $request): JsonResponse
    {
        $dto = CreateReservationDto::fromArray(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()?->tenant_id ?? 0],
        ));

        $reservation = $this->inventoryService->reserve($dto);

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * POST /api/inventory/adjust — корректировка.
     */
    public function adjust(AdjustStockRequest $request): InventoryItemResource
    {
        $dto = CreateAdjustmentDto::fromArray(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()?->tenant_id ?? 0],
        ));

        $item = $this->inventoryService->adjust($dto);

        return new InventoryItemResource($item);
    }

    /**
     * GET /api/inventory/available/{productId} — доступное количество.
     */
    public function available(int $productId, Request $request): JsonResponse
    {
        $warehouseId = $request->has('warehouse_id')
            ? (int) $request->input('warehouse_id')
            : null;

        $available = $this->inventoryService->getAvailableStock($productId, $warehouseId);

        return new JsonResponse([
            'product_id'     => $productId,
            'warehouse_id'   => $warehouseId,
            'available'      => $available,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ]);
    }
}
