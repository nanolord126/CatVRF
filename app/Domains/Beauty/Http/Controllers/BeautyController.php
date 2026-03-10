<?php

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Services\BeautyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BeautyController extends Controller
{
    public function __construct(private BeautyService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            BeautySalon::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', BeautySalon::class);
        return response()->json($this->service->createSalon($request->all()), 201);
    }

    public function show(BeautySalon $salon): JsonResponse
    {
        return response()->json($salon);
    }

    public function update(Request $request, BeautySalon $salon): JsonResponse
    {
        $this->authorize('update', $salon);
        return response()->json($this->service->updateSchedule($salon, $request->all()));
    }

    public function destroy(BeautySalon $salon): JsonResponse
    {
        $this->authorize('delete', $salon);
        $salon->delete();
        return response()->json(['message' => 'Salon deleted']);
    }
}
