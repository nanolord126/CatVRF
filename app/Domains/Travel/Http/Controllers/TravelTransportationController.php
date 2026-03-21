<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelTransportation;
use App\Domains\Travel\Services\TransportationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelTransportationController
{
    public function __construct(
        private readonly TransportationService $transportationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $per_page = $request->get('per_page', 20);
            $type = $request->get('type');

            $query = TravelTransportation::query()
                ->where('tenant_id', tenant()->id)
                ->where('status', 'available');

            if ($type) {
                $query->where('type', $type);
            }

            $transportation = $query->paginate($per_page, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $transportation->items(),
                'pagination' => [
                    'total' => $transportation->total(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list transportation',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transportation,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transportation not found',
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
                'type' => 'required|in:car_rental,bus,train,taxi,shuttle',
                'provider' => 'required|string|max:255',
                'location_pickup' => 'required|string',
                'location_dropoff' => 'required|string',
                'pickup_time' => 'required|date_format:Y-m-d H:i:s',
                'dropoff_time' => 'required|date_format:Y-m-d H:i:s|after:pickup_time',
                'capacity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
            ]);

            $transportation = DB::transaction(function () use ($request, $correlationId) {
                return TravelTransportation::create([
                    'tenant_id' => tenant()->id,
                    'type' => $request->get('type'),
                    'provider' => $request->get('provider'),
                    'location_pickup' => $request->get('location_pickup'),
                    'location_dropoff' => $request->get('location_dropoff'),
                    'pickup_time' => $request->get('pickup_time'),
                    'dropoff_time' => $request->get('dropoff_time'),
                    'capacity' => $request->get('capacity'),
                    'available_count' => $request->get('capacity'),
                    'price' => $request->get('price'),
                    'commission_amount' => $request->get('price') * 0.14,
                    'features' => $request->get('features', []),
                    'status' => 'available',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Transportation created', [
                'transportation_id' => $transportation->id,
                'type' => $transportation->type,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $transportation,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create transportation',
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
            $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('update', $transportation);

            $transportation = DB::transaction(function () use ($request, $transportation, $correlationId) {
                $transportation->update([
                    'price' => $request->get('price', $transportation->price),
                    'status' => $request->get('status', $transportation->status),
                    'features' => $request->get('features', $transportation->features),
                    'correlation_id' => $correlationId,
                ]);

                return $transportation;
            });

            return response()->json([
                'success' => true,
                'data' => $transportation,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transportation',
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
            $transportation = TravelTransportation::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('delete', $transportation);

            DB::transaction(function () use ($transportation) {
                $transportation->delete();
            });

            Log::channel('audit')->info('Transportation deleted', [
                'transportation_id' => $transportation->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transportation',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
