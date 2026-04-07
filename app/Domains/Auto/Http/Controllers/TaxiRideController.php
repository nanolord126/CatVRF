<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TaxiRideController extends Controller
{

    public function __construct(
            private readonly TaxiService $taxiService, private readonly LoggerInterface $logger
        ) {

    }

        /**
         * Заказать поездку
         */
        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid());

            try {
                // Валидация входных данных
                $validated = $request->validate([
                    'pickup.lat' => 'required|numeric',
                    'pickup.lng' => 'required|numeric',
                    'pickup.address' => 'string',
                    'dest.lat' => 'required|numeric',
                    'dest.lng' => 'required|numeric',
                    'dest.address' => 'string',
                    'cargo_type' => 'in:passenger,cargo,express',
                ]);

                $pickup = new LocationDTO($validated['pickup']['lat'], $validated['pickup']['lng'], $validated['pickup']['address'] ?? '');
                $dest = new LocationDTO($validated['dest']['lat'], $validated['dest']['lng'], $validated['dest']['address'] ?? '');

                $ride = $this->taxiService->createRide(
                    (int) $request->user()->tenant_id,
                    (int) $request->user()->id,
                    $pickup,
                    $dest,
                    $validated['cargo_type'] ?? 'passenger'
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'ride_id' => $ride->id,
                    'uuid' => $ride->uuid,
                    'price_cents' => $ride->price_cents,
                    'correlation_id' => $correlationId,
                ], 201);

            } catch (\Throwable $e) {
                $this->logger->error('Ride creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'error' => 'Failed to create ride request',
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
}
