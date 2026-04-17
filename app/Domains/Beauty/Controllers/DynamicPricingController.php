<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\DynamicPricingDto;
use App\Domains\Beauty\Requests\DynamicPricingRequest;
use App\Domains\Beauty\Resources\DynamicPricingResource;
use App\Domains\Beauty\Services\DynamicPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DynamicPricingController
{
    public function __construct(
        private DynamicPricingService $pricingService,
    ) {}

    public function calculate(DynamicPricingRequest $request): JsonResponse
    {
        $dto = DynamicPricingDto::from($request);

        $result = $this->pricingService->calculate($dto);

        return response()->json([
            'success' => true,
            'data' => new DynamicPricingResource($result),
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function getPriceHistory(Request $request): JsonResponse
    {
        $serviceId = (int) $request->input('service_id');
        $key = "beauty:price_history:{$serviceId}";
        $history = $this->redis->lrange($key, 0, 19);

        $parsedHistory = array_map(fn($item) => json_decode($item, true), $history);

        return response()->json([
            'success' => true,
            'data' => $parsedHistory,
        ]);
    }
}
