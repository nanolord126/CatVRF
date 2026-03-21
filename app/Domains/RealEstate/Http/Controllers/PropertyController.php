<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Services\PropertySearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Controller для управления объектами недвижимости.
 * Production 2026.
 */
final class PropertyController
{
    public function __construct(
        private readonly PropertySearchService $searchService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $filters = request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']);

            $properties = $this->searchService->searchProperties($filters, $correlationId);

            return response()->json([
                'success' => true,
                'data' => $properties,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка'], 500);
        }
    }

    public function show(Property $property): JsonResponse
    {
        try {
            $details = (new PropertySearchService())->getPropertyDetails($property);

            return response()->json([
                'success' => true,
                'data' => $details,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Объект не найден'], 404);
        }
    }

    public function details(Property $property): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'property' => $property->load(['rentalListing', 'saleListing', 'images', 'viewingAppointments']),
                'images' => $property->images()->orderBy('sort_order')->get(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }

    public function update(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }

    public function destroy(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        return response()->json(['success' => false], 501);
    }
}
