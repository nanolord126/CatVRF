<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelFlight;
use App\Domains\Travel\Services\FlightService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelFlightController extends Controller
{
    public function __construct(
        private readonly FlightService $flightService,
        private readonly FraudControlService $fraudControlService,
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
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        $this->fraudControlService->check(auth()->id() ?? 0, 'flight_store', 0, $request->ip(), null, $correlationId);

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

            $validated = $request->all();
            $flight = $this->db->transaction(function () use ($validated, $correlationId) {
                return TravelFlight::create([
                    'tenant_id' => tenant()->id,
                    'airline' => ($validated['airline'] ?? null),
                    'flight_number' => ($validated['flight_number'] ?? null),
                    'departure_airport' => ($validated['departure_airport'] ?? null),
                    'arrival_airport' => ($validated['arrival_airport'] ?? null),
                    'departure_time' => ($validated['departure_time'] ?? null),
                    'arrival_time' => ($validated['arrival_time'] ?? null),
                    'duration_minutes' => ($validated['duration_minutes'] ?? 0),
                    'class' => ($validated['class'] ?? null),
                    'available_seats' => ($validated['available_seats'] ?? null),
                    'price' => ($validated['price'] ?? null),
                    'commission_amount' => ($validated['price'] ?? null) * 0.14,
                    'status' => 'available',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            $this->log->channel('audit')->info('Flight created', [
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
        $correlationId = $request->get('correlation_id', Str::uuid()->toString());
        $this->fraudControlService->check(auth()->id() ?? 0, 'flight_update', 0, $request->ip(), null, $correlationId);

        try {
            $flight = TravelFlight::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('update', $flight);

            $validated = $request->all();
            $flight = $this->db->transaction(function () use ($validated, $flight, $correlationId) {
                $flight->update([
                    'available_seats' => ($validated['available_seats'] ?? $flight->available_seats),
                    'price' => ($validated['price'] ?? $flight->price),
                    'status' => ($validated['status'] ?? $flight->status),
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
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'flight_destroy', 0, request()->ip(), null, $correlationId);

        try {
            $flight = TravelFlight::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('delete', $flight);

            $this->db->transaction(function () use ($flight) {
                $flight->delete();
            });

            $this->log->channel('audit')->info('Flight deleted', [
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
