<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Services\PropertySearchService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
        private readonly FraudControlService $fraudControlService,
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
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'property_create', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $data = request()->validate([
                'address'     => 'required|string|max:500',
                'type'        => 'required|in:apartment,house,land,commercial',
                'area'        => 'required|numeric|min:1',
                'rooms'       => 'nullable|integer',
                'floor'       => 'nullable|integer',
                'description' => 'nullable|string',
                'price'       => 'nullable|integer|min:0',
                'geo_point'   => 'nullable|array',
            ]);

            $property = DB::transaction(function () use ($data, $correlationId) {
                return Property::create([
                    ...$data,
                    'tenant_id'      => tenant('id') ?? auth()->user()?->tenant_id ?? 1,
                    'owner_id'       => auth()->id(),
                    'status'         => 'active',
                    'correlation_id' => $correlationId,
                    'uuid'           => Str::uuid(),
                ]);
            });

            Log::channel('audit')->info('Property created', [
                'correlation_id' => $correlationId,
                'property_id'    => $property->id,
                'tenant_id'      => $property->tenant_id,
                'user_id'        => auth()->id(),
                'type'           => $property->type,
            ]);

            return response()->json([
                'success'        => true,
                'data'           => $property,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Property create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка создания объекта.', 'correlation_id' => $correlationId], 500);
        }
    }

    public function update(Property $property): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'property_update', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            $data = request()->validate([
                'address'     => 'nullable|string|max:500',
                'type'        => 'nullable|in:apartment,house,land,commercial',
                'area'        => 'nullable|numeric|min:1',
                'rooms'       => 'nullable|integer',
                'floor'       => 'nullable|integer',
                'description' => 'nullable|string',
                'price'       => 'nullable|integer|min:0',
                'status'      => 'nullable|in:active,sold,rented',
            ]);

            $before = $property->getAttributes();

            DB::transaction(function () use ($property, $data) {
                $property->update($data);
            });

            Log::channel('audit')->info('Property updated', [
                'correlation_id' => $correlationId,
                'property_id'    => $property->id,
                'tenant_id'      => $property->tenant_id,
                'user_id'        => auth()->id(),
                'before'         => $before,
                'after'          => $data,
            ]);

            return response()->json([
                'success'        => true,
                'data'           => $property->fresh(),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Property update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка обновления объекта.', 'correlation_id' => $correlationId], 500);
        }
    }

    public function destroy(Property $property): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $fraudResult = $this->fraudControlService->check(auth()->id() ?? 0, 'property_delete', 0, request()->ip(), null, $correlationId);
        if ($fraudResult['decision'] === 'block') {
            return response()->json(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
        }

        try {
            DB::transaction(function () use ($property) {
                $property->delete();
            });

            Log::channel('audit')->info('Property deleted', [
                'correlation_id' => $correlationId,
                'property_id'    => $property->id,
                'tenant_id'      => $property->tenant_id,
                'user_id'        => auth()->id(),
            ]);

            return response()->json([
                'success'        => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Property delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Ошибка удаления объекта.', 'correlation_id' => $correlationId], 500);
        }
    }
}
