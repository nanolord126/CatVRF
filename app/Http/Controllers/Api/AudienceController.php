<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AudienceSegmentationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final readonly class AudienceController extends Controller
{
    public function __construct(
        private readonly AudienceSegmentationService $audienceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $audiences = \App\Models\InternalAudience::where('tenant_id', $validated['tenant_id'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'data' => $audiences,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'segmentation_type' => 'required|in:all_clients,master_clients,service_clients,ml_segment,custom',
            'segmentation_rules' => 'nullable|array',
            'master_id' => 'nullable|integer',
            'service_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
        ]);

        $audience = $this->audienceService->createAudience($validated, $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => $audience,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $audience = \App\Models\InternalAudience::findOrFail($id);

        return response()->json([
            'data' => $audience,
        ]);
    }

    public function getMembers(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:1000',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 1000;
        $offset = $validated['offset'] ?? 0;

        $members = $this->audienceService->getAudienceMembers($id, $limit, $offset);

        return response()->json([
            'data' => $members,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($members),
            ],
        ]);
    }

    public function getMasters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $masters = $this->audienceService->getAvailableMasters($validated['tenant_id']);

        return response()->json([
            'data' => $masters,
        ]);
    }

    public function getServices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $services = $this->audienceService->getAvailableServices($validated['tenant_id']);

        return response()->json([
            'data' => $services,
        ]);
    }

    public function validateOwnership(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer',
        ]);

        $isValid = $this->audienceService->validateAudienceOwnership($id, $validated['tenant_id']);

        return response()->json([
            'valid' => $isValid,
        ]);
    }
}
