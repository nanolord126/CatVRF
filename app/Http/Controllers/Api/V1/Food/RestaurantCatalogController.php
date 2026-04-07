<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Food\Services\RestaurantCatalogService;
use App\Domains\Food\DTOs\SearchRestaurantDto;

final class RestaurantCatalogController extends Controller
{
    public function __construct(
        private readonly RestaurantCatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dto = SearchRestaurantDto::fromRequest($request);
        
        try {
            $restaurants = $this->catalogService->searchNearby($dto);
            return new JsonResponse([
                "success" => true,
                "data" => $restaurants,
                "correlation_id" => $dto->correlationId,
            ], 200);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "success" => false,
                "message" => $e->getMessage(),
                "correlation_id" => $dto->correlationId,
            ], 400);
        }
    }

    public function menu(int $restaurantId, Request $request): JsonResponse
    {
        $correlationId = (string) $request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
        
        try {
            $menu = $this->catalogService->getRestaurantMenu($restaurantId, $correlationId);
            return new JsonResponse([
                "success" => true,
                "data" => $menu,
                "correlation_id" => $correlationId,
            ], 200);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "success" => false,
                "message" => $e->getMessage(),
                "correlation_id" => $correlationId,
            ], 404);
        }
    }
}
