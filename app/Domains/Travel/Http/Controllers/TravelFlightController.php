<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelFlight;
use App\Domains\Travel\Services\FlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelFlightController
{
    public function __construct(
        private readonly FlightService $flightService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $per_page = $request->get('per_page', 20);
            $departure = $request->get('departure_airport');
            $arrival = $request->get('arrival_airport');

            $query = TravelFlight::query()
                ->where('tenant_id', tenant()->id)
                ->where('status', 'available');

            if ($departure) {
                $query->where('departure_airport', 'ilike', "%{$departure}%");
            }

            if ($arrival) {
                $query->where('arrival_airport', 'ilike', "%{$arrival}%");
            }

            $flights = $query->paginate($per_page, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $flights->items(),
                'pagination' => [
                    'total' => $flights->total(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list flights',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $flight = TravelFlight::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $flight,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flight not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $request->validate([
                'airline' => 'required|string|max:255',
                'flight_number' => 'required|string|unique:travel_flights',
                'departure_airport' => 'required|string',
                'arrival_airport' => 'required|string',
                'departure_time' => 'required|date_format:Y-m-d H:i:s',
                'arrival_time' => 'required|date_format:Y-m-d H:i:s|after:departure_time',
                'class' => 'required|in:economy,business,first',
                'available_seats' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
            ]);

            $flight = DB::transaction(function () use ($request, $correlationId) {
                return TravelFlight::create([
                    'tenant_id' => tenant()->id,
                    'airline' => $request->get('airline'),
                    'flight_number' => $request->get('flight_number'),
                    'departure_airport' => $request->get('departure_airport'),
                    'arrival_airport' => $request->get('arrival_airport'),
                    'departure_time' => $request->get('departure_time'),
                    'arrival_time' => $request->get('arrival_time'),
                    'duration_minutes' => $request->get('duration_minutes', 0),
                    'class' => $request->get('class'),
                    'available_seats' => $request->get('available_seats'),
                    'price' => $request->get('price'),
                    'commission_amount' => $request->get('price') * 0.14,
                    'status' => 'available',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Flight created', [
                'flight_id' => $flight->id,
                'flight_number' => $flight->flight_number,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $flight,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create flight',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = $request->get('correlation_id', Str::uuid()->toString());

        try {
            $flight = TravelFlight::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('update', $flight);

            $flight = DB::transaction(function () use ($request, $flight, $correlationId) {
                $flight->update([
                    'available_seats' => $request->get('available_seats', $flight->available_seats),
                    'price' => $request->get('price', $flight->price),
                    'status' => $request->get('status', $flight->status),
                    'correlation_id' => $correlationId,
                ]);

                return $flight;
            });

            return response()->json([
                'success' => true,
                'data' => $flight,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update flight',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $correlationId = Str::uuid()->toString();

        try {
            $flight = TravelFlight::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('delete', $flight);

            DB::transaction(function () use ($flight) {
                $flight->delete();
            });

            Log::channel('audit')->info('Flight deleted', [
                'flight_id' => $flight->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete flight',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
