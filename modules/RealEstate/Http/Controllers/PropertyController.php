<?php declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Models\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Services\AI\RealEstateDesignConstructorService;

final class PropertyController
{
    public function __construct(
        private RealEstateDesignConstructorService $aiConstructor,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Property::where('tenant_id', tenant()->id);

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('property_type')) {
                $query->where('property_type', $request->input('property_type'));
            }

            if ($request->has('city')) {
                $query->where('city', 'like', "%{$request->input('city')}%");
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->input('min_price'));
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->input('max_price'));
            }

            if ($request->has('min_area')) {
                $query->where('area', '>=', $request->input('min_area'));
            }

            if ($request->has('max_area')) {
                $query->where('area', '<=', $request->input('max_area'));
            }

            if ($request->has('rooms')) {
                $query->where('rooms', $request->input('rooms'));
            }

            if ($request->has('virtual_tour')) {
                $query->whereNotNull('virtual_tour_url');
            }

            if ($request->has('ar_model')) {
                $query->whereNotNull('ar_model_url');
            }

            $properties = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $properties->items(),
                'meta' => [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $property = Property::where('id', $id)
                ->where('tenant_id', tenant()->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $property,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Property not found',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    public function analyzeDesign(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'property_id' => ['required', 'integer', 'exists:real_estate_properties,id'],
                'area' => ['nullable', 'numeric'],
                'rooms' => ['nullable', 'integer'],
                'property_type' => ['nullable', 'string'],
                'city' => ['nullable', 'string'],
            ]);

            $result = $this->aiConstructor->analyzePropertyAndRecommend([
                'property_id' => $request->input('property_id'),
                'area' => $request->input('area') ?? 100,
                'rooms' => $request->input('rooms') ?? 3,
                'property_type' => $request->input('property_type') ?? 'apartment',
                'city' => $request->input('city') ?? 'Москва',
                'correlation_id' => $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString(),
            ], auth()->id());

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $tenantId = tenant()->id;

            $statistics = [
                'total_properties' => Property::where('tenant_id', $tenantId)->count(),
                'available' => Property::where('tenant_id', $tenantId)->where('status', PropertyStatus::AVAILABLE)->count(),
                'sold' => Property::where('tenant_id', $tenantId)->where('status', PropertyStatus::SOLD)->count(),
                'rented' => Property::where('tenant_id', $tenantId)->where('status', PropertyStatus::RENTED)->count(),
                'with_virtual_tour' => Property::where('tenant_id', $tenantId)->whereNotNull('virtual_tour_url')->count(),
                'with_ar_model' => Property::where('tenant_id', $tenantId)->whereNotNull('ar_model_url')->count(),
                'avg_price' => Property::where('tenant_id', $tenantId)->where('status', PropertyStatus::AVAILABLE)->avg('price'),
                'avg_area' => Property::where('tenant_id', $tenantId)->avg('area'),
                'by_type' => [
                    'apartment' => Property::where('tenant_id', $tenantId)->where('property_type', PropertyType::APARTMENT)->count(),
                    'house' => Property::where('tenant_id', $tenantId)->where('property_type', PropertyType::HOUSE)->count(),
                    'commercial' => Property::where('tenant_id', $tenantId)->where('property_type', PropertyType::COMMERCIAL)->count(),
                    'land' => Property::where('tenant_id', $tenantId)->where('property_type', PropertyType::LAND)->count(),
                ],
                'by_city' => Property::where('tenant_id', $tenantId)
                    ->selectRaw('city, COUNT(*) as count')
                    ->groupBy('city')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->pluck('count', 'city')
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
