<?php

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Services\RealEstateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RealEstateController extends Controller
{
    public function __construct(private RealEstateService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Property::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Property::class);
        return response()->json($this->service->createProperty($request->all()), 201);
    }

    public function show(Property $property): JsonResponse
    {
        return response()->json($property);
    }

    public function update(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);
        return response()->json($this->service->publishProperty($property));
    }

    public function destroy(Property $property): JsonResponse
    {
        $this->authorize('delete', $property);
        $property->delete();
        return response()->json(['message' => 'Property deleted']);
    }
}
