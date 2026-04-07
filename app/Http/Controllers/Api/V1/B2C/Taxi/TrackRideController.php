<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\B2C\Taxi;

use App\Domains\Auto\Taxi\Application\B2C\UseCases\TrackRideUseCase;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Class TrackRideController
 *
 * API Controller following CatVRF canon:
 * - Constructor injection for all dependencies
 * - Request validation via Form Requests
 * - Response via ResponseFactory DI
 * - correlation_id in all responses
 *
 * @see \App\Http\Controllers\BaseApiController
 * @package App\Http\Controllers\Api\V1\B2C\Taxi
 */
final class TrackRideController
{
    public function __construct(
        private readonly TrackRideUseCase $trackRideUseCase,
        private readonly ResponseFactory $response,
    ) {

    }

    /**
     * Handle __invoke operation.
     *
     * @throws \DomainException
     */
    public function __invoke(Request $request, string $rideId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            $rideDTO = ($this->trackRideUseCase)(
                rideId: new RideId($rideId),
                userId: $request->user()->id,
                correlationId: $correlationId,
            );

            return $this->response->json([
                'data' => $rideDTO->toArray(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            return $this->response->json(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return $this->response->json(['message' => 'Server error.'], 500);
        }
    }
}
