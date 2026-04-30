<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InternalNotificationService;
use App\Services\NotificationComplianceService;
use App\Services\NotificationFrequencyLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class NotificationCampaignController extends Controller
{
    public function __construct(
        private readonly InternalNotificationService $notificationService,
        private readonly NotificationComplianceService $complianceService,
        private readonly NotificationFrequencyLimiter $frequencyLimiter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $campaigns = \App\Models\NotificationCampaign::where('tenant_id', $validated['tenant_id'])
            ->with('audience')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $campaigns,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
            'audience_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:promotion,information,reminder,alert',
            'channel' => 'required|in:in_app,email,push',
            'scheduled_at' => 'nullable|date',
            'created_by' => 'required|integer',
            'metadata' => 'nullable|array',
        ]);

        $campaign = \App\Models\NotificationCampaign::create($validated);

        return response()->json([
            'data' => $campaign->load('audience'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = \App\Models\NotificationCampaign::with('audience')->findOrFail($id);

        return response()->json([
            'data' => $campaign,
        ]);
    }

    public function checkCompliance(Request $request, int $id): JsonResponse
    {
        $campaign = \App\Models\NotificationCampaign::findOrFail($id);

        $result = $this->complianceService->checkCampaignCompliance($campaign, $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $result,
        ]);
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $result = $this->notificationService->sendCampaign($id, $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $result,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'approved_by' => 'required|integer',
        ]);

        $campaign = \App\Models\NotificationCampaign::findOrFail($id);
        $campaign->approve($validated['approved_by']);

        return response()->json([
            'data' => $campaign->load('audience'),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $campaign = \App\Models\NotificationCampaign::findOrFail($id);
        $campaign->reject($validated['notes'] ?? '');

        return response()->json([
            'data' => $campaign,
        ]);
    }

    public function checkFrequency(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $result = $this->frequencyLimiter->canSendBroadcast($validated['tenant_id'], $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $result,
        ]);
    }

    public function getComplianceStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $stats = $this->complianceService->getComplianceStatistics($validated['tenant_id']);

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function getTenantStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $stats = $this->notificationService->getTenantNotificationStats($validated['tenant_id']);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
