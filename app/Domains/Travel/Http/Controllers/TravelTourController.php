<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Controllers;

use App\Domains\Travel\Models\TravelTour;
use App\Domains\Travel\Models\TravelAgency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TravelTourController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $per_page = $request->get('per_page', 20);
            $agency_id = $request->get('agency_id');
            $destination = $request->get('destination');

            $query = TravelTour::query()
                ->where('tenant_id', tenant()->id)
                ->where('status', 'active');

            if ($agency_id) {
                $query->where('agency_id', $agency_id);
            }

            if ($destination) {
                $query->where('destination', 'ilike', "%{$destination}%");
            }

            $tours = $query->paginate($per_page, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $tours->items(),
                'pagination' => [
                    'total' => $tours->total(),
                    'per_page' => $tours->perPage(),
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to list tours', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list tours',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $tour = TravelTour::where('tenant_id', tenant()->id)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $tour,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour not found',
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
                'agency_id' => 'required|exists:travel_agencies,id',
                'name' => 'required|string|max:255',
                'destination' => 'required|string|max:255',
                'duration_days' => 'required|integer|min:1',
                'start_date' => 'required|date|after:today',
                'end_date' => 'required|date|after:start_date',
                'price' => 'required|numeric|min:0',
                'max_participants' => 'required|integer|min:1',
                'itinerary' => 'nullable|array',
                'inclusions' => 'nullable|array',
            ]);

            $agency = TravelAgency::findOrFail($request->get('agency_id'));

            $this->authorize('create', TravelTour::class);

            $tour = DB::transaction(function () use ($request, $agency, $correlationId) {
                return TravelTour::create([
                    'tenant_id' => tenant()->id,
                    'agency_id' => $agency->id,
                    'name' => $request->get('name'),
                    'destination' => $request->get('destination'),
                    'duration_days' => $request->get('duration_days'),
                    'start_date' => $request->get('start_date'),
                    'end_date' => $request->get('end_date'),
                    'price' => $request->get('price'),
                    'cost_price' => $request->get('cost_price', 0),
                    'max_participants' => $request->get('max_participants'),
                    'current_participants' => 0,
                    'itinerary' => $request->get('itinerary', []),
                    'inclusions' => $request->get('inclusions', []),
                    'status' => 'draft',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Travel tour created', [
                'tour_id' => $tour->id,
                'agency_id' => $agency->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $tour,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Tour creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create tour',
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
            $tour = TravelTour::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('update', $tour);

            $tour = DB::transaction(function () use ($request, $tour, $correlationId) {
                $tour->update([
                    'name' => $request->get('name', $tour->name),
                    'destination' => $request->get('destination', $tour->destination),
                    'duration_days' => $request->get('duration_days', $tour->duration_days),
                    'start_date' => $request->get('start_date', $tour->start_date),
                    'end_date' => $request->get('end_date', $tour->end_date),
                    'price' => $request->get('price', $tour->price),
                    'max_participants' => $request->get('max_participants', $tour->max_participants),
                    'itinerary' => $request->get('itinerary', $tour->itinerary),
                    'inclusions' => $request->get('inclusions', $tour->inclusions),
                    'correlation_id' => $correlationId,
                ]);

                return $tour;
            });

            Log::channel('audit')->info('Travel tour updated', [
                'tour_id' => $tour->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $tour,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tour',
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
            $tour = TravelTour::where('tenant_id', tenant()->id)->findOrFail($id);

            $this->authorize('delete', $tour);

            DB::transaction(function () use ($tour, $correlationId) {
                $tour->delete();
            });

            Log::channel('audit')->info('Travel tour deleted', [
                'tour_id' => $tour->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tour',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function getReviews(int $id): JsonResponse
    {
        try {
            $tour = TravelTour::where('tenant_id', tenant()->id)->findOrFail($id);

            $reviews = $tour->reviews()
                ->where('status', 'approved')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'correlation_id' => Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reviews',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function restore(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $tour = TravelTour::withTrashed()
                ->where('tenant_id', tenant()->id)
                ->findOrFail($id);

            DB::transaction(function () use ($tour, $correlationId) {
                $tour->restore();
                $tour->update(['correlation_id' => $correlationId]);
            });

            Log::channel('audit')->info('Travel tour restored', [
                'tour_id' => $tour->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore tour',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
