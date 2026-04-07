<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\B2B\Taxi;

use App\Domains\Auto\Taxi\Application\B2B\UseCases\AcceptRideUseCase;
use App\Domains\Auto\Taxi\Application\B2B\UseCases\FinishRideUseCase;
use App\Domains\Auto\Taxi\Application\B2B\UseCases\StartRideUseCase;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use App\Http\Requests\Api\V1\B2B\Taxi\AcceptRideFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Contracts\Routing\ResponseFactory;

final class DriverRideController
{
    public function __construct(
        private readonly AcceptRideUseCase $acceptRideUseCase,
        private readonly StartRideUseCase $startRideUseCase,
        private readonly FinishRideUseCase $finishRideUseCase,
        private readonly RideRepositoryInterface $rideRepository,
        private readonly ResponseFactory $response,
    ) {

    }

    public function accept(AcceptRideFormRequest $request, string $rideId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            ($this->acceptRideUseCase)(
                rideId: new RideId($rideId),
                driverId: new DriverId($request->validated('driver_id')),
                correlationId: $correlationId,
            );

            return $this->response->json([
                'message' => 'Ride accepted.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            return $this->response->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->response->json(['message' => 'Server error.'], 500);
        }
    }

    public function start(AcceptRideFormRequest $request, string $rideId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            ($this->startRideUseCase)(
                rideId: new RideId($rideId),
                driverId: new DriverId($request->validated('driver_id')),
                correlationId: $correlationId,
            );

            return $this->response->json([
                'message' => 'Ride started.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            return $this->response->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->response->json(['message' => 'Server error.'], 500);
        }
    }

    public function finish(AcceptRideFormRequest $request, string $rideId): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            ($this->finishRideUseCase)(
                rideId: new RideId($rideId),
                driverId: new DriverId($request->validated('driver_id')),
                correlationId: $correlationId,
            );

            return $this->response->json([
                'message' => 'Ride finished.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\DomainException $e) {
            return $this->response->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return $this->response->json(['message' => 'Server error.'], 500);
        }
    }
}
