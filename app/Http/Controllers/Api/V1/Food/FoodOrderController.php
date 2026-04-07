<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Food\Services\FoodOrderingService;
use App\Domains\Food\DTOs\CreateFoodOrderDto;

final class FoodOrderController extends Controller
{
    public function __construct(
        private readonly FoodOrderingService $orderingService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $dto = CreateFoodOrderDto::fromRequest($request);
        
        try {
            $order = $this->orderingService->placeOrder($dto);
            return new JsonResponse([
                "success" => true,
                "data" => [
                    "order_id" => $order->id,
                    "total_price" => $order->total_price,
                    "status" => $order->status,
                ],
                "correlation_id" => $dto->correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "success" => false,
                "message" => $e->getMessage(),
                "correlation_id" => $dto->correlationId,
            ], 400);
        }
    }
}
