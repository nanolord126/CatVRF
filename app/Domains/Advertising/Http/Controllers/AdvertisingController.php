<?php

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Models\AdCampaign;
use App\Domains\Advertising\Policies\AdCampaignPolicy;
use App\Domains\Advertising\Services\AdvertisingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdvertisingController extends Controller
{
    public function __construct(
        private AdvertisingService $service,
        private AdCampaignPolicy $policy
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdCampaign::class);
        return response()->json(
            AdCampaign::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function show(AdCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);
        return response()->json($campaign->load(['banners', 'interactions']));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', AdCampaign::class);
        
        $campaign = AdCampaign::create([...$request->validated(), 'tenant_id' => tenant()->id]);
        return response()->json($campaign, 201);
    }

    public function update(Request $request, AdCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);
        $campaign->update($request->validated());
        return response()->json($campaign);
    }

    public function destroy(AdCampaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);
        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted']);
    }
}
