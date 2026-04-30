<?php

declare(strict_types=1);

namespace Modules\Warehouse\Interface\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Application\DTOs\AddStockDTO;
use Modules\Warehouse\Application\DTOs\RemoveStockDTO;
use Modules\Warehouse\Application\DTOs\TransferStockDTO;
use Modules\Warehouse\Application\UseCases\AddStockUseCase;
use Modules\Warehouse\Application\UseCases\RemoveStockUseCase;
use Modules\Warehouse\Application\UseCases\TransferStockUseCase;
use Modules\Warehouse\Domain\Entities\InventoryItem;

/**
 * Inventory Controller - Interface Layer
 *
 * Handles HTTP requests for inventory operations
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InventoryController
{
    public function __construct(
        private readonly AddStockUseCase $addStockUseCase,
        private readonly RemoveStockUseCase $removeStockUseCase,
        private readonly TransferStockUseCase $transferStockUseCase
    ) {}

    /**
     * Add stock to inventory
     */
    public function addStock(Request $request): JsonResponse
    {
        $dto = AddStockDTO::fromArray($request->validated());
        
        $item = $this->addStockUseCase->execute($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $item->toArray(),
        ], 201);
    }

    /**
     * Remove stock from inventory
     */
    public function removeStock(Request $request): JsonResponse
    {
        $dto = RemoveStockDTO::fromArray($request->validated());
        
        $item = $this->removeStockUseCase->execute($dto);

        return new JsonResponse([
            'success' => true,
            'data' => $item->toArray(),
        ]);
    }

    /**
     * Transfer stock between zones
     */
    public function transferStock(Request $request): JsonResponse
    {
        $dto = TransferStockDTO::fromArray($request->validated());
        
        $this->transferStockUseCase->execute($dto);

        return new JsonResponse([
            'success' => true,
            'message' => 'Stock transferred successfully',
        ]);
    }
}
