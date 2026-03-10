<?php

namespace App\Domains\Geo\Http\Controllers;

use App\Domains\Geo\Models\GeoZone;
use App\Domains\Geo\Policies\GeoZonePolicy;
use App\Domains\Geo\Services\GeoService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GeoController extends Controller
{
    public function __construct(
        private GeoService $service,
        private GeoZonePolicy $policy
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GeoZone::class);
        return response()->json(
            GeoZone::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function show(GeoZone $zone): JsonResponse
    {
        $this->authorize('view', $zone);
        return response()->json($zone);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', GeoZone::class);
        
        $zone = $this->service->createZone($request->all());
        return response()->json($zone, 201);
    }

    public function update(Request $request, GeoZone $zone): JsonResponse
    {
        $this->authorize('update', $zone);
        return response()->json($this->service->updateZone($zone, $request->all()));
    }

    public function destroy(GeoZone $zone): JsonResponse
    {
        $this->authorize('delete', $zone);
        $this->service->deleteZone($zone);
        return response()->json(['message' => 'Zone deleted']);
    }
}
