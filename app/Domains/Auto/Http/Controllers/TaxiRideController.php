<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\TaxiRide;
use App\Domains\Auto\Models\TaxiDriver;
use App\Domains\Auto\Services\TaxiSurgeService;
use App\Domains\Auto\Policies\TaxiRidePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления поездками такси.
 * Production 2026.
 */
final class TaxiRideController
{
    public function __construct(
        private readonly TaxiSurgeService $surgeService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $rides = TaxiRide::query()
                ->with(['passenger', 'driver', 'vehicle'])
                ->where('tenant_id', tenant('id') ?? 1)
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $rides,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to fetch rides', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении поездок',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();

            $request->validate([
                'passenger_id' => 'required|exists:users,id',
                'driver_id' => 'required|exists:taxi_drivers,id',
                'vehicle_id' => 'required|exists:taxi_vehicles,id',
                'pickup_point' => 'required|array',
                'dropoff_point' => 'required|array',
            ]);

            $ride = DB::transaction(function () use ($request, $correlationId) {
                $driver = TaxiDriver::find($request->get('driver_id'));
                $surgeMultiplier = $this->surgeService->calculateSurgeMultiplier(
                    $request->get('pickup_point'),
                    tenant('id') ?? 1,
                    $correlationId,
                );

                $ride = TaxiRide::create([
                    'tenant_id' => tenant('id') ?? 1,
                    'passenger_id' => $request->get('passenger_id'),
                    'driver_id' => $request->get('driver_id'),
                    'vehicle_id' => $request->get('vehicle_id'),
                    'pickup_point' => $request->get('pickup_point'),
                    'dropoff_point' => $request->get('dropoff_point'),
                    'status' => 'waiting',
                    'surge_multiplier' => $surgeMultiplier,
                    'base_price' => 5000, 
                    'total_price' => 5000, 
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Ride created', [
                    'ride_id' => $ride->id,
                    'passenger_id' => $ride->passenger_id,
                    'driver_id' => $ride->driver_id,
                    'surge_multiplier' => $surgeMultiplier,
                    'correlation_id' => $correlationId,
                ]);

                return $ride;
            });

            return response()->json([
                'success' => true,
                'data' => $ride,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create ride', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании поездки',
            ], 500);
        }
    }

    public function show(TaxiRide $ride): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $ride->load(['passenger', 'driver', 'vehicle']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Поездка не найдена',
            ], 404);
        }
    }

    public function cancel(TaxiRide $ride, Request $request): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

            $this->authorize('cancel', $ride);

            $ride = DB::transaction(function () use ($ride, $correlationId) {
                $ride->update(['status' => 'cancelled']);

                Log::channel('audit')->info('Ride cancelled', [
                    'ride_id' => $ride->id,
                    'correlation_id' => $correlationId,
                ]);

                return $ride;
            });

            return response()->json([
                'success' => true,
                'data' => $ride,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to cancel ride', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене поездки',
            ], 500);
        }
    }

    public function rate(TaxiRide $ride, Request $request): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $this->authorize('rate', $ride);

            Log::channel('audit')->info('Ride rated', [
                'ride_id' => $ride->id,
                'rating' => $request->get('rating'),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Спасибо за оценку',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при оценке поездки',
            ], 500);
        }
    }

    public function status(TaxiRide $ride): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => $ride->status,
            'driver' => $ride->driver,
            'vehicle' => $ride->vehicle,
        ]);
    }
}
