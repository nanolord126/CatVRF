<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Http\Controllers;

use App\Domains\Advertising\Models\AdCampaign;
use App\Domains\Advertising\Services\AdCampaignService;
use App\Domains\Advertising\Enums\CampaignStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdCampaignController
{
    public function __construct(private AdCampaignService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $campaigns = AdCampaign::where('tenant_id', tenant('id'))
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $campaigns->items(),
            'pagination' => [
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'campaign_type' => 'required|string',
            'status' => 'required|string',
            'budget' => 'required|numeric|min:0.01',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $campaign = $this->service->create($validated);

        return response()->json($campaign, 201);
    }

    public function show(string $id): JsonResponse
    {
        $campaign = $this->authorize('view', AdCampaign::findOrFail($id));

        return response()->json($campaign);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string',
            'budget' => 'sometimes|numeric|min:0.01',
            'end_date' => 'sometimes|date|after:start_date',
        ]);

        $campaign = $this->service->update($campaign, $validated);

        return response()->json($campaign);
    }

    public function destroy(string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $this->authorize('delete', $campaign);

        $this->service->delete($campaign);

        return response()->json(null, 204);
    }

    public function activate(string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $campaign->update(['status' => CampaignStatus::ACTIVE->value]);

        return response()->json($campaign);
    }

    public function pause(string $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        $this->authorize('update', $campaign);

        $campaign->update(['status' => CampaignStatus::PAUSED->value]);

        return response()->json($campaign);
    }
}
