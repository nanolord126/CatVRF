<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Domains\Hotels\Models\HotelProperty;
use App\Domains\Hotels\Services\HotelPropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class HotelPropertyController
{
    public function __construct(private readonly HotelPropertyService $service) {}

    public function index(): JsonResponse
    {
        $properties = HotelProperty::where('tenant_id', tenant()->id)->paginate();

        return response()->json(['data' => $properties]);
    }

    public function show(HotelProperty $property): JsonResponse
    {
        $this->authorize('view', $property);

        return response()->json(['data' => $property->load(['rooms', 'bookings'])]);
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $this->authorize('create', HotelProperty::class);
        $correlationId = Str::uuid()->toString();

        try {
            $property = $this->service->createProperty([
                'name' => $request->input('name'),
                'address' => $request->input('address'),
                'geo_point' => $request->input('geo_point'),
                'star_rating' => $request->input('star_rating'),
            ], tenant()->id, $correlationId);

            return response()->json(['data' => $property], 201);
        } catch (\Exception $e) {
            \Log::channel('audit')->error('Property creation failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create property'], 422);
        }
    }

    public function update(Request $request, HotelProperty $property): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $this->authorize('update', $property);
        $correlationId = Str::uuid()->toString();

        try {
            $property->update($request->only(['name', 'address', 'star_rating']));
            \Log::channel('audit')->info('Property updated', ['correlation_id' => $correlationId, 'property_id' => $property->id]);

            return response()->json(['data' => $property]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update property'], 422);
        }
    }

    public function destroy(HotelProperty $property): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $this->authorize('delete', $property);
        $correlationId = Str::uuid()->toString();

        try {
            $property->delete();
            \Log::channel('audit')->info('Property deleted', ['correlation_id' => $correlationId, 'property_id' => $property->id]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete property'], 422);
        }
    }
}
