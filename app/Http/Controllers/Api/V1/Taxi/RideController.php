<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Taxi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Taxi\Services\TaxiBookingService;
use App\Domains\Taxi\DTOs\OrderRideDto;

final class RideController extends Controller
{
    public function __construct(
        private readonly TaxiBookingService $bookingService,
    ) {}

    public function order(Request $request): JsonResponse
    {
        $dto = OrderRideDto::fromRequest($request);
        
        try {
            $ride = $this->bookingService->createRide($dto);
            return new JsonResponse([
                "success" => true,
                "data" => [
                    "ride_id" => $ride->id,
                    "driver" => [
                        "id" => $ride->driver->id,
                        "name" => $ride->driver->first_name . " " . $ride->driver->last_name,
                        "rating" => $ride->driver->rating,
                    ],
                    "price" => $ride->price,
                    "distance_km" => $ride->distance_km,
                    "status" => $ride->status,
                ],
                "correlation_id" => $dto->correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse([
                "success" => false,
                "message" => $e->getMessage(),
                "correlation_id" => $dto->correlationId,
            ], $e->getCode() ?: 400);
        }
    }
}
