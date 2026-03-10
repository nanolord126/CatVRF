<?php

namespace App\Domains\Insurance\Http\Controllers;

use App\Domains\Insurance\Models\InsurancePolicy;
use App\Domains\Insurance\Services\InsuranceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InsuranceController extends Controller
{
    public function __construct(private InsuranceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InsurancePolicy::class);
        return response()->json(
            InsurancePolicy::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', InsurancePolicy::class);
        return response()->json($this->service->createPolicy($request->all()), 201);
    }

    public function show(InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('view', $policy);
        return response()->json($policy);
    }

    public function update(Request $request, InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('update', $policy);
        return response()->json($this->service->activatePolicy($policy));
    }

    public function destroy(InsurancePolicy $policy): JsonResponse
    {
        $this->authorize('delete', $policy);
        $policy->delete();
        return response()->json(['message' => 'Policy deleted']);
    }
}
