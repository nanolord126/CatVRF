<?php

declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\DTOs\LocationDTO;
use App\Domains\Auto\Services\TaxiService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API контроллер для такси и логистики
 */
final class TaxiRideController extends Controller
{
    public function __construct(
        private readonly TaxiService $taxiService
    ) {
    }

    /**
     * Заказать поездку
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid());

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

            return response()->json([
                'success' => true,
                'ride_id' => $ride->id,
                'uuid' => $ride->uuid,
                'price_cents' => $ride->price_cents,
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\Exception $e) {
            $this->log->error('Ride creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create ride request',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
