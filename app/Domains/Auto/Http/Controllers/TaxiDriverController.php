<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\TaxiDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления такси-водителями.
 * Production 2026.
 */
final class TaxiDriverController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();

            $drivers = TaxiDriver::query()
                ->where('tenant_id', tenant('id') ?? 1)
                ->where('is_active', true)
                ->with(['vehicles', 'rides'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $drivers,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to fetch drivers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении водителей',
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
                'user_id' => 'required|exists:users,id',
                'license_number' => 'required|unique:taxi_drivers',
            ]);

            $driver = DB::transaction(function () use ($request, $correlationId) {
                $driver = TaxiDriver::create([
                    'tenant_id' => tenant('id') ?? 1,
                    'user_id' => $request->get('user_id'),
                    'license_number' => $request->get('license_number'),
                    'rating' => 5.0,
                    'completed_rides' => 0,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Driver created', [
                    'driver_id' => $driver->id,
                    'user_id' => $driver->user_id,
                    'correlation_id' => $correlationId,
                ]);

                return $driver;
            });

            return response()->json([
                'success' => true,
                'data' => $driver,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create driver', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании водителя',
            ], 500);
        }
    }

    public function show(TaxiDriver $driver): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $driver->load(['vehicles', 'user']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Водитель не найден',
            ], 404);
        }
    }

    public function updateLocation(TaxiDriver $driver, Request $request): JsonResponse
    {
        try {
            $this->authorize('update', $driver);

            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            $driver->update([
                'current_location' => [
                    'lat' => $request->get('latitude'),
                    'lng' => $request->get('longitude'),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Локация обновлена',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении локации',
            ], 500);
        }
    }

    public function getLocation(TaxiDriver $driver): JsonResponse
    {
        return response()->json([
            'success' => true,
            'location' => $driver->current_location,
        ]);
    }

    public function deactivate(TaxiDriver $driver): JsonResponse
    {
        try {
            $this->authorize('deactivate', $driver);

            $driver->update(['is_active' => false]);

            Log::channel('audit')->info('Driver deactivated', [
                'driver_id' => $driver->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Водитель деактивирован',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при деактивации',
            ], 500);
        }
    }

    public function activate(TaxiDriver $driver): JsonResponse
    {
        try {
            $this->authorize('deactivate', $driver);

            $driver->update(['is_active' => true]);

            Log::channel('audit')->info('Driver activated', [
                'driver_id' => $driver->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Водитель активирован',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при активации',
            ], 500);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $drivers = TaxiDriver::query()
            ->where('is_active', true)
            ->select(['id', 'rating', 'completed_rides', 'current_location'])
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }
}
