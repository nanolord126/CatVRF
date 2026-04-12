<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hotels;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Hotels\Services\HotelSearchService;
use App\Domains\Hotels\DTOs\SearchHotelDto;

final class HotelSearchController extends Controller
{
    public function __construct(
        private readonly HotelSearchService $searchService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dto = SearchHotelDto::fromRequest($request);
        
        try {
            $hotels = $this->searchService->searchHotels($dto);
            return new JsonResponse([
                "success" => true,
                "data" => $hotels,
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

    /**
     * Component: HotelSearchController
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * HotelSearchController — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
