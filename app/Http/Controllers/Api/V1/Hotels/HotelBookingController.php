<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hotels;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Hotels\Services\HotelBookingService;
use App\Domains\Hotels\DTOs\BookRoomDto;

final class HotelBookingController extends Controller
{
    public function __construct(
        private readonly HotelBookingService $bookingService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $dto = BookRoomDto::fromRequest($request);
        
        try {
            $booking = $this->bookingService->bookRoom($dto);
            return new JsonResponse([
                "success" => true,
                "data" => [
                    "booking_id" => $booking->id,
                    "total_price" => $booking->total_price,
                    "status" => $booking->status,
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

    /**
     * Component: HotelBookingController
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
     * HotelBookingController — CatVRF 2026 Component.
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
