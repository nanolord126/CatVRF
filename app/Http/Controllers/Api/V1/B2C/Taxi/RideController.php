<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\B2C\Taxi;


use Illuminate\Auth\AuthManager;
use App\Domains\Auto\Taxi\Application\B2C\UseCases\RequestRideUseCase;
use App\Domains\Auto\Taxi\Application\Shared\DTOs\RequestRideDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\B2C\Taxi\RequestRideFormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class RideController extends Controller
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly RequestRideUseCase $requestRideUseCase,
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {

    }

    public function requestRide(RequestRideFormRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $dto = RequestRideDTO::fromArray(
                array_merge($request->validated(), [
                    'client_id' => $this->authManager->id(),
                    'correlation_id' => $correlationId,
                ])
            );

            $rideDto = ($this->requestRideUseCase)($dto);

            return $this->response->json([
                'data' => $rideDto,
                'correlation_id' => $correlationId,
            ], Response::HTTP_ACCEPTED);

        } catch (Throwable $e) {
            $this->logger->channel('audit')->error('Failed to request a ride', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->response->json([
                'message' => 'An error occurred while requesting the ride.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
